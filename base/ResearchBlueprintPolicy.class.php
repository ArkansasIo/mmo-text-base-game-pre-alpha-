<?php
require_once __DIR__ . '/FleetPolicy.class.php';
require_once __DIR__ . '/NotificationPolicy.class.php';
final class ResearchBlueprintPolicy
{
    public static function requirements(array $blueprint): array
    {
        $tier = max(1, (int)($blueprint['tier'] ?? 1));
        return ['research_campus'=>max(0, $tier - 1), 'simulation_core'=>max(0, (int)floor(($tier - 1) / 2)), 'data_vault'=>max(0, (int)floor(($tier - 3) / 2))];
    }
    public static function unlockCost(array $blueprint): array
    {
        return ['naquadah'=>(int)max(1000, round($blueprint['metal'] * 2.5)), 'metal'=>(int)max(500, round($blueprint['metal'] * .35)), 'crystal'=>(int)max(250, round($blueprint['crystal'] * .35)), 'deuterium'=>(int)max(100, round($blueprint['energy'] * .25)), 'energy'=>(int)max(100, round($blueprint['energy'] * .15))];
    }
    public static function meetsResearch(array $levels, array $blueprint): bool
    {
        foreach (self::requirements($blueprint) as $key => $required) if ((int)($levels[$key] ?? 0) < $required) return false;
        return true;
    }
    public static function unlocked(mysqli $db, int $uid, string $key): bool
    {
        $safe = $db->real_escape_string($key);
        $q = $db->query("SELECT 1 FROM player_blueprint_research WHERE uid=$uid AND blueprint_key='$safe' AND status='unlocked' LIMIT 1");
        return (bool)$q && $q->num_rows > 0;
    }
    public static function levels(mysqli $db, int $uid): array
    {
        $levels = ['research_campus'=>0,'simulation_core'=>0,'data_vault'=>0];
        $q = $db->query("SELECT research_campus,simulation_core,data_vault FROM research_infrastructure WHERE uid=".(int)$uid." LIMIT 1");
        if ($q && ($row = $q->fetch_assoc())) foreach ($levels as $key => $_) $levels[$key] = (int)($row[$key] ?? 0);
        return $levels;
    }
    public static function unlock(mysqli $db, int $uid, string $key): array
    {
        self::ensureTable($db); $blueprint = FleetPolicy::blueprint($key);
        if (!$blueprint) return ['ok'=>false,'message'=>'Unknown blueprint.'];
        if (self::unlocked($db,$uid,$key)) return ['ok'=>false,'message'=>'Blueprint is already researched.'];
        if (!self::meetsResearch(self::levels($db,$uid),$blueprint)) return ['ok'=>false,'message'=>'Research infrastructure prerequisites are not met.'];
        $cost = self::unlockCost($blueprint); $db->begin_transaction();
        try {
            $bank = $db->query("SELECT onHand FROM bank WHERE uid=".(int)$uid." FOR UPDATE")->fetch_assoc();
            $res = $db->query("SELECT metal,crystal,deuterium,energy FROM player_resources WHERE uid=".(int)$uid." FOR UPDATE")->fetch_assoc();
            if (!$bank || !$res || (int)$bank['onHand'] < $cost['naquadah'] || (int)$res['metal'] < $cost['metal'] || (int)$res['crystal'] < $cost['crystal'] || (int)$res['deuterium'] < $cost['deuterium'] || (int)$res['energy'] < $cost['energy']) throw new RuntimeException('Insufficient research reserves.');
            $ok = $db->query("UPDATE bank SET onHand=onHand-{$cost['naquadah']} WHERE uid=".(int)$uid." AND onHand>={$cost['naquadah']} LIMIT 1");
            $ok = $ok && $db->query("UPDATE player_resources SET metal=metal-{$cost['metal']},crystal=crystal-{$cost['crystal']},deuterium=deuterium-{$cost['deuterium']},energy=energy-{$cost['energy']} WHERE uid=".(int)$uid." AND metal>={$cost['metal']} AND crystal>={$cost['crystal']} AND deuterium>={$cost['deuterium']} AND energy>={$cost['energy']} LIMIT 1");
            $safe = $db->real_escape_string($key); $ok = $ok && $db->query("INSERT INTO player_blueprint_research(uid,blueprint_key,source,status,unlocked_at) VALUES(".(int)$uid.",'$safe','research','unlocked',NOW())"); $ok = $ok && $db->query("INSERT INTO player_blueprints(uid,blueprint_key,quantity) VALUES(".(int)$uid.",'$safe',1) ON DUPLICATE KEY UPDATE quantity=quantity"); $ok = $ok && NotificationPolicy::push($db,$uid,'research_complete','Blueprint research complete',$blueprint['name'].' is now unlocked for shipyard construction, PvP deployment, and player exchange.','blueprint_research:'.$key,['blueprint_key'=>$key,'tier'=>(int)$blueprint['tier']]);
            if (!$ok) throw new RuntimeException('Blueprint unlock could not be recorded.'); $db->commit(); return ['ok'=>true,'message'=>$blueprint['name'].' researched and added to your blueprint library.'];
        } catch (Throwable $e) { $db->rollback(); return ['ok'=>false,'message'=>$e->getMessage() ?: 'Blueprint research failed.']; }
    }
    public static function ensureTable(mysqli $db): void
    {
        $db->query("CREATE TABLE IF NOT EXISTS player_blueprint_research (uid INT NOT NULL,blueprint_key VARCHAR(64) NOT NULL,source ENUM('starter','research','market','trade','legacy') NOT NULL DEFAULT 'research',status ENUM('locked','unlocked') NOT NULL DEFAULT 'unlocked',unlocked_at DATETIME NULL,PRIMARY KEY(uid,blueprint_key),KEY idx_blueprint_research_status(uid,status,blueprint_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}
?>
