<?php
require_once __DIR__ . '/../base/TerritoryEconomy.class.php';
$passed = 0; $failed = 0;
function check_economy(bool $condition, string $name): void { global $passed, $failed; if ($condition) { $passed++; echo "PASS: $name\n"; } else { $failed++; echo "FAIL: $name\n"; } }
check_economy(TerritoryEconomy::clampTax(-10) === 0, 'tax clamps below zero');
check_economy(TerritoryEconomy::clampTax(25) === 25, 'tax allows maximum rate');
check_economy(TerritoryEconomy::clampTax(100) === 25, 'tax clamps above maximum rate');
$base = TerritoryEconomy::production(100, 5, 1);
$strong = TerritoryEconomy::production(1000, 25, 10);
check_economy($base['metal'] > 0 && $base['crystal'] > 0 && $base['energy'] > 0, 'claimed territory produces strategic resources');
check_economy($base['credits'] > 0, 'taxed territory produces credits');
check_economy($strong['metal'] > $base['metal'], 'control points and guild level scale production');
check_economy($strong['credits'] > $base['credits'], 'higher tax rate scales treasury tax yield');
$accrued = TerritoryEconomy::accrue(['control_points'=>500,'tax_rate'=>10], 2, 7200, 0);
check_economy($accrued['ticks'] === 4, 'accrual calculates 30-minute ticks');
$offline = TerritoryEconomy::accrue(['control_points'=>500,'tax_rate'=>10], 2, 99999999, 0);
check_economy($offline['ticks'] === TerritoryEconomy::MAX_CATCHUP_TICKS, 'offline accrual is capped');
if ($failed > 0) { fwrite(STDERR, "$failed test(s) failed; $passed passed.\n"); exit(1); }
echo "All $passed territory economy tests passed.\n";
?>
