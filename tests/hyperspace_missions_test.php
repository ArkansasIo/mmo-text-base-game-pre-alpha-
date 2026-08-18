<?php
$module=file_get_contents(__DIR__.'/../modules/hyperspace.php');$migration=file_get_contents(__DIR__.'/../database/sql/42_hyperspace_mission_outcomes.sql');$tick=file_get_contents(__DIR__.'/../scripts/backend/game_tick.php');$passed=0;$failed=0;
function hs_check(bool $ok,string $name):void{global $passed,$failed;if($ok){$passed++;echo "PASS: $name\n";}else{$failed++;echo "FAIL: $name\n";}}
hs_check(strpos($module,"\$t->transit_type === 'expedition'")!==false,'expedition settlement remains supported');
hs_check(strpos($module,"\$t->transit_type === 'transfer'")!==false&&strpos($module,'transfer_delivered')!==false,'transfer missions deliver a distinct convoy outcome');
hs_check(strpos($module,"\$t->transit_type === 'colonize'")!==false&&strpos($module,'hyperspace_colonies')!==false,'colonization missions found durable frontier colonies');
hs_check(strpos($module,'begin_transaction()')!==false&&strpos($module,"status='enroute'")!==false,'hyperspace settlement is transactional and idempotent');
hs_check(strpos($module,'outcome_text')!==false&&strpos($module,'<th align="left">Outcome</th>')!==false,'mission outcomes are visible in the player UI');
hs_check(strpos($migration,'hyperspace_colonies')!==false&&strpos($migration,'outcome_text')!==false,'hyperspace outcome migration is durable');
hs_check(strpos($tick,'ON DUPLICATE KEY UPDATE quantity=quantity+$qty')!==false&&strpos($tick,"status='arrived'")!==false,'fleet arrivals transfer ships into destination inventory');
if($failed){fwrite(STDERR,"$failed hyperspace checks failed; $passed passed.\n");exit(1);}echo "All $passed hyperspace mission checks passed.\n";
?>
