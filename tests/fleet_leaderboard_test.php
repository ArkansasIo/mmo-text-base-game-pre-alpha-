<?php
require_once __DIR__.'/../base/FleetPolicy.class.php';
require_once __DIR__.'/../base/LeaderboardPolicy.class.php';
$p = 0; $f = 0;
function checkv(bool $v, string $n): void { global $p, $f; if ($v) { $p++; echo "PASS: $n\n"; } else { $f++; echo "FAIL: $n\n"; } }
checkv(FleetPolicy::valid('scout') && FleetPolicy::valid('carrier') && !FleetPolicy::valid('unknown'), 'blueprint whitelist');
$one = FleetPolicy::cost('frigate', 1); $cost = FleetPolicy::cost('frigate', 2);
checkv($cost['metal'] === $one['metal'] * 2 && $cost['crystal'] === $one['crystal'] * 2 && $cost['energy'] === $one['energy'] * 2, 'blueprint costs scale');
$scout = FleetPolicy::blueprint('scout'); $frigate = FleetPolicy::blueprint('frigate');
$power = FleetPolicy::fleetPower(['scout'=>10,'frigate'=>2]);
checkv($power['attack'] === ($scout['attack'] * 10) + ($frigate['attack'] * 2) && $power['defense'] === ($scout['defense'] * 10) + ($frigate['defense'] * 2) && $power['capacity'] === ($scout['capacity'] * 10) + ($frigate['capacity'] * 2), 'fleet power aggregation');
checkv($power['capacity'] <= FleetPolicy::MAX_DEPLOYMENT, 'deployment capacity accepted');
$ranked = LeaderboardPolicy::rank([10=>500,20=>500,30=>100]);
checkv($ranked[10]['rank']===1 && $ranked[20]['rank']===1 && $ranked[30]['rank']===3, 'leaderboard ties share rank');
checkv(LeaderboardPolicy::unlocked(100,100) && !LeaderboardPolicy::unlocked(99,100), 'achievement threshold');
if ($f) { fwrite(STDERR, "$f failed; $p passed.\n"); exit(1); }
echo "All $p fleet and leaderboard tests passed.\n";
?>
