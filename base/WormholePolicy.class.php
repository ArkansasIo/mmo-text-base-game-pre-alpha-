<?php
final class WormholePolicy
{
    private const DECAY = ['stable'=>1,'unstable'=>2,'ancient'=>2,'null'=>3,'quantum'=>4];
    public static function decayRate(string $class): int { return self::DECAY[$class] ?? 2; }
    public static function degradedStability(int $stability,string $class,int $elapsedMinutes): int { return max(0,min(100,$stability-(max(0,$elapsedMinutes)*self::decayRate($class)))); }
    public static function collapseRisk(int $stability,int $difficulty,string $class,int $elapsedMinutes): int { $degraded=self::degradedStability($stability,$class,$elapsedMinutes);$risk=5+(int)floor($difficulty/3)+(int)floor(max(0,$elapsedMinutes)/5)+(int)floor((100-$degraded)/4);return max(5,min(95,$risk)); }
    public static function exoticTier(int $difficulty,int $risk): int { return max(1,min(5,1+(int)floor($difficulty/25)+(int)floor($risk/30))); }
    public static function exoticReward(int $tier,int $difficulty): int { $tier=max(1,min(5,$tier));return random_int(2+$tier,8+($tier*6))+intdiv(max(0,$difficulty),10); }
    public static function collapse(int $risk): bool { return random_int(1,100)<=max(5,min(95,$risk)); }
}
?>
