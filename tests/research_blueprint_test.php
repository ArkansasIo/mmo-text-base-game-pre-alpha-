<?php
require_once __DIR__ . '/../base/ResearchBlueprintPolicy.class.php';
$passed=0;$failed=0;
function research_check(bool $value,string $name):void{global $passed,$failed;if($value){$passed++;echo "PASS: $name\n";}else{$failed++;echo "FAIL: $name\n";}}
$scout=FleetPolicy::blueprint('scout');$zenith=FleetPolicy::blueprint('z_scout_26');$reqLow=ResearchBlueprintPolicy::requirements($scout);$reqHigh=ResearchBlueprintPolicy::requirements($zenith);
research_check($reqLow['research_campus']===0,'starter-tier blueprint has no campus prerequisite');
research_check($reqHigh['research_campus']>$reqLow['research_campus']&&$reqHigh['simulation_core']>=$reqLow['simulation_core'],'higher-tier blueprint requires deeper research');
$levels=['research_campus'=>0,'simulation_core'=>0,'data_vault'=>0];research_check(ResearchBlueprintPolicy::meetsResearch($levels,$scout)&&!ResearchBlueprintPolicy::meetsResearch($levels,$zenith),'research prerequisites gate higher tiers');
$cost=ResearchBlueprintPolicy::unlockCost($zenith);research_check($cost['naquadah']>0&&$cost['metal']>0&&$cost['crystal']>0&&$cost['deuterium']>0&&$cost['energy']>0,'blueprint discovery costs include all research resources');
$research=file_get_contents(__DIR__.'/../modules/blueprint_research.php');$fleet=file_get_contents(__DIR__.'/../modules/fleet.php');$catalog=file_get_contents(__DIR__.'/../modules/blueprints.php');$market=file_get_contents(__DIR__.'/../modules/player_market.php');$migration=file_get_contents(__DIR__.'/../database/sql/35_research_blueprint_unlocks.sql');$notificationPolicy=file_get_contents(__DIR__.'/../base/NotificationPolicy.class.php');$notificationModule=file_get_contents(__DIR__.'/../modules/notifications.php');$notificationMigration=file_get_contents(__DIR__.'/../database/sql/36_player_notifications.sql');$nav=file_get_contents(__DIR__.'/../templates/index.tpl');
research_check(strpos($research,'ResearchBlueprintPolicy::unlock')!==false&&strpos($research,'Research Blueprint Matrix')!==false,'research console exposes blueprint unlock actions');
research_check(strpos($fleet,'ResearchBlueprintPolicy::unlocked')!==false,'shipyard construction requires researched blueprints');
research_check(strpos($catalog,'player_blueprint_research')!==false&&strpos($catalog,'Unlocked')!==false,'catalog shows research unlock status');
research_check(strpos($market,'player_blueprint_research')!==false&&strpos($market,"'market','unlocked'")!==false,'market-acquired blueprints enter research access state');
research_check(strpos($migration,'player_blueprint_research')!==false&&strpos($migration,"source, status")!==false,'research unlock migration preserves legacy blueprint access');
research_check(strpos($nav,"sendData('blueprint_research','get','mainDisplay')")!==false,'research console is reachable from navigation');
research_check(strpos($notificationPolicy,'INSERT IGNORE INTO player_notifications')!==false&&strpos($notificationPolicy,'uq_notification_dedupe')!==false,'notification policy deduplicates completion alerts');
$researchPolicy=file_get_contents(__DIR__.'/../base/ResearchBlueprintPolicy.class.php');
research_check(strpos($researchPolicy,"'research_complete'")!==false&&strpos($researchPolicy,'NotificationPolicy::push')!==false,'research unlock flow emits completion notification category');
research_check(strpos($notificationModule,'player_notifications')!==false&&strpos($notificationModule,'player_read_all')!==false,'notification inbox displays and marks player alerts');
research_check(strpos($notificationMigration,'player_notifications')!==false&&strpos($notificationMigration,'uq_notification_dedupe')!==false,'notification migration creates feed and deduplication index');
research_check(strpos($nav,"sendData('notifications','get','mainDisplay')")!==false,'alert network is reachable from navigation');
if($failed){fwrite(STDERR,"$failed research checks failed; $passed passed.\n");exit(1);}echo "All $passed research-blueprint checks passed.\n";
?>
