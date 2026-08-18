<?php
final class ResourcePolicy
{
    public const STRATEGIC = ['antimatter','iridium','tritanium','plasma','exotic_matter'];
    public const ALL = ['metal','crystal','deuterium','energy','antimatter','iridium','tritanium','plasma','exotic_matter','dark_matter'];
    public static function labels(): array { return ['metal'=>'Metal','crystal'=>'Crystal','deuterium'=>'Deuterium','energy'=>'Energy','antimatter'=>'Antimatter','iridium'=>'Iridium','tritanium'=>'Tritanium','plasma'=>'Plasma','exotic_matter'=>'Exotic Matter','dark_matter'=>'Dark Matter']; }
    public static function valid(string $resource): bool { return in_array($resource,self::ALL,true); }
    public static function isPremium(string $resource): bool { return $resource==='dark_matter'; }
    public static function clamp(int $amount): int { return max(0,min(1000000000000,$amount)); }
    public static function defaultProduction(string $resource): int { return match($resource){ 'antimatter'=>2,'iridium'=>4,'tritanium'=>5,'plasma'=>2,'exotic_matter'=>1,'dark_matter'=>0,default=>0}; }
}
?>
