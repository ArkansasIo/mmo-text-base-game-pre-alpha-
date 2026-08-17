<?php
/** Dynamic guild-territory event policy. */
final class GuildEventPolicy
{
    public const TYPES = ['celestial_anomaly','pirate_invasion'];
    public const MAX_SEVERITY = 5;
    public const MIN_DURATION_MINUTES = 30;
    public const MAX_DURATION_MINUTES = 1440;
    public static function validType(string $type): bool { return in_array($type, self::TYPES, true); }
    public static function profile(string $type, int $severity): array
    {
        $severity=max(1,min(self::MAX_SEVERITY,$severity));
        if($type==='pirate_invasion') return ['name'=>'Pirate Invasion','production_penalty'=>min(50,5*$severity),'defense_bonus'=>0,'attack_power'=>100*$severity,'response_cost'=>10000*$severity,'duration'=>min(self::MAX_DURATION_MINUTES,60*$severity)];
        return ['name'=>'Celestial Anomaly','production_penalty'=>min(40,4*$severity),'defense_bonus'=>min(25,3*$severity),'attack_power'=>0,'response_cost'=>5000*$severity,'duration'=>min(self::MAX_DURATION_MINUTES,45*$severity)];
    }
    public static function eventChance(int $controlPoints, int $warCount): int { return max(1,min(35,3+intdiv(max(0,min(1000,$controlPoints)),250)+min(10,$warCount*2))); }
    public static function responseReduction(int $severity, int $responsePower): int { return max(0,min($severity, intdiv(max(0,$responsePower),100))); }
}
?>
