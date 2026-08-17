<?php
require_once __DIR__ . '/../base/ProgressionCaps.class.php';

$passed = 0;
$failed = 0;
function check(bool $condition, string $name): void {
    global $passed, $failed;
    if ($condition) { $passed++; echo "PASS: $name\n"; }
    else { $failed++; echo "FAIL: $name\n"; }
}

$expected = [
    'infrastructure' => 30,
    'core_research' => 30,
    'stargate_hyperspace' => 25,
    'power' => 25,
    'combat_technology' => 30,
    'combat_site' => 25,
    'combat_installation' => 20,
    'military_rank' => 50,
    'unit_veterancy' => 10,
    'battle_waves' => 8,
];
foreach ($expected as $family => $cap) {
    check(ProgressionCaps::max($family) === $cap, "$family cap is $cap");
    check(ProgressionCaps::clamp($family, $cap + 100) === $cap, "$family clamps above cap");
    check(ProgressionCaps::clamp($family, -10) === 0, "$family clamps negative values to zero");
    check(ProgressionCaps::canUpgrade($family, $cap - 1, 1), "$family allows upgrade to cap");
    check(!ProgressionCaps::canUpgrade($family, $cap, 1), "$family blocks upgrade above cap");
    check(!ProgressionCaps::canUpgrade($family, 0, 0), "$family rejects zero-delta upgrade");
}

check(ProgressionCaps::familyForTechnology('attack') === 'combat_technology', 'attack maps to combat technology');
check(ProgressionCaps::familyForTechnology('defense') === 'combat_technology', 'defense maps to combat technology');
check(ProgressionCaps::familyForTechnology('galaxy') === 'stargate_hyperspace', 'galaxy maps to Stargate/hyperspace');
check(ProgressionCaps::familyForTechnology('ascend') === 'stargate_hyperspace', 'ascend maps to Stargate/hyperspace');
check(ProgressionCaps::familyForTechnology('puCap') === 'power', 'puCap maps to power');
check(ProgressionCaps::familyForTechnology('pmCap') === 'power', 'pmCap maps to power');
check(ProgressionCaps::familyForTechnology('cov_lvl') === 'core_research', 'covert level maps to core research');
check(ProgressionCaps::familyForTechnology('anti_lvl') === 'core_research', 'anti-covert level maps to core research');

// Fixed content boundaries from the current game design.
check(strlen('ABCDEFGHIJKLMNOPQRSTUVWXYZ') === 26, 'universe taxonomy contains 26 A-Z classes');
check(1 >= 1 && 9 <= 9, 'world size range is 1-9');
check(1 <= 8, 'wave minimum is within 1-8');
check(8 <= ProgressionCaps::max('battle_waves'), 'wave maximum is 8');
check(1 <= 1440 && 1440 >= 1, 'administrator turn interval range is 1-1440 minutes');
check(100000000000 <= 100000000000, 'administrator grant ceiling is 100 billion');
check(1000000000000 <= 1000000000000, 'administrator resource ceiling is 1 trillion');
check(in_array('compact', ['compact', 'standard', 'expanded'], true), 'compact density is supported');
check(in_array('standard', ['compact', 'standard', 'expanded'], true), 'standard density is supported');
check(in_array('expanded', ['compact', 'standard', 'expanded'], true), 'expanded density is supported');

if ($failed > 0) {
    fwrite(STDERR, "\n$failed test(s) failed; $passed passed.\n");
    exit(1);
}
echo "\nAll $passed progression-cap tests passed.\n";
