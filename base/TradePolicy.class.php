<?php
/** Server-authoritative guild market and inter-territory trade rules. */
final class TradePolicy
{
    public const MAX_CARGO = 1000000000;
    public const MAX_UNIT_PRICE = 1000000;
    public const MIN_ROUTE_MINUTES = 30;
    public const MAX_ROUTE_MINUTES = 240;

    public static function resources(): array { return ['metal', 'crystal', 'energy']; }
    public static function validResource(string $resource): bool { return in_array($resource, self::resources(), true); }
    public static function clampCargo(int $quantity): int { return max(0, min($quantity, self::MAX_CARGO)); }
    public static function clampPrice(int $price): int { return max(1, min($price, self::MAX_UNIT_PRICE)); }

    public static function routeMinutes(string $origin, string $destination): int
    {
        $distance = abs(crc32(strtoupper($origin)) - crc32(strtoupper($destination))) % 211;
        return max(self::MIN_ROUTE_MINUTES, min(self::MAX_ROUTE_MINUTES, self::MIN_ROUTE_MINUTES + $distance));
    }
}
?>
