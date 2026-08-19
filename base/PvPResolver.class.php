<?php
require_once __DIR__ . '/FleetPolicy.class.php';
require_once __DIR__ . '/PvPPolicy.class.php';
require_once __DIR__ . '/PvPRankingPolicy.class.php';
require_once __DIR__ . '/PvPExpansionPolicy.class.php';

final class PvPResolver
{
    private static function settleRanking(mysqli $db, array $battle, string $outcome, int $battleId): void
    {
        $season = PvPRankingPolicy::SEASON_CODE;
        $attacker = (int)$battle['attacker_uid']; $defender = (int)$battle['defender_uid'];
        $db->query("INSERT IGNORE INTO pvp_rankings(season_code,uid) VALUES ('$season',$attacker),('$season',$defender)");
        $rows = $db->query("SELECT uid,rating,wins,losses,draws FROM pvp_rankings WHERE season_code='$season' AND uid IN ($attacker,$defender) FOR UPDATE");
        $ratings = [];
        if ($rows) while ($row = $rows->fetch_assoc()) $ratings[(int)$row['uid']] = $row;
        foreach ([[$attacker, true], [$defender, false]] as [$uid, $isAttacker]) {
            $before = (int)($ratings[$uid]['rating'] ?? PvPRankingPolicy::STARTING_RATING);
            $opponent = $isAttacker ? $defender : $attacker;
            $opponentRating = (int)($ratings[$opponent]['rating'] ?? PvPRankingPolicy::STARTING_RATING);
            $result = PvPRankingPolicy::outcomeForPlayer($outcome, $isAttacker);
            $delta = PvPRankingPolicy::delta($before, $opponentRating, $result);
            $after = max(PvPRankingPolicy::MIN_RATING, min(PvPRankingPolicy::MAX_RATING, $before + $delta));
            $wins = $result === 'win' ? 1 : 0; $losses = $result === 'loss' ? 1 : 0; $draws = $result === 'draw' ? 1 : 0;
            $db->query("UPDATE pvp_rankings SET rating=$after,wins=wins+$wins,losses=losses+$losses,draws=draws+$draws,points_for=points_for+".(int)($isAttacker ? $battle['attack_power'] : $battle['defense_power']).",points_against=points_against+".(int)($isAttacker ? $battle['defense_power'] : $battle['attack_power']).",last_battle_at=NOW() WHERE season_code='$season' AND uid=$uid LIMIT 1");
            $db->query("INSERT IGNORE INTO pvp_rating_history(season_code,battle_id,uid,result,rating_before,rating_delta,rating_after) VALUES ('$season',$battleId,$uid,'$result',$before,$delta,$after)");
        }
        $db->query("UPDATE pvp_battles SET ranking_settled=1 WHERE battle_id=$battleId LIMIT 1");
    }

