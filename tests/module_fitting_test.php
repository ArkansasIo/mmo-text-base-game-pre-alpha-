<?php
require_once __DIR__ . '/../base/FleetPolicy.class.php';
require_once __DIR__ . '/../base/ModuleFittingPolicy.class.php';
$passed = 0; $failed = 0;
function fitting_check(bool $value, string $name): void { global $passed, $failed; if ($value) { $passed++; echo "PASS: $name\n"; } else { $failed++; echo "FAIL: $name\n"; } }
$scout = FleetPolicy::blueprint('scout');
$carrier = FleetPolicy::blueprint('carrier');
$valid = ['railgun_array'=>1,'shield_hardener'=>1,'cargo_optimizer'=>1];
$fit = ModuleFittingPolicy::fit($carrier, $valid);
fitting_check($fit['valid'], 'valid high, medium, and low modules fit a scout');
fitting_check(ModuleFittingPolicy::module('railgun_array')['slot'] === 'high', 'weapon module is restricted to high slots');
fitting_check(ModuleFittingPolicy::module('shield_hardener')['slot'] === 'medium', 'shield module is restricted to medium slots');
fitting_check(ModuleFittingPolicy::module('cargo_optimizer')['slot'] === 'low', 'cargo module is restricted to low slots');
$tooManyHigh = ModuleFittingPolicy::fit($scout, ['railgun_array'=>99]);
fitting_check(!$tooManyHigh['valid'] && count($tooManyHigh['errors']) > 0, 'slot overflow is rejected');
$tooMuchPower = ModuleFittingPolicy::fit($scout, ['siege_lance'=>99]);
fitting_check(!$tooMuchPower['valid'], 'power-grid and slot overages are rejected');
$power = FleetPolicy::fittedPower(['carrier'=>1], $valid);
fitting_check($power['attack'] > $carrier['attack'] && $power['defense'] > $carrier['defense'] && $power['capacity'] > $carrier['capacity'], 'fitted modules modify fleet power');
fitting_check(count(FleetPolicy::BLUEPRINTS) === 90, 'fitting policy applies to all 90 blueprints');
if ($failed) { fwrite(STDERR, "$failed fitting checks failed; $passed passed.\n"); exit(1); }
echo "All $passed module-fitting checks passed.\n";
?>
