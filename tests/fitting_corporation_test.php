<?php
require_once __DIR__ . '/../base/FleetPolicy.class.php';
require_once __DIR__ . '/../base/CorporationPolicy.class.php';
$passed=0;$failed=0;
function fc_check(bool $value,string $name):void{global $passed,$failed;if($value){$passed++;echo "PASS: $name\n";}else{$failed++;echo "FAIL: $name\n";}}
$sim=file_get_contents(__DIR__.'/../modules/fitting_simulator.php');$corp=file_get_contents(__DIR__.'/../modules/corporation.php');$migration=file_get_contents(__DIR__.'/../database/sql/37_corporations_cooperative_ops.sql');$nav=file_get_contents(__DIR__.'/../templates/index.tpl');
fc_check(count(FleetPolicy::BLUEPRINTS)===90,'fitting simulator receives all 90 blueprints');
fc_check(strpos($sim,'fit-hull')!==false&&strpos($sim,'fit-module')!==false&&strpos($sim,'fit-summary')!==false,'simulator exposes hull, module, and live summary controls');
fc_check(strpos($sim,'fit-invalid')!==false&&strpos($sim,'power_grid')!==false&&strpos($sim,'capacitor')!==false,'simulator validates slots and fitting resources client-side');
fc_check(CorporationPolicy::can('director','manage')&&CorporationPolicy::can('operator','operate')&&!CorporationPolicy::can('member','operate'),'corporation roles enforce cooperative permissions');
fc_check(CorporationPolicy::MAX_MEMBERS===150&&CorporationPolicy::clampContribution(0)===1&&CorporationPolicy::clampContribution(2000000000)===1000000000,'corporation membership and contribution bounds');
fc_check(CorporationPolicy::validMission('joint_defense')&&CorporationPolicy::validMission('coordinated_strike')&&!CorporationPolicy::validMission('solo_attack'),'cooperative mission allowlist');
fc_check(count(CorporationPolicy::researchKeys())===4&&CorporationPolicy::researchCost('fleet_doctrine',2)>CorporationPolicy::researchCost('fleet_doctrine',1),'shared research catalog and scaling');
fc_check(strpos($migration,'corporation_members')!==false&&strpos($migration,'corporation_research')!==false&&strpos($migration,'corporation_operations')!==false,'corporation migration defines members research and operations');
fc_check(strpos($corp,'shared_research_pool')!==false&&strpos($corp,'corporation_contributions')!==false&&strpos($corp,'begin_transaction')!==false,'corporation uses shared pools and transactional contributions');
fc_check(strpos($corp,'FleetPolicy::validateFitting')!==false&&strpos($corp,'corporation_operation_members')!==false&&strpos($corp,'launch_operation')!==false,'cooperative operations validate fittings and support member fleets');
fc_check(strpos($corp,'NotificationPolicy::push')!==false&&strpos($corp,'corporation_operation')!==false,'cooperative launch notifications are emitted');
fc_check(strpos($nav,"sendData('fitting_simulator','get','mainDisplay')")!==false&&strpos($nav,"sendData('corporation','get','mainDisplay')")!==false,'both systems are reachable from warfare navigation');
if($failed){fwrite(STDERR,"$failed fitting/corporation checks failed; $passed passed.\n");exit(1);}echo "All $passed fitting/corporation checks passed.\n";
?>
