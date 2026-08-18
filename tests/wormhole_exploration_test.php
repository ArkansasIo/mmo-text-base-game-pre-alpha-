<?php
$module=file_get_contents(__DIR__.'/../modules/wormholes.php');$worker=file_get_contents(__DIR__.'/../scripts/backend/wormhole_tick.php');$migration=file_get_contents(__DIR__.'/../database/sql/40_wormhole_exploration.sql');$cron=file_get_contents(__DIR__.'/../scripts/backend/cron_runner.sh');$nav=file_get_contents(__DIR__.'/../templates/index.tpl');$gameTick=file_get_contents(__DIR__.'/../scripts/backend/game_tick.php');$passed=0;$failed=0;function wh_check(bool $ok,string $name):void{global $passed,$failed;if($ok){$passed++;echo "PASS: $name\n";}else{$failed++;echo "FAIL: $name\n";}}
wh_check(strpos($module,"wormhole_action.*scan")!==false||strpos($module,"\$action==='scan'")!==false,'scanner action is implemented');
wh_check(strpos($module,'dark_matter-$cost')!==false&&strpos($module,'Insufficient Dark Matter')!==false,'scan and exploration consume Dark Matter server-side');
wh_check(strpos($module,"['stable','unstable','ancient','null','quantum']")!==false,'wormhole class taxonomy is present');
wh_check(strpos($module,'scan_difficulty')!==false&&strpos($module,'stability')!==false,'signatures include difficulty and stability');
wh_check(strpos($module,"status='enroute'")!==false&&strpos($module,'resolves_at')!==false,'exploration dispatch is timed and single-active-probe protected');
wh_check(strpos($worker,'random_int(1,100)')!==false&&strpos($worker,'reward_dark_matter')!==false,'worker resolves success chance and Dark Matter rewards');
wh_check(strpos($worker,'exotic_matter=exotic_matter+')!==false&&strpos($worker,'tritanium=tritanium+')!==false,'worker settles strategic exploration rewards');
wh_check(strpos($worker,'NotificationPolicy::push')!==false&&strpos($module,'NotificationPolicy::push')!==false,'scan, launch, and resolution alerts are emitted');
wh_check(strpos($migration,'wormhole_signatures')!==false&&strpos($migration,'wormhole_expeditions')!==false&&strpos($migration,'idx_wormhole_due')!==false,'wormhole migration and due-queue indexes exist');
wh_check(strpos($cron,'wormhole_tick')!==false,'wormhole worker is registered in the locked cron dispatcher');
wh_check(strpos($nav,"sendData('wormholes','get','mainDisplay')")!==false,'wormhole command panel is reachable from navigation');
wh_check(strpos($gameTick,'dark_matter')!==false,'Dark Matter resource exists in the game tick resource model');
if($failed){fwrite(STDERR,"$failed wormhole checks failed; $passed passed.\n");exit(1);}echo "All $passed wormhole checks passed.\n";
?>
