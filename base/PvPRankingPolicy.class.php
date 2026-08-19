<?php
final class PvPRankingPolicy
{
    public const STARTING_RATING = 1000;
    public const MIN_RATING = 100;
    public const MAX_RATING = 5000;
    public const K_FACTOR = 32;
    public const SEASON_CODE = 'S1-2026';
    public const REMATCH_COOLDOWN_HOURS = 24;

    public static function expected(int $rating, int $opponentRating): float
    {
        return 1 / (1 + pow(10, (($opponentRating - $rating) / 400)));
    }

    public static function delta(int $rating, int $opponentRating, string $outcome): int
    {
        $score = $outcome === 'win' ? 1.0 : ($outcome === 'draw' ? 0.5 : 0.0);
        $change = (int)round(self::K_FACTOR * ($score - self::expected($rating, $opponentRating)));
        return max(-self::K_FACTOR, min(self::K_FACTOR, $change));
    }

    public static function division(int $rating): string
    {
        if ($rating >= 1800) return 'Admiral';
        if ($rating >= 1500) return 'Commodore';
        if ($rating >= 1250) return 'Captain';
        return 'Commander';
    }

    public static function outcomeForPlayer(string $battleOutcome, bool $attacker): string
    {
        if ($battleOutcome === 'draw') return 'draw';
        $attackerWon = $battleOutcome === 'attacker_victory';
        return (($attackerWon && $attacker) || (!$attackerWon && !$attacker)) ? 'win' : 'loss';
    }
}
?>
