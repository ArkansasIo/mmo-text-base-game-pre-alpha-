<?php
require_once __DIR__.'/../base/WormholePolicy.class.php';$module=file_get_contents(__DIR__.'/../modules/wormholes.php');$worker=file_get_contents(__DIR__.'/../scripts/backend/wormhole_tick.php');$migration=file_get_contents(__DIR__.'/../database/sql/40_wormhole_exploration.sql');$stabilityMigration=file_get_contents(__DIR__.'/../database/sql/41_wormhole_stability_degradation.sql');$cron=file_get_contents(__DIR__.'/../scripts/backend/cron_runner.sh');$nav=file_get_contents(__DIR__.'/../templates/index.tpl');$gameTick=file_get_contents(__DIR__.'/../scripts/backend/game_tick.php');$passed=0;$failed=0;function wh_check(bool $ok,string $name):void{global $passed,$failed;if($ok){$passed++;echo "PASS: $name\n";}else{$failed++;echo "FAIL: $name\n";}}
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
wh_check(WormholePolicy::degradedStability(80,'quantum',10)<WormholePolicy::degradedStability(80,'stable',10),'dangerous wormhole classes degrade stability faster');
wh_check(WormholePolicy::collapseRisk(80,40,'quantum',60)>WormholePolicy::collapseRisk(80,40,'quantum',1),'prolonged exploration increases collapse risk');
wh_check(WormholePolicy::exoticTier(80,80)>WormholePolicy::exoticTier(25,10),'high difficulty and risk produce higher exotic tiers');
wh_check(strpos($module,'stabilityAtDispatch')!==false&&strpos($module,'collapseRisk')!==false&&strpos($module,'exoticTier')!==false,'dispatch persists stability, risk, and reward tier');
wh_check(strpos($worker,'WormholePolicy::collapseRisk')!==false&&strpos($worker,'WormholePolicy::collapse')!==false,'worker applies degradation and collapse settlement');
wh_check(strpos($worker,'exoticReward')!==false&&strpos($worker,'charted_corridor_tier_')!==false,'worker scales exotic rewards by tier');
wh_check(strpos($stabilityMigration,'stability_at_dispatch')!==false&&strpos($stabilityMigration,'collapse_risk')!==false&&strpos($stabilityMigration,'exotic_tier')!==false,'stability migration persists risk and tier fields');
if($failed){fwrite(STDERR,"$failed wormhole checks failed; $passed passed.\n");exit(1);}echo "All $passed wormhole checks passed.\n";
?>
