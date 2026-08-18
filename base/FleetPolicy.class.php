<?php
require_once __DIR__ . '/FleetBlueprintCatalog.class.php';
final class FleetPolicy
{
    public const MAX_QUEUE = 8;
    public const MAX_DEPLOYMENT = 1000000;
    public const BLUEPRINTS = FleetBlueprintCatalog::BLUEPRINTS;
    public static function blueprint(string $key): ?array { return self::BLUEPRINTS[$key]??null; }
    public static function valid(string $key): bool { return isset(self::BLUEPRINTS[$key]); }
    public static function cost(string $key,int $quantity): array { $b=self::blueprint($key);$quantity=max(1,min(100000,$quantity));return ['metal'=>$b['metal']*$quantity,'crystal'=>$b['crystal']*$quantity,'energy'=>$b['energy']*$quantity,'build_minutes'=>$b['build_minutes']*$quantity]; }
    public static function fleetPower(array $fleet,array $equipment=[]): array { $a=0;$d=0;$c=0;foreach($fleet as $key=>$qty){$b=self::blueprint($key);$q=max(0,(int)$qty);if($b){$a+=$b['attack']*$q;$d+=$b['defense']*$q;$c+=$b['capacity']*$q;}}if($equipment){require_once __DIR__.'/CraftingPolicy.class.php';$m=CraftingPolicy::modifiers($equipment);$a+=(int)$m['attack'];$d+=(int)$m['defense'];$c+=(int)$m['capacity'];}return ['attack'=>$a,'defense'=>$d,'capacity'=>$c];}
}
?>
