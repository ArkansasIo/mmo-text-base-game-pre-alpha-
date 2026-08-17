<?php
require_once __DIR__ . '/../base/PopulationModel.class.php';
require_once __DIR__ . '/../base/ArmyPolicy.class.php';
$passed = 0; $failed = 0;
function check_rule(bool $condition, string $name): void { global $passed, $failed; if ($condition) { $passed++; echo "PASS: $name\n"; } else { $failed++; echo "FAIL: $name\n"; } }
check_rule(PopulationModel::STARTING_UNTRAINED_UNITS === 2500000, 'starting untrained population is 2,500,000');
for ($i = 0; $i < 20; $i++) {
    $planet = PopulationModel::randomPlanet(5, 70);
    $moon = PopulationModel::randomMoon(3, 20);
    check_rule($planet >= PopulationModel::PLANET_MIN && $planet <= 10000000, 'random planet population remains bounded');
    check_rule($moon >= PopulationModel::MOON_MIN && $moon <= 2000000, 'random moon population remains bounded');
}
check_rule(ArmyPolicy::BASE_ARMY_SIZE === 250000, 'base trained army size is 250,000');
check_rule(ArmyPolicy::trainedTotal(['attack'=>100000,'defense'=>50000,'covert'=>25000,'anticovert'=>25000]) === 200000, 'trained army total aggregates corps');
check_rule(ArmyPolicy::canTrain(['attack'=>100000,'defense'=>50000,'covert'=>25000,'anticovert'=>25000], 50000), 'training is allowed up to base capacity');
check_rule(!ArmyPolicy::canTrain(['attack'=>150000,'defense'=>50000,'covert'=>25000,'anticovert'=>25000], 1), 'training above base capacity is blocked');
check_rule(ArmyPolicy::remaining(['attack'=>150000,'defense'=>50000,'covert'=>25000,'anticovert'=>25000]) === 0, 'remaining capacity clamps at zero');
if ($failed > 0) { fwrite(STDERR, "$failed test(s) failed; $passed passed.\n"); exit(1); }
echo "All $passed population and army tests passed.\n";
?>
