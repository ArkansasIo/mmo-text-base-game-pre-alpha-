<?php
require_once __DIR__ . '/FleetPolicy.class.php';
require_once __DIR__ . '/ModuleFittingPolicy.class.php';
final class PlayerMarketPolicy
{
    public const ITEM_TYPES = ['blueprint','module'];
    public const MARKET_FEE_PERCENT = 5;
    public const MAX_QUANTITY = 100000;
    public const MAX_PRICE = 100000000000;
    public static function validType(string $type): bool { return in_array($type, self::ITEM_TYPES, true); }
    public static function validItem(string $type, string $key): bool { return $type === 'blueprint' ? FleetPolicy::valid($key) : ModuleFittingPolicy::validModule($key); }
    public static function normalizeQuantity(int $quantity): int { return max(1, min(self::MAX_QUANTITY, $quantity)); }
    public static function normalizePrice(int $price): int { return max(1, min(self::MAX_PRICE, $price)); }
    public static function label(string $type, string $key, int $level = 0): string
    {
        if ($type === 'blueprint') return (string)(FleetPolicy::blueprint($key)['name'] ?? $key);
        return (string)(ModuleFittingPolicy::module($key)['name'] ?? $key) . ($level > 0 ? ' Mk '.$level : '');
    }
}
?>
