<?php
/**
 * Server-authoritative guild rules for Universe Civilization: Empire at Wars.
 */
final class GuildPolicy
{
    public const MAX_MEMBERS = 150;
    public const MAX_PENDING_INVITES = 25;
    public const MAX_DAILY_CONTRIBUTION = 1000000000;
    public const MAX_SHARED_RESOURCES = 1000000000000;

    public static function ranks(): array
    {
        return [
            4 => 'Founder',
            3 => 'Marshal',
            2 => 'Officer',
            1 => 'Member',
        ];
    }

    public static function canManage(int $rank): bool { return $rank >= 2; }
    public static function canManageOfficers(int $rank): bool { return $rank >= 3; }
    public static function canWithdraw(int $rank): bool { return $rank >= 3; }
    public static function clampContribution(int $amount): int { return max(0, min($amount, self::MAX_DAILY_CONTRIBUTION)); }

    public static function bonus(int $members, int $guildLevel, int $contributionScore): array
    {
        $members = max(0, min($members, self::MAX_MEMBERS));
        $guildLevel = max(1, min($guildLevel, 20));
        $contributionScore = max(0, min($contributionScore, 1000000000));
        return [
            'production_percent' => min(25, 2 + intdiv($members, 15) + intdiv($guildLevel, 4)),
            'defense_percent' => min(30, 3 + intdiv($members, 12) + intdiv($guildLevel, 3)),
            'research_percent' => min(20, 1 + intdiv($contributionScore, 100000000) + intdiv($guildLevel, 5)),
            'fleet_recovery_percent' => min(15, 2 + intdiv($members, 25) + intdiv($guildLevel, 5)),
        ];
    }
}
?>
