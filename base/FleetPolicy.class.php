<?php
require_once __DIR__ . '/FleetBlueprintCatalog.class.php';
require_once __DIR__ . '/ModuleFittingPolicy.class.php';
final class FleetPolicy
{
    public const MAX_QUEUE = 8;
    public const MAX_DEPLOYMENT = 1000000;
    public const BLUEPRINTS = FleetBlueprintCatalog::BLUEPRINTS;
    public static function blueprint(string $key): ?array { return self::BLUEPRINTS[$key]??null; }
    public static function valid(string $key): bool { return isset(self::BLUEPRINTS[$key]); }
    public static function cost(string $key,int $quantity): array { $b=self::blueprint($key);$quantity=max(1,min(100000,$quantity));return ['metal'=>$b['metal']*$quantity,'crystal'=>$b['crystal']*$quantity,'energy'=>$b['energy']*$quantity,'build_minutes'=>$b['build_minutes']*$quantity]; }
    public static function fleetPower(array $fleet,array $equipment=[]): array { $a=0;$d=0;$c=0;foreach($fleet as $key=>$qty){$b=self::blueprint($key);$q=max(0,(int)$qty);if($b){$a+=$b['attack']*$q;$d+=$b['defense']*$q;$c+=$b['capacity']*$q;}}if($equipment){require_once __DIR__.'/CraftingPolicy.class.php';$m=CraftingPolicy::modifiers($equipment);$a+=(int)$m['attack'];$d+=(int)$m['defense'];$c+=(int)$m['capacity'];}return ['attack'=>$a,'defense'=>$d,'capacity'=>$c];}
    public static function fittedPower(array $fleet, array $fitting = [], array $equipment = []): array { $base = self::fleetPower($fleet, $equipment); $summary = ModuleFittingPolicy::summarize($fitting); return ['attack'=>$base['attack'] + $summary['attack'],'defense'=>$base['defense'] + $summary['defense'],'capacity'=>$base['capacity'] + $summary['capacity']]; }
    public static function validateFitting(array $fleet, array $fitting): array { $aggregate = ['valid'=>true,'errors'=>[],'summary'=>['high'=>0,'medium'=>0,'low'=>0,'power_grid'=>0,'cpu'=>0,'capacitor'=>0,'attack'=>0,'defense'=>0,'capacity'=>0],'slots'=>['high'=>0,'medium'=>0,'low'=>0],'limits'=>['power_grid'=>0,'cpu'=>0,'capacitor'=>0]]; foreach ($fleet as $key => $quantity) { $blueprint = self::blueprint((string)$key); $quantity = max(0, (int)$quantity); if (!$blueprint || $quantity === 0) continue; $fit = ModuleFittingPolicy::fit($blueprint, $fitting); foreach (['high','medium','low','power_grid','cpu','capacitor','attack','defense','capacity'] as $field) $aggregate['summary'][$field] = max($aggregate['summary'][$field], $fit['summary'][$field]); foreach (['high','medium','low','power_grid','cpu','capacitor'] as $field) $aggregate['limits'][$field] = max($aggregate['limits'][$field], $fit['limits'][$field]); if (!$fit['valid']) { $aggregate['valid'] = false; $aggregate['errors'] = array_merge($aggregate['errors'], [$key.': '.implode(' ', $fit['errors'])]); } } return $aggregate; }
}
?>
