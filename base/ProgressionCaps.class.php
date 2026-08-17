<?php
/**
 * Shared balance-policy caps for the current 0.9.0 preview build.
 * Keep these values server-side; client controls are not authoritative.
 */
final class ProgressionCaps
{
    public const CAPS = [
        'infrastructure' => 30,
        'core_research' => 30,
        'stargate_hyperspace' => 25,
        'power' => 25,
        'combat_technology' => 30,
        'combat_site' => 25,
        'combat_installation' => 20,
        'military_rank' => 50,
        'unit_veterancy' => 10,
        'battle_waves' => 8,
    ];

    public static function max(string $family): int
    {
        return self::CAPS[$family] ?? 0;
    }

    public static function clamp(string $family, int $level): int
    {
        return max(0, min(self::max($family), $level));
    }

    public static function canUpgrade(string $family, int $current, int $delta = 1): bool
    {
        if ($delta < 1 || !array_key_exists($family, self::CAPS)) return false;
        return $current >= 0 && $current + $delta <= self::max($family);
    }

    public static function familyForTechnology(string $field): string
    {
        $stargate = ['galaxy', 'ascend'];
        $power = ['puCap', 'pmCap'];
        $combat = ['attack', 'defense', 'pDef'];
        $covert = ['covert', 'anticovert', 'cov_lvl', 'anti_lvl'];
        if (in_array($field, $stargate, true)) return 'stargate_hyperspace';
        if (in_array($field, $power, true)) return 'power';
        if (in_array($field, $combat, true)) return 'combat_technology';
        if (in_array($field, $covert, true)) return 'core_research';
        return 'core_research';
    }
}
?>
