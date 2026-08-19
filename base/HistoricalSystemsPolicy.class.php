<?php
/**
 * Deterministic rules reconstructed from the attached Empire At Wars design document.
 * Inputs are server-side snapshots; callers must validate ownership and affordability.
 */
final class HistoricalSystemsPolicy
{
    public const TURN_INTERVAL_MINUTES = 30;
    public const MAX_ATTACK_TURNS = 10000;
    public const TURN_GENERATION_FLOOR = 4000;
    public const MAX_PLANETS = 10;
    public const MAX_OFFICERS = 25;

    public const RACES = [
        'asgard' => ['label' => 'Asgard', 'attack' => 1.00, 'defense' => 1.25, 'income' => 1.00, 'covert' => 1.00, 'ascended' => 'Ancient'],
        'goauld' => ['label' => "Goa'uld", 'attack' => 1.00, 'defense' => 1.00, 'income' => 1.25, 'covert' => 1.00, 'ascended' => 'System Lord'],
        'replicator' => ['label' => 'Replicator', 'attack' => 1.00, 'defense' => 1.00, 'income' => 1.00, 'covert' => 1.25, 'ascended' => 'NanoTiMaster'],
        'tauri' => ['label' => "Tau'ri", 'attack' => 1.25, 'defense' => 0.95, 'income' => 1.00, 'covert' => 1.00, 'ascended' => 'Tollan'],
    ];

    public const DEFCON = [
        'none' => ['label' => 'None', 'income' => 1.00, 'security' => 1.00],
        'low' => ['label' => 'Low', 'income' => 0.90, 'security' => 1.10],
        'medium' => ['label' => 'Medium', 'income' => 0.80, 'security' => 1.20],
        'high' => ['label' => 'High', 'income' => 0.60, 'security' => 1.40],
        'critical' => ['label' => 'Critical', 'income' => 0.30, 'security' => 1.70],
    ];

    public static function race(string $race): array
    {
        $key = strtolower(trim($race));
        return self::RACES[$key] ?? self::RACES['tauri'];
    }

    public static function defcon(string $level): array
    {
        $key = strtolower(trim($level));
        return self::DEFCON[$key] ?? self::DEFCON['none'];
    }

    public static function naturalIncome(int $untrained, int $miners, int $lifers, string $race = 'tauri', string $defcon = 'none'): int
    {
        $base = max(0, $untrained) * 20 + (max(0, $miners) + max(0, $lifers)) * 80;
        return (int)floor($base * self::race($race)['income'] * self::defcon($defcon)['income']);
    }

    public static function bankCapacity(int $naturalIncome, float $supporterMultiplier = 1.0): int
    {
        return (int)floor(max(0, $naturalIncome) * 48 * 1.5 * max(1.0, $supporterMultiplier));
    }

    public static function unitProductionUpgradeCost(int $currentLevel): int
    {
        return max(0, $currentLevel) * 5000 + 10000;
    }

    public static function strikePower(int $normalWeaponStrength, int $superWeaponStrength, float $offenseTech = 1.0, string $race = 'tauri', int $planetBonus = 0): int
    {
        $base = max(0, $normalWeaponStrength) * 5 + max(0, $superWeaponStrength) * 10 + max(0, $planetBonus);
        return (int)floor($base * max(0.0, $offenseTech) * self::race($race)['attack']);
    }

    public static function defensePower(int $normalWeaponStrength, int $superWeaponStrength, float $defenseTech = 1.0, string $race = 'tauri', int $planetBonus = 0): int
    {
        $base = max(0, $normalWeaponStrength) * 5 + max(0, $superWeaponStrength) * 10 + max(0, $planetBonus);
        return (int)floor($base * max(0.0, $defenseTech) * self::race($race)['defense']);
    }

    public static function covertPower(int $agents, int $level, float $technology = 1.0, string $race = 'tauri'): int
    {
        $agents = max(0, $agents);
        return (int)floor(((sqrt(2 ** max(0, $level)) * $agents * max(0.0, $technology) * self::race($race)['covert']) + $agents) * 10);
    }

    public static function antiCovertPower(int $agents, int $level, float $technology = 1.0, string $race = 'tauri'): int
    {
        $agents = max(0, $agents);
        return (int)floor(((sqrt(2 ** (max(0, $level) + 2)) * $agents * max(0.0, $technology) * self::race($race)['covert']) + $agents) * 10);
    }

    public static function planetBonus(string $type, int $level): int
    {
        $perLevel = ['attack' => 30000, 'defense' => 25000, 'covert' => 181000, 'anti_covert' => 181000, 'unit_production' => 1, 'income' => 3840];
        return max(0, $level) * ($perLevel[strtolower($type)] ?? 0);
    }

    public static function planetIncome(int $incomeBonusLevel = 0): int
    {
        return 8000 + self::planetBonus('income', $incomeBonusLevel);
    }

    public static function overallRank(array $ranks): float
    {
        $values = array_map(static fn($v): float => max(0.0, (float)$v), array_slice(array_values($ranks), 0, 4));
        return $values ? array_sum($values) / count($values) : 0.0;
    }

    public static function hostileActionAllowed(array $state, int $now = 0): bool
    {
        $now = $now ?: time();
        if (!empty($state['vacation_until']) && strtotime((string)$state['vacation_until']) > $now) return false;
        if (!empty($state['ppt_until']) && strtotime((string)$state['ppt_until']) > $now) return false;
        return true;
    }

    public static function turnAward(int $currentTurns, int $ticks = 1, int $perTick = 3): int
    {
        if ($currentTurns >= self::TURN_GENERATION_FLOOR) return 0;
        return min(self::MAX_ATTACK_TURNS - max(0, $currentTurns), max(0, $ticks) * max(0, $perTick));
    }

    public static function ascendedRace(string $race): string
    {
        return (string)self::race($race)['ascended'];
    }
}
