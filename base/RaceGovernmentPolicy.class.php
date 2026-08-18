<?php
final class RaceGovernmentPolicy
{
    public const RACES=[1=>['name'=>'Astraeans','income'=>8,'upkeep'=>4,'attack'=>3,'defense'=>2],2=>['name'=>'Noxari','income'=>4,'upkeep'=>2,'attack'=>1,'defense'=>6],3=>['name'=>'Terran Union','income'=>6,'upkeep'=>5,'attack'=>5,'defense'=>4],4=>['name'=>'Asgardian Remnant','income'=>3,'upkeep'=>3,'attack'=>7,'defense'=>7],5=>['name'=>'Tokari Syndicate','income'=>7,'upkeep'=>1,'attack'=>4,'defense'=>3]];
    public const GOVERNMENTS=[1=>'Parliamentary Republic',2=>'Imperial Directorate',3=>'Federated Commonwealth',4=>'Technocratic Compact',5=>'Merchant League',6=>'Militarized Protectorate',7=>'Theocratic Dominion',8=>'Hive Council',9=>'Frontier Confederacy'];
    public static function race(int $id):array{return self::RACES[$id]??self::RACES[1];}
    public static function government(int $id):string{return self::GOVERNMENTS[$id]??self::GOVERNMENTS[1];}
    public static function validRace(int $id):bool{return isset(self::RACES[$id]);}
    public static function validGovernment(int $id):bool{return isset(self::GOVERNMENTS[$id]);}
    public static function bonuses(int $race,int $government):array{$r=self::race($race);$gBonus=[1=>['income'=>4,'attack'=>1,'defense'=>2],2=>['income'=>1,'attack'=>5,'defense'=>0],3=>['income'=>3,'attack'=>2,'defense'=>2],4=>['income'=>2,'attack'=>2,'defense'=>5],5=>['income'=>6,'attack'=>0,'defense'=>1],6=>['income'=>0,'attack'=>6,'defense'=>1],7=>['income'=>2,'attack'=>3,'defense'=>4],8=>['income'=>3,'attack'=>4,'defense'=>3],9=>['income'=>4,'attack'=>2,'defense'=>3]][$government]??['income'=>0,'attack'=>0,'defense'=>0];return ['income'=>$r['income']+$gBonus['income'],'attack'=>$r['attack']+$gBonus['attack'],'defense'=>$r['defense']+$gBonus['defense'],'upkeep'=>$r['upkeep']];}
}
?>
