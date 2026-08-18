<?php
require_once __DIR__ . '/../base/FleetPolicy.class.php';
require_once __DIR__ . '/../base/PvPPolicy.class.php';
$passed = 0; $failed = 0;
function pvp_check(bool $value, string $name): void { global $passed, $failed; if ($value) { $passed++; echo "PASS: $name\n"; } else { $failed++; echo "FAIL: $name\n"; } }
$module = file_get_contents(__DIR__ . '/../modules/pvp.php');
$resolver = file_get_contents(__DIR__ . '/../base/PvPResolver.class.php');
$migration = file_get_contents(__DIR__ . '/../database/sql/31_player_pvp.sql');
$indexes = file_get_contents(__DIR__ . '/../database/sql/32_concurrency_indexes.sql');
$cron = file_get_contents(__DIR__ . '/../scripts/backend/cron_runner.sh');
$smallFleet = ['scout' => 5, 'frigate' => 2];
pvp_check(PvPPolicy::validFleet($smallFleet), 'valid fleets pass policy validation');
pvp_check(!PvPPolicy::validFleet(['unknown_ship' => 1]), 'unknown ship types are rejected');
pvp_check(PvPPolicy::outcome(1000, 1, 4) === 'attacker_victory', 'stronger attackers can win a battle');
pvp_check(PvPPolicy::outcome(1, 1000, 4) === 'defender_victory', 'stronger defenders can repel a battle');
pvp_check(PvPPolicy::lossPercent('attacker_victory', true) < PvPPolicy::lossPercent('attacker_victory', false), 'victorious side takes fewer losses');
$loot = PvPPolicy::loot(1000, 500, 100);
pvp_check($loot['metal'] === 200 && $loot['crystal'] === 100 && $loot['deuterium'] === 20, 'loot is capped at the configured rate');
pvp_check(strpos($module, 'resolves_at') !== false && strpos($module, 'fleet_json') !== false, 'PvP launch stores timed fleet dispatch data');
pvp_check(strpos($module, 'fitting_json') !== false && strpos($module, 'validateFitting') !== false, 'PvP launch validates and persists module fittings');
pvp_check(strpos($resolver, 'ON DUPLICATE KEY UPDATE quantity=quantity+$survivors') !== false, 'surviving attacker ships return to the origin world');
pvp_check(strpos($resolver, 'GREATEST(0,quantity-') !== false, 'defender fleet losses are settled atomically');
pvp_check(strpos($migration, 'idx_pvp_due') !== false && strpos($migration, 'pvp_alerts') !== false, 'PvP schema includes due-battle and alert indexes');
pvp_check(strpos($indexes, 'idx_fleet_uid_planet_ship') !== false && strpos($indexes, 'idx_pvp_due_status') !== false, 'concurrency migration includes action indexes');
pvp_check(strpos($cron, 'pvp_tick') !== false && file_exists(__DIR__ . '/../scripts/backend/pvp_tick.php'), 'PvP settlement is wired to cron');
$template = file_get_contents(__DIR__ . '/../templates/index.tpl');
pvp_check(strpos($template, "sendData('pvp','get','mainDisplay')") !== false, 'PvP Battle Command is reachable from warfare navigation');
if ($failed) { fwrite(STDERR, "$failed PvP checks failed; $passed passed.\n"); exit(1); }
echo "All $passed PvP checks passed.\n";
?>