    public static function resolveDue(mysqli $db, int $limit = 50): int
    {
        $limit = max(1, min(500, $limit));
        $result = $db->query("SELECT battle_id FROM pvp_battles WHERE status='enroute' AND resolves_at<=NOW() ORDER BY resolves_at,battle_id LIMIT $limit");
        $resolved = 0;
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                if (self::resolveOne($db, (int)$row['battle_id'])) {
                    $resolved++;
                }
            }
            $result->free();
        }
        return $resolved;
    }

    public static function resolveOne(mysqli $db, int $battleId): bool
    {
        $db->begin_transaction();
        try {
            $stmt = $db->prepare("SELECT * FROM pvp_battles WHERE battle_id=? AND status='enroute' AND resolves_at<=NOW() FOR UPDATE");
            if (!$stmt) {
                throw new RuntimeException('Battle lookup failed');
            }
            $stmt->bind_param('i', $battleId);
            $stmt->execute();
            $battle = $stmt->get_result()->fetch_assoc();
            if (!$battle) {
                $db->rollback();
                return false;
            }
            $attackerFleet = json_decode((string)$battle['fleet_json'], true) ?: [];
            $attackerUnits = array_sum(array_map('intval', $attackerFleet));
            $defenderRows = $db->query('SELECT ship_type,quantity FROM player_fleet_inventory WHERE uid=' . (int)$battle['defender_uid'] . ' AND planet_id=' . (int)$battle['target_planet_id'] . ' AND quantity>0 FOR UPDATE');
            $defenderFleet = [];
            $defenderUnits = 0;
            $defenderPower = 0;
            if ($defenderRows) {
                while ($fleetRow = $defenderRows->fetch_assoc()) {
                    $shipType = (string)$fleetRow['ship_type'];
                    $quantity = (int)$fleetRow['quantity'];
                    if (isset(FleetPolicy::BLUEPRINTS[$shipType])) {
                        $defenderFleet[$shipType] = $quantity;
                        $defenderUnits += $quantity;
                        $defenderPower += FleetPolicy::BLUEPRINTS[$shipType]['defense'] * $quantity;
                    }
                }
                $defenderRows->free();
            }
            $defenderPower = max(1, $defenderPower);
            $outcome = PvPPolicy::outcome((int)$battle['attack_power'], $defenderPower, $battleId);
            $defenderUnitLossPercent = PvPPolicy::lossPercent($outcome, false);
            $attackerUnitLossPercent = PvPPolicy::lossPercent($outcome, true);
            $attackerLosses = (int)ceil($attackerUnits * $attackerUnitLossPercent / 100);
            $defenderLosses = (int)ceil($defenderUnits * $defenderUnitLossPercent / 100);
            foreach ($attackerFleet as $shipType => $quantity) {
                $quantity = (int)$quantity;
                $survivors = max(0, $quantity - (int)ceil($quantity * $attackerUnitLossPercent / 100));
                if ($survivors > 0 && isset(FleetPolicy::BLUEPRINTS[$shipType])) {
                    $db->query('INSERT INTO player_fleet_inventory(uid,planet_id,ship_type,quantity) VALUES (' . (int)$battle['attacker_uid'] . ',' . (int)$battle['origin_planet_id'] . ", '" . $db->real_escape_string((string)$shipType) . "',$survivors) ON DUPLICATE KEY UPDATE quantity=quantity+$survivors");
                }
            }
            foreach ($defenderFleet as $shipType => $quantity) {
                $lost = min($quantity, (int)ceil($quantity * $defenderUnitLossPercent / 100));
                if ($lost > 0) {
                    $db->query('UPDATE player_fleet_inventory SET quantity=GREATEST(0,quantity-' . $lost . ') WHERE uid=' . (int)$battle['defender_uid'] . ' AND planet_id=' . (int)$battle['target_planet_id'] . " AND ship_type='" . $db->real_escape_string((string)$shipType) . "' LIMIT 1");
                }
            }
            $loot = ['metal'=>0,'crystal'=>0,'deuterium'=>0];
            if ($outcome === 'attacker_victory') {
                $resources = $db->query('SELECT metal,crystal,deuterium FROM player_resources WHERE uid=' . (int)$battle['defender_uid'] . ' FOR UPDATE');
                $resourceRow = $resources ? $resources->fetch_assoc() : null;
                if ($resourceRow) {
                    $loot = PvPPolicy::loot((int)$resourceRow['metal'], (int)$resourceRow['crystal'], (int)$resourceRow['deuterium']);
                    $loot['metal'] = min($loot['metal'], (int)$resourceRow['metal']);
                    $loot['crystal'] = min($loot['crystal'], (int)$resourceRow['crystal']);
                    $loot['deuterium'] = min($loot['deuterium'], (int)$resourceRow['deuterium']);
                    $db->query('UPDATE player_resources SET metal=GREATEST(0,metal-' . $loot['metal'] . '),crystal=GREATEST(0,crystal-' . $loot['crystal'] . '),deuterium=GREATEST(0,deuterium-' . $loot['deuterium'] . ') WHERE uid=' . (int)$battle['defender_uid'] . ' LIMIT 1');
                    $db->query('INSERT IGNORE INTO player_resources (uid) VALUES (' . (int)$battle['attacker_uid'] . ')');
                    $db->query('UPDATE player_resources SET metal=metal+' . $loot['metal'] . ',crystal=crystal+' . $loot['crystal'] . ',deuterium=deuterium+' . $loot['deuterium'] . ' WHERE uid=' . (int)$battle['attacker_uid'] . ' LIMIT 1');
                }
            }
            $report = sprintf('PvP battle %d resolved: %s. Attack %d vs defense %d. Losses %d/%d. Loot M:%d C:%d D:%d.', $battleId, str_replace('_', ' ', $outcome), (int)$battle['attack_power'], (int)$battle['defense_power'], $attackerLosses, $defenderLosses, $loot['metal'], $loot['crystal'], $loot['deuterium']);
            $stmt = $db->prepare("UPDATE pvp_battles SET status='resolved',outcome=?,loot_metal=?,loot_crystal=?,loot_deuterium=?,attacker_losses=?,defender_losses=?,resolved_at=NOW(),report=? WHERE battle_id=? AND status='enroute'");
            $stmt->bind_param('siiiiisi', $outcome, $loot['metal'], $loot['crystal'], $loot['deuterium'], $attackerLosses, $defenderLosses, $report, $battleId);
            if (!$stmt->execute()) {
                throw new RuntimeException('Battle update failed');
            }
            $safeReport = $db->real_escape_string($report);
            $db->query("INSERT INTO pvp_alerts(uid,battle_id,alert_type,title,body) VALUES (" . (int)$battle['attacker_uid'] . ",$battleId,'battle_result','PvP battle resolved','$safeReport'),(" . (int)$battle['defender_uid'] . ",$battleId,'battle_result','Your world was attacked','$safeReport')");
            $events = PvPExpansionPolicy::replayEvents($battle, $outcome, $attackerLosses, $defenderLosses);
            foreach ($events as $sequence => $event) {
                $phase=$db->real_escape_string((string)$event['phase']); $label=$db->real_escape_string((string)$event['label']); $json=$db->real_escape_string(json_encode($event,JSON_THROW_ON_ERROR));
                $db->query("INSERT IGNORE INTO pvp_replay_events(battle_id,sequence_no,phase,event_at_seconds,label,event_json) VALUES ($battleId,".(int)$sequence.",'$phase',".(int)$event['at'].",'$label','$json')");
            }
            self::settleRanking($db, $battle, $outcome, $battleId);
            $db->commit();
            return true;
        } catch (Throwable $exception) {
            $db->rollback();
            return false;
        }
    }
}
?>
