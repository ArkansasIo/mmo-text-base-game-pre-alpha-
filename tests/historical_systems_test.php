<?php
require_once __DIR__.'/../base/HistoricalSystemsPolicy.class.php';
$passed=0;$failed=0;function historical_check(bool $ok,string $name):void{global $passed,$failed;if($ok){$passed++;echo "PASS: $name\n";}else{$failed++;echo "FAIL: $name\n";}}
$policy=HistoricalSystemsPolicy::class;
historical_check($policy::race('goauld')['income']===1.25,'Goauld income doctrine is preserved');
historical_check($policy::race('asgard')['defense']===1.25,'Asgard defense doctrine is preserved');
historical_check($policy::race('replicator')['covert']===1.25,'Replicator covert doctrine is preserved');
historical_check($policy::race('tauri')['attack']===1.25,'Tauri attack doctrine is preserved');
historical_check($policy::naturalIncome(100,10,1,'goauld','none')===3600,'natural income formula applies UU, miners, lifers, and race bonus');
historical_check($policy::naturalIncome(100,10,1,'tauri','high')===1728,'DefCon reduces income while preserving the server formula');
historical_check($policy::bankCapacity(1000)===72000,'bank capacity formula is deterministic');
historical_check($policy::unitProductionUpgradeCost(5)===35000,'Unit Production cost formula is deterministic');
historical_check($policy::strikePower(100,10,1,'tauri')===750,'strike weights normal and super weapons with race bonus');
historical_check($policy::defensePower(100,10,1,'asgard')===750,'defense weights normal and super weapons with race bonus');
historical_check($policy::covertPower(10,1,1,'replicator')>0&&$policy::antiCovertPower(10,1)>0,'covert and anti-covert formulas produce positive power');
historical_check($policy::overallRank([10,20,30,40])===25.0,'overall rank averages four rank dimensions');
historical_check(!$policy::hostileActionAllowed(['vacation_until'=>date('Y-m-d H:i:s',time()+3600)]),'vacation blocks hostile actions');
historical_check(!$policy::hostileActionAllowed(['ppt_until'=>date('Y-m-d H:i:s',time()+3600)]),'PPT blocks hostile actions');
historical_check($policy::turnAward(100,1,3)===3&&$policy::turnAward(4000,1,3)===0,'turn floor and cap policy are enforced');
historical_check($policy::ascendedRace('asgard')==='Ancient','ascended race mapping is available');
$module=file_get_contents(__DIR__.'/../modules/strategy_codex.php');$template=file_get_contents(__DIR__.'/../templates/index.tpl');$migration=file_get_contents(__DIR__.'/../database/sql/48_historical_strategy_state.sql');$runner=file_get_contents(__DIR__.'/../scripts/backend/db_migrate.sh');
historical_check(strpos($module,'Systems Codex')!==false&&strpos($module,'NaturalIncome')!==false,'Strategy Codex exposes condensed features and formulas');
historical_check(strpos($template,"strategy_codex")!==false,'Strategy Codex is reachable from authenticated navigation');
historical_check(strpos($migration,'player_historical_state')!==false&&strpos($migration,'historical_strategy_events')!==false,'historical state and event tables are migrated');
historical_check(strpos($runner,'48_historical_strategy_state.sql')!==false,'historical migration is registered in the locked runner');
if($failed){fwrite(STDERR,"$failed historical-system checks failed; $passed passed.\n");exit(1);}echo "All $passed historical-system checks passed.\n";
?>
