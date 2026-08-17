<?php
/** Guild diplomacy and warfare limits. */
final class GuildWarfarePolicy
{
    public const WAR_COOLDOWN_HOURS = 24;
    public const RAID_TRAVEL_MINUTES = 30;
    public const RAID_LOOT_PERCENT = 10;
    public const MAX_RAID_POWER = 1000000;
    public static function relationPair(int $a, int $b): array { return [min($a,$b), max($a,$b)]; }
    public static function raidPower(int $army, int $militaryTech): int { return max(1,min(self::MAX_RAID_POWER,$army + ($militaryTech*1000))); }
    public static function defensePower(int $controlPoints, int $defenseLevel, int $defenseTech): int { return max(1,($controlPoints*max(1,$defenseLevel)) + ($defenseTech*1000)); }
    public static function loot(int $stock, int $raidPower, int $defensePower): int { if($raidPower<=$defensePower)return 0; return max(0,min($stock,(int)floor($stock*self::RAID_LOOT_PERCENT/100))); }
}
?>
