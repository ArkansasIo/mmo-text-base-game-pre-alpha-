<?php
final class CorporationPolicy
{
    public const MAX_MEMBERS = 150;
    public const ROLES = ['member'=>0,'researcher'=>1,'operator'=>2,'officer'=>3,'director'=>4];
    public static function can(string $role,string $action): bool { $rank=self::ROLES[$role]??-1; return match($action){ 'contribute'=>$rank>=0,'research'=>$rank>=self::ROLES['researcher'],'operate'=>$rank>=self::ROLES['operator'],'manage'=>$rank>=self::ROLES['officer'],'director'=>$rank>=self::ROLES['director'],default=>false}; }
    public static function clampContribution(int $amount): int { return max(1,min(1000000000,$amount)); }
    public static function researchCost(string $key,int $nextLevel): int { $base=['fleet_doctrine'=>100,'industrial_logistics'=>125,'warp_coordination'=>150,'shield_network'=>175][$key]??0; return $base*max(1,$nextLevel); }
    public static function researchKeys(): array { return ['fleet_doctrine'=>'Fleet Doctrine','industrial_logistics'=>'Industrial Logistics','warp_coordination'=>'Warp Coordination','shield_network'=>'Shield Network']; }
    public static function validMission(string $mission): bool { return in_array($mission,['joint_defense','expedition','coordinated_strike','territory_relief'],true); }
}
?>
