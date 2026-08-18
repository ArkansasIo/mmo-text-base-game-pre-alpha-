<?php
require_once __DIR__ . '/FleetPolicy.class.php';
require_once __DIR__ . '/ModuleFittingPolicy.class.php';
final class CorporationMarketPolicy
{
    public const FEE_BPS = 500;
    public static function validItem(string $type,string $key,int $level): bool { if($level<0||$level>100)return false; if($type==='blueprint')return isset(FleetPolicy::BLUEPRINTS[$key]); if($type==='module')return isset(ModuleFittingPolicy::MODULES[$key]); return false; }
    public static function quantity(int $value): int { return max(1,min(100000,$value)); }
    public static function price(int $value): int { return max(1,min(1000000000000,$value)); }
    public static function side(string $side): bool { return in_array($side,['ask','bid'],true); }
    public static function fee(int $gross): int { return (int)floor($gross*self::FEE_BPS/10000); }
}
?>
