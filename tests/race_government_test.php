<?php
require_once __DIR__.'/../base/RaceGovernmentPolicy.class.php';
$passed=0;$failed=0;function rg_check(bool $v,string $n):void{global $passed,$failed;if($v){$passed++;echo "PASS: $n\n";}else{$failed++;echo "FAIL: $n\n";}}
rg_check(count(RaceGovernmentPolicy::RACES)===5,'five renamed player races are defined');
rg_check(RaceGovernmentPolicy::RACES[1]['name']==='Astraeans','first race uses new name');
rg_check(RaceGovernmentPolicy::RACES[3]['name']==='Terran Union','third race uses new name');
rg_check(count(RaceGovernmentPolicy::GOVERNMENTS)===9,'nine government choices are defined');
for($i=1;$i<=9;$i++)rg_check(RaceGovernmentPolicy::validGovernment($i),"government $i is valid");
rg_check(!RaceGovernmentPolicy::validGovernment(0)&&!RaceGovernmentPolicy::validGovernment(10),'government values outside 1-9 are rejected');
$bonus=RaceGovernmentPolicy::bonuses(4,6);rg_check($bonus['attack']>0&&$bonus['defense']>0,'race and government bonuses combine');
if($failed){fwrite(STDERR,"$failed race/government checks failed; $passed passed.\n");exit(1);}echo "All $passed race/government checks passed.\n";
?>
