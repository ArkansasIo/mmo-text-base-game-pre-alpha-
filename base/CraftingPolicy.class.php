<?php
final class CraftingPolicy
{
    public const MAX_LEVEL=10;
    public const RECIPES=[
      'reinforced_hull'=>['name'=>'Reinforced Hull','metal'=>2500,'crystal'=>1000,'energy'=>500,'attack'=>0,'defense'=>4,'capacity'=>0],
      'targeting_array'=>['name'=>'Targeting Array','metal'=>1800,'crystal'=>2200,'energy'=>700,'attack'=>5,'defense'=>0,'capacity'=>0],
      'shield_matrix'=>['name'=>'Shield Matrix','metal'=>1200,'crystal'=>2500,'energy'=>1200,'attack'=>0,'defense'=>7,'capacity'=>0],
      'cargo_frame'=>['name'=>'Cargo Frame','metal'=>3000,'crystal'=>1200,'energy'=>800,'attack'=>0,'defense'=>0,'capacity'=>50],
    ];
    public static function recipe(string $key):?array{return self::RECIPES[$key]??null;}
    public static function cost(string $key,int $level):array{$r=self::recipe($key);$level=max(1,min(self::MAX_LEVEL,$level));$scale=pow(1.65,$level-1);return ['metal'=>(int)ceil($r['metal']*$scale),'crystal'=>(int)ceil($r['crystal']*$scale),'energy'=>(int)ceil($r['energy']*$scale)];}
    public static function modifiers(array $equipment):array{$out=['attack'=>0,'defense'=>0,'capacity'=>0];foreach($equipment as $key=>$level){$r=self::recipe((string)$key);$l=max(0,min(self::MAX_LEVEL,(int)$level));if($r){$out['attack']+=$r['attack']*$l;$out['defense']+=$r['defense']*$l;$out['capacity']+=$r['capacity']*$l;}}return $out;}
}
?>
