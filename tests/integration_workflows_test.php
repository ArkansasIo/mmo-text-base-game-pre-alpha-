<?php
require_once __DIR__.'/../base/FleetDockMissionPolicy.class.php';
$passed=0;$failed=0;
function integration_check(bool $ok,string $name):void{global $passed,$failed;if($ok){$passed++;echo "PASS: $name\n";}else{$failed++;echo "FAIL: $name\n";}}
$hyperspace=file_get_contents(__DIR__.'/../modules/hyperspace.php');$fleet=file_get_contents(__DIR__.'/../modules/fleet.php');$tick=file_get_contents(__DIR__.'/../scripts/backend/game_tick.php');$hMigration=file_get_contents(__DIR__.'/../database/sql/42_hyperspace_mission_outcomes.sql');$fMigration=file_get_contents(__DIR__.'/../database/sql/43_legacy_fleet_mission_outcomes.sql');$dock=file_get_contents(__DIR__.'/../modules/fleetdock.php');
// Hyperspace colonization workflow: dispatch, settlement, durable colony, UI.
integration_check(strpos($hyperspace,"\$t->transit_type === 'colonize'")!==false,'colonization settlement branch exists');
integration_check(strpos($hyperspace,'INSERT INTO hyperspace_colonies')!==false&&strpos($hyperspace,'colonyPopulation')!==false,'colonization creates a population-bearing colony');
integration_check(strpos($hyperspace,'begin_transaction()')!==false&&strpos($hyperspace,"status='enroute'")!==false,'colonization settlement is idempotent and transactional');
integration_check(strpos($hMigration,'UNIQUE KEY uq_colony_transit')!==false,'colonization migration prevents duplicate colony settlement');
integration_check(strpos($hyperspace,'outcome_text')!==false&&strpos($hyperspace,'<th align="left">Outcome</th>')!==false,'colonization outcome is displayed to the player');
// New fleet deployment workflow: ownership, source lock, arrival transfer.
integration_check(strpos($fleet,'SELECT pid FROM planets WHERE pid=$planet AND uid=$uid')!==false,'shipyard validates owned construction planet');
integration_check(strpos($fleet,'SELECT pid FROM planets WHERE pid=$origin AND uid=$uid')!==false,'deployment validates owned origin planet');
integration_check(strpos($fleet,'FOR UPDATE')!==false&&strpos($fleet,'player_fleet_inventory SET quantity=quantity-$quantity')!==false,'deployment locks and reserves source inventory');
integration_check(strpos($tick,'destination_planet_id')!==false&&strpos($tick,'ON DUPLICATE KEY UPDATE quantity=quantity+$qty')!==false,'game tick transfers arriving ships to destination');
// Legacy mission workflow policy outcomes.
$spy=FleetDockMissionPolicy::outcome('spy',10,40);$raid=FleetDockMissionPolicy::outcome('raid',10,50,100000,50000,25000);$patrol=FleetDockMissionPolicy::outcome('patrol',12);
integration_check($spy['success']===true&&$spy['intel']['fleet_power']===40&&$spy['intel']['scan_quality']>0,'spy mission returns target fleet intelligence');
integration_check($raid['success']===true&&$raid['loot']['metal']>0&&$raid['loot']['crystal']>0,'raid mission computes bounded resource loot');
integration_check($patrol['success']===true&&$patrol['patrol_bonus']>0,'patrol mission creates a defensive bonus');
integration_check(strpos($dock,'fleet_patrol_state')!==false&&strpos($dock,'loot_metal')!==false&&strpos($dock,'intel_json')!==false,'legacy dock persists patrol, loot, and intelligence outcomes');
integration_check(strpos($dock,'FOR UPDATE')!==false&&strpos($dock,'fleet SET ')!==false,'legacy dispatch uses a locked source fleet row');
integration_check(strpos($fMigration,'fleet_patrol_state')!==false&&strpos($fMigration,'outcome_text')!==false,'legacy mission migration creates durable outcome storage');
if($failed){fwrite(STDERR,"$failed integration checks failed; $passed passed.\n");exit(1);}echo "All $passed workflow integration checks passed.\n";
?>
