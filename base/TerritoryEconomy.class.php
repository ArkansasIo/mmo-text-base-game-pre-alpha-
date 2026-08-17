<?php
/** Server-authoritative production and taxation rules for claimed guild territories. */
final class TerritoryEconomy
{
    public const TAX_MIN = 0;
    public const TAX_MAX = 25;
    public const TICK_MINUTES = 30;
    public const MAX_CATCHUP_TICKS = 48;

    public static function clampTax(int $rate): int { return max(self::TAX_MIN, min(self::TAX_MAX, $rate)); }

    public static function production(int $controlPoints, int $taxRate, int $guildLevel = 1): array
    {
        $points = max(0, min(1000, $controlPoints));
        $tax = self::clampTax($taxRate);
        $level = max(1, min(20, $guildLevel));
        $scale = 1 + ($points / 1000.0) + (($level - 1) * 0.05);
        $taxBase = (int)floor(10000 * $scale);
        return [
            'metal' => (int)floor(2500 * $scale),
            'crystal' => (int)floor(1200 * $scale),
            'energy' => (int)floor(900 * $scale),
            'credits' => (int)floor($taxBase * ($tax / 100)),
            'tax_base' => $taxBase,
        ];
    }

    public static function accrue(array $territory, int $guildLevel, int $now, int $lastAccrued): array
    {
        $elapsed = max(0, $now - $lastAccrued);
        $ticks = min(self::MAX_CATCHUP_TICKS, (int)floor($elapsed / (self::TICK_MINUTES * 60)));
        $one = self::production((int)($territory['control_points'] ?? 100), (int)($territory['tax_rate'] ?? 5), $guildLevel);
        foreach (['metal', 'crystal', 'energy', 'credits', 'tax_base'] as $key) $one[$key] *= $ticks;
        $one['ticks'] = $ticks;
        return $one;
    }
}
?>
