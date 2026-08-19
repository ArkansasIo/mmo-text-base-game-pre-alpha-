<?php
final class LeaderboardPolicy
{
    public const BOARDS=['territory_power','guild_power','member_contribution','member_fleet','pvp_ranked'];
    public const ACHIEVEMENTS=['territory_pioneer','industrial_magnate','fleet_commander','war_hero'];
    public static function validBoard(string $key): bool{return in_array($key,self::BOARDS,true);}
    public static function rank(array $scores): array {arsort($scores,SORT_NUMERIC);$out=[];$n=0;$last=null;foreach($scores as $id=>$score){$n++;if($last!==$score)$rank=$n;$out[$id]=['score'=>(int)$score,'rank'=>$rank];$last=$score;}return $out;}
    public static function unlocked(int $progress,int $target): bool{return $progress>=max(1,$target);}
}
?>
