<?php
final class PvPExpansionPolicy
{
    public const MATCHMAKING_BASE_RANGE = 150;
    public const MATCHMAKING_RANGE_STEP = 100;
    public const MATCHMAKING_MAX_RANGE = 600;
    public const REWARD_TIERS = [
        1 => ['title'=>'Champion', 'dark_matter'=>5000, 'metal'=>2500000, 'crystal'=>1500000, 'deuterium'=>750000],
        2 => ['title'=>'Vice Champion', 'dark_matter'=>3000, 'metal'=>1500000, 'crystal'=>900000, 'deuterium'=>450000],
        3 => ['title'=>'War Marshal', 'dark_matter'=>1800, 'metal'=>900000, 'crystal'=>550000, 'deuterium'=>275000],
    ];

    public static function matchRange(int $rating, int $waitSeconds): int
    {
        $steps = max(0, (int)floor(max(0, $waitSeconds) / 60));
        return min(self::MATCHMAKING_MAX_RANGE, self::MATCHMAKING_BASE_RANGE + ($steps * self::MATCHMAKING_RANGE_STEP));
    }

    public static function eligible(int $rating, int $opponentRating, int $waitSeconds): bool
    {
        return abs($rating - $opponentRating) <= self::matchRange($rating, $waitSeconds);
    }

    public static function rewardForPlace(int $place): ?array
    {
        return self::REWARD_TIERS[$place] ?? null;
    }

    public static function replayEvents(array $battle, string $outcome, int $attackerLosses, int $defenderLosses): array
    {
        return [
            ['phase'=>'launch','at'=>0,'label'=>'Fleet launched','attacker_power'=>(int)$battle['attack_power'],'defender_power'=>(int)$battle['defense_power']],
            ['phase'=>'engagement','at'=>10,'label'=>'Fleets entered combat range','attacker_power'=>(int)$battle['attack_power'],'defender_power'=>(int)$battle['defense_power']],
            ['phase'=>'resolution','at'=>20,'label'=>str_replace('_',' ',ucfirst($outcome)),'attacker_losses'=>$attackerLosses,'defender_losses'=>$defenderLosses],
        ];
    }
}
?>
