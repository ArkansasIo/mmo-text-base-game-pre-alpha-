<?php
final class PvPPolicy
{
    public const MIN_ATTACK_POWER = 1;
    public const MAX_FLEET_CAPACITY = 1000000;
    public const ATTACK_COOLDOWN_MINUTES = 5;
    public const NEW_PLAYER_PROTECTION_HOURS = 24;
    public const POST_ATTACK_PROTECTION_HOURS = 2;
    public const TRAVEL_SECONDS = 30;
    public const LOOT_RATE = 20;

    public static function validFleet(array $fleet): bool
    {
        if (!$fleet || count($fleet) > count(FleetPolicy::BLUEPRINTS)) {
            return false;
        }
        $capacity = 0;
        foreach ($fleet as $key => $quantity) {
            if (!FleetPolicy::valid((string)$key) || !is_numeric($quantity)) {
                return false;
            }
            $quantity = (int)$quantity;
            if ($quantity < 1 || $quantity > FleetPolicy::MAX_DEPLOYMENT) {
                return false;
            }
            $capacity += FleetPolicy::BLUEPRINTS[$key]['capacity'] * $quantity;
        }
        return $capacity > 0 && $capacity <= self::MAX_FLEET_CAPACITY;
    }

    public static function power(array $fleet): array
    {
        return FleetPolicy::fleetPower($fleet);
    }

    public static function outcome(int $attack, int $defense, int $battleId): string
    {
        $attackRoll = $attack * (96 + (($battleId * 17) % 9));
        $defenseRoll = $defense * (96 + (($battleId * 23) % 9));
        if ($attackRoll > ($defenseRoll * 105 / 100)) {
            return 'attacker_victory';
        }
        if ($defenseRoll > ($attackRoll * 105 / 100)) {
            return 'defender_victory';
        }
        return 'draw';
    }

    public static function lossPercent(string $outcome, bool $attacker): int
    {
        if ($outcome === 'draw') {
            return 18;
        }
        if (($outcome === 'attacker_victory') === $attacker) {
            return 8;
        }
        return 35;
    }

    public static function loot(int $metal, int $crystal, int $deuterium): array
    {
        return [
            'metal' => max(0, (int)floor($metal * self::LOOT_RATE / 100)),
            'crystal' => max(0, (int)floor($crystal * self::LOOT_RATE / 100)),
            'deuterium' => max(0, (int)floor($deuterium * self::LOOT_RATE / 100)),
        ];
    }
}
?>
