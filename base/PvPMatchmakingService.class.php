<?php
require_once __DIR__ . '/PvPRankingPolicy.class.php';
require_once __DIR__ . '/PvPExpansionPolicy.class.php';
final class PvPMatchmakingService
{
    public static function queue(mysqli $db, string $season, int $uid, int $rating, int $origin, int $target, array $fleet, array $fitting): bool
    {
        $fleetJson=$db->real_escape_string(json_encode($fleet,JSON_THROW_ON_ERROR));$fittingJson=$db->real_escape_string(json_encode($fitting,JSON_THROW_ON_ERROR));$division=$db->real_escape_string(PvPRankingPolicy::division($rating));
        $db->query("INSERT INTO pvp_matchmaking_queue(season_code,uid,rating,division,origin_planet_id,target_planet_id,fleet_json,fitting_json) VALUES ('$season',$uid,$rating,'$division',$origin,$target,'$fleetJson','$fittingJson') ON DUPLICATE KEY UPDATE rating=VALUES(rating),division=VALUES(division),origin_planet_id=VALUES(origin_planet_id),target_planet_id=VALUES(target_planet_id),fleet_json=VALUES(fleet_json),fitting_json=VALUES(fitting_json),queued_at=NOW(),status='queued'");
        return $db->affected_rows >= 0;
    }
    public static function cancel(mysqli $db, string $season, int $uid): bool { return (bool)$db->query("UPDATE pvp_matchmaking_queue SET status='cancelled' WHERE season_code='$season' AND uid=$uid AND status='queued' LIMIT 1"); }
    public static function matchDue(mysqli $db, string $season, int $limit=25): int
    {
        $matched=0;$rows=$db->query("SELECT * FROM pvp_matchmaking_queue WHERE season_code='".$db->real_escape_string($season)."' AND status='queued' ORDER BY queued_at,queue_id LIMIT ".max(1,min(200,$limit)));
        if(!$rows)return 0;
        $entries=[];while($r=$rows->fetch_assoc())$entries[]=$r;
        foreach($entries as $i=>$a){if(($a['status']??'')!=='queued')continue;$wait=max(0,time()-strtotime($a['queued_at']));$range=PvPExpansionPolicy::matchRange((int)$a['rating'],$wait);foreach($entries as $j=>$b){if($i===$j||($b['status']??'')!=='queued'||(int)$a['uid']===(int)$b['uid'])continue;if(abs((int)$a['rating']-(int)$b['rating'])>$range)continue;$db->begin_transaction();try{$fleet=$db->real_escape_string($a['fleet_json']);$fit=$db->real_escape_string($a['fitting_json']);$eta=date('Y-m-d H:i:s',time()+30);$stmt=$db->prepare("INSERT INTO pvp_battles(attacker_uid,defender_uid,target_planet_id,origin_planet_id,fleet_json,fitting_json,attack_power,defense_power,resolves_at) VALUES (?,?,?,?,?,?,1,1,?)");$att=(int)$a['uid'];$def=(int)$b['uid'];$target=(int)$b['target_planet_id'];$origin=(int)$a['origin_planet_id'];$stmt->bind_param('iiiisss',$att,$def,$target,$origin,$fleet,$fit,$eta);if(!$stmt->execute())throw new RuntimeException('match battle insert failed');$battle=(int)$db->insert_id;$db->query("UPDATE pvp_matchmaking_queue SET status='matched',matched_battle_id=$battle WHERE queue_id IN (".(int)$a['queue_id'].",".(int)$b['queue_id'].") AND status='queued'");$db->commit();$matched++;$entries[$i]['status']='matched';$entries[$j]['status']='matched';break;}catch(Throwable $e){$db->rollback();}}}return $matched;
    }
}
?>
