<?php
/** Server-authoritative army capacity rules. */
final class ArmyPolicy
{
    public const BASE_ARMY_SIZE = 250000;

    public static function trainedTotal(array $units): int
    {
        return max(0, (int)($units['attack'] ?? 0))
            + max(0, (int)($units['defense'] ?? 0))
            + max(0, (int)($units['covert'] ?? 0))
            + max(0, (int)($units['anticovert'] ?? 0));
    }

    public static function canTrain(array $units, int $quantity): bool
    {
        $quantity = max(0, $quantity);
        return self::trainedTotal($units) + $quantity <= self::BASE_ARMY_SIZE;
    }

    public static function remaining(array $units): int
    {
        return max(0, self::BASE_ARMY_SIZE - self::trainedTotal($units));
    }
}
?>
