<?php
require_once __DIR__ . '/../base/FleetPolicy.class.php';
$passed = 0; $failed = 0;
function blueprint_check(bool $value, string $name): void { global $passed, $failed; if ($value) { $passed++; echo "PASS: $name\n"; } else { $failed++; echo "FAIL: $name\n"; } }
$blueprints = FleetPolicy::BLUEPRINTS;
$template = file_get_contents(__DIR__ . '/../templates/index.tpl');
$required = ['name','class_code','class_name','role','tier','description','attack','defense','capacity','high_slots','medium_slots','low_slots','hull','shield','speed','cargo','sensor','power_grid','crew','armor','capacitor','signature','warp','evasion','drone_bandwidth','salvage','metal','crystal','energy','build_minutes'];
blueprint_check(count($blueprints) === 90, 'catalog contains exactly 90 blueprints');
blueprint_check(count(array_unique(array_keys($blueprints))) === 90, 'blueprint keys are unique');
$classes = array_values(array_unique(array_map(static fn(array $b): string => $b['class_code'], $blueprints)));
sort($classes);
blueprint_check($classes === range('A', 'Z'), 'catalog covers every A-Z class');
blueprint_check(isset($blueprints['scout'], $blueprints['frigate'], $blueprints['destroyer'], $blueprints['carrier']), 'legacy fleet keys remain compatible');
$complete = true; $positive = true;
foreach ($blueprints as $blueprint) {
    foreach ($required as $field) if (!array_key_exists($field, $blueprint)) $complete = false;
    foreach (['attack','defense','capacity','high_slots','medium_slots','low_slots','hull','shield','speed','cargo','sensor','power_grid','crew','armor','capacitor','evasion','drone_bandwidth','salvage','metal','crystal','energy','build_minutes'] as $field) if ((int)$blueprint[$field] <= 0) $positive = false;
}
blueprint_check($complete, 'every blueprint contains primary, secondary, fitting, and industrial fields');
blueprint_check($positive, 'all blueprint stats and costs are positive');
blueprint_check(strpos($template, "sendData('blueprints','get','mainDisplay')") !== false, 'blueprint catalog is reachable from warfare navigation');
$fleetModule=file_get_contents(__DIR__.'/../modules/fleet.php');$gameTick=file_get_contents(__DIR__.'/../scripts/backend/game_tick.php');
blueprint_check(strpos($fleetModule,'SELECT pid FROM planets WHERE pid=$planet AND uid=$uid')!==false&&strpos($fleetModule,'SELECT pid FROM planets WHERE pid=$origin AND uid=$uid')!==false,'shipyard and deployment require owned planets');
blueprint_check(strpos($fleetModule,'player_fleet_inventory SET quantity=quantity-$quantity')!==false&&strpos($fleetModule,'begin_transaction()')!==false,'deployment reserves source fleet inventory transactionally');
blueprint_check(strpos($gameTick,'destination_planet_id')!==false&&strpos($gameTick,'ON DUPLICATE KEY UPDATE quantity=quantity+$qty')!==false,'arrived deployments transfer ships into destination inventory');
if ($failed) { fwrite(STDERR, "$failed blueprint checks failed; $passed passed.\n"); exit(1); }
echo "All $passed blueprint checks passed.\n";
?>
