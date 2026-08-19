<?php
require_once __DIR__ . '/PvPExpansionPolicy.class.php';
final class PvPSeasonService
{
    public static function distributeEnded(mysqli $db): int
    {
        $count=0;$seasons=$db->query("SELECT season_code FROM pvp_seasons WHERE status='active' AND ends_at<=NOW() FOR UPDATE");if(!$seasons)return 0;
        while($season=$seasons->fetch_assoc()){$code=$db->real_escape_string($season['season_code']);$db->begin_transaction();try{$rows=$db->query("SELECT uid,rating FROM pvp_rankings WHERE season_code='$code' ORDER BY rating DESC,wins DESC,uid ASC LIMIT 3");$place=0;while($rows&&($row=$rows->fetch_assoc())){$place++;$reward=PvPExpansionPolicy::rewardForPlace($place);if(!$reward)continue;$uid=(int)$row['uid'];$db->query("INSERT IGNORE INTO pvp_season_rewards(season_code,uid,place_position,title,dark_matter,metal,crystal,deuterium) VALUES ('$code',$uid,$place,'".$db->real_escape_string($reward['title'])."',".(int)$reward['dark_matter'].",".(int)$reward['metal'].",".(int)$reward['crystal'].",".(int)$reward['deuterium'].')');$count++;}$db->query("UPDATE pvp_seasons SET status='ended' WHERE season_code='$code' LIMIT 1");$db->commit();}catch(Throwable $e){$db->rollback();}}return $count;
    }
    public static function claim(mysqli $db,string $season,int $uid): bool
    {
        $db->begin_transaction();try{$code=$db->real_escape_string($season);$row=$db->query("SELECT * FROM pvp_season_rewards WHERE season_code='$code' AND uid=$uid AND claimed_at IS NULL FOR UPDATE");$reward=$row?$row->fetch_assoc():null;if(!$reward)throw new RuntimeException('No unclaimed seasonal reward.');$db->query("INSERT IGNORE INTO player_resources(uid) VALUES ($uid)");$ok=$db->query("UPDATE player_resources SET dark_matter=dark_matter+".(int)$reward['dark_matter'].",metal=metal+".(int)$reward['metal'].",crystal=crystal+".(int)$reward['crystal'].",deuterium=deuterium+".(int)$reward['deuterium']." WHERE uid=$uid LIMIT 1");if(!$ok)throw new RuntimeException('Reward resource settlement failed.');$db->query("UPDATE pvp_season_rewards SET claimed_at=NOW() WHERE reward_id=".(int)$reward['reward_id'].' AND claimed_at IS NULL LIMIT 1');$db->commit();return true;}catch(Throwable $e){$db->rollback();return false;}
    }
}
?>
