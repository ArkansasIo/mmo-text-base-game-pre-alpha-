<?php
final class FleetPolicy
{
    public const MAX_QUEUE = 8;
    public const MAX_DEPLOYMENT = 1000000;
    public const BLUEPRINTS = [
        'scout' => ['name'=>'Scout Corvette','metal'=>1200,'crystal'=>600,'energy'=>100,'build_minutes'=>5,'attack'=>8,'defense'=>4,'capacity'=>25],
        'frigate' => ['name'=>'Frontier Frigate','metal'=>5000,'crystal'=>2500,'energy'=>800,'build_minutes'=>15,'attack'=>35,'defense'=>30,'capacity'=>100],
        'destroyer' => ['name'=>'Siege Destroyer','metal'=>18000,'crystal'=>9000,'energy'=>3000,'build_minutes'=>45,'attack'=>120,'defense'=>90,'capacity'=>250],
        'carrier' => ['name'=>'Fleet Carrier','metal'=>50000,'crystal'=>30000,'energy'=>12000,'build_minutes'=>120,'attack'=>250,'defense'=>260,'capacity'=>800],
    ];
    public static function blueprint(string $key): ?array { return self::BLUEPRINTS[$key]??null; }
    public static function valid(string $key): bool { return isset(self::BLUEPRINTS[$key]); }
    public static function cost(string $key,int $quantity): array { $b=self::blueprint($key);$quantity=max(1,min(100000,$quantity));return ['metal'=>$b['metal']*$quantity,'crystal'=>$b['crystal']*$quantity,'energy'=>$b['energy']*$quantity,'build_minutes'=>$b['build_minutes']*$quantity]; }
    public static function fleetPower(array $fleet): array { $a=0;$d=0;$c=0;foreach($fleet as $key=>$qty){$b=self::blueprint($key);$q=max(0,(int)$qty);if($b){$a+=$b['attack']*$q;$d+=$b['defense']*$q;$c+=$b['capacity']*$q;}}return ['attack'=>$a,'defense'=>$d,'capacity'=>$c]; }
}
?>
