<?php
include("../config.php");

$pagegen = new page_gen();
$pagegen->round_to = 4;
$pagegen->start();

$s = new Game();
if (!$s->loggedIn || !isset($_GET['time'])) {
    header("Location: index.php?");
    exit;
}

$uid = (int)$_SESSION['userid'];
$status = '';

function rh_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function rh_num($value): string {
    return number_format((float)$value);
}

function rh_stargate_bonus(Game $s, int $uid): array {
    $bonus = [
        'production' => 1.0,
        'energy' => 1.0,
        'deuterium' => 1.0,
        'population' => 1.0,
    ];

    $hasTable = $s->query("SHOW TABLES LIKE 'stargate_tech_levels'");
    if (!$hasTable || $hasTable->num_rows === 0) {
        return $bonus;
    }

    $tech = [];
    $q = $s->query("SELECT tech_key, level FROM stargate_tech_levels WHERE uid=" . $uid);
    if ($q) {
        while ($r = $q->fetch_assoc()) {
            $tech[$r['tech_key']] = (int)$r['level'];
        }
    }

    $bonus['production'] += (
        ($tech['lantian_knowledge_matrix'] ?? 0) * 0.008 +
        ($tech['time_dilation_calculus'] ?? 0) * 0.007 +
        ($tech['transit_manifest_ai'] ?? 0) * 0.005
    );
    $bonus['energy'] += (
        ($tech['zero_point_theory'] ?? 0) * 0.020 +
        ($tech['zpm_focusing'] ?? 0) * 0.018 +
        ($tech['reactor_overdrive'] ?? 0) * 0.015 +
        ($tech['grid_redundancy'] ?? 0) * 0.010
    );
    $bonus['deuterium'] += (
        ($tech['wormhole_topology'] ?? 0) * 0.010 +
        ($tech['destiny_navigation'] ?? 0) * 0.008 +
        ($tech['phase_inverters'] ?? 0) * 0.007
    );
    $bonus['population'] += (
        ($tech['ascension_interface'] ?? 0) * 0.005 +
        ($tech['fortress_polarization'] ?? 0) * 0.004
    );

    return $bonus;
}

function rh_rates($baseData, $tech, int $planetCount, array $levels, array $sgBonus): array {
    $incomeBase = max(220, (int)($baseData->income ?? 220));
    $upBase = max(10, (int)($baseData->up ?? 10));
    $techIncome = max(0, (int)($tech->income ?? 0));
    $techProd = max(0, (int)($tech->unitProd ?? 0));

    $prodMul = max(1.0, (float)$sgBonus['production']);
    $energyMul = max(1.0, (float)$sgBonus['energy']);
    $deutMul = max(1.0, (float)$sgBonus['deuterium']);
    $popMul = max(1.0, (float)$sgBonus['population']);

    return [
        'metal' => (int)round(((($incomeBase * 0.40) + ($planetCount * 180) + ($upBase * 8) + ($techProd * 20)) * (1 + ($levels['metal_mine'] * 0.12))) * $prodMul),
        'crystal' => (int)round(((($incomeBase * 0.28) + ($planetCount * 140) + ($upBase * 5) + ($techIncome * 16)) * (1 + ($levels['crystal_lab'] * 0.12))) * $prodMul),
        'deuterium' => (int)round(((($incomeBase * 0.18) + ($planetCount * 120) + ($upBase * 3) + ($techIncome * 12)) * (1 + ($levels['deuterium_refinery'] * 0.12))) * $prodMul * $deutMul),
        'food' => (int)round(((($incomeBase * 0.14) + ($planetCount * 220) + ($techIncome * 9)) * (1 + ($levels['hydroponics'] * 0.10))) * $prodMul),
        'water' => (int)round(((($incomeBase * 0.12) + ($planetCount * 240) + ($techIncome * 8)) * (1 + ($levels['water_plant'] * 0.10))) * $prodMul),
        'population' => max(25, (int)round(((($planetCount * 30) + ($upBase * 0.35)) * (1 + ($levels['habitat_dome'] * 0.08))) * $popMul)),
        'energy' => (int)round(((($incomeBase * 0.22) + ($planetCount * 160) + ($techProd * 14) + ($techIncome * 10)) * (1 + ($levels['energy_reactor'] * 0.13))) * $energyMul),
    ];
}

$s->query("CREATE TABLE IF NOT EXISTS player_resources (
    uid INT NOT NULL PRIMARY KEY,
    metal BIGINT NOT NULL DEFAULT 80000,
    crystal BIGINT NOT NULL DEFAULT 60000,
    deuterium BIGINT NOT NULL DEFAULT 45000,
    food BIGINT NOT NULL DEFAULT 55000,
    water BIGINT NOT NULL DEFAULT 55000,
    population BIGINT NOT NULL DEFAULT 120000,
    energy BIGINT NOT NULL DEFAULT 50000,
    last_tick_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");
$s->query("ALTER TABLE player_resources ADD COLUMN IF NOT EXISTS energy BIGINT NOT NULL DEFAULT 50000");

$s->query("CREATE TABLE IF NOT EXISTS resource_structures (
    uid INT NOT NULL PRIMARY KEY,
    metal_mine INT NOT NULL DEFAULT 1,
    crystal_lab INT NOT NULL DEFAULT 1,
    deuterium_refinery INT NOT NULL DEFAULT 1,
    hydroponics INT NOT NULL DEFAULT 1,
    water_plant INT NOT NULL DEFAULT 1,
    habitat_dome INT NOT NULL DEFAULT 1,
    energy_reactor INT NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");
$s->query("ALTER TABLE resource_structures ADD COLUMN IF NOT EXISTS energy_reactor INT NOT NULL DEFAULT 1");

$s->query("INSERT IGNORE INTO player_resources (uid) VALUES (" . $uid . ")");
$s->query("INSERT IGNORE INTO resource_structures (uid) VALUES (" . $uid . ")");

$baseData = $s->baseVars();
$tech = $s->viewTech();
$planets = $s->getUserPlanets($uid);
$planetCount = max(1, count($planets));

$structQ = $s->query("SELECT metal_mine,crystal_lab,deuterium_refinery,hydroponics,water_plant,habitat_dome,energy_reactor FROM resource_structures WHERE uid=" . $uid . " LIMIT 1");
$structures = $structQ ? $structQ->fetch_object() : (object)[
    'metal_mine' => 1,
    'crystal_lab' => 1,
    'deuterium_refinery' => 1,
    'hydroponics' => 1,
    'water_plant' => 1,
    'habitat_dome' => 1,
    'energy_reactor' => 1,
];

$levels = [
    'metal_mine' => (int)$structures->metal_mine,
    'crystal_lab' => (int)$structures->crystal_lab,
    'deuterium_refinery' => (int)$structures->deuterium_refinery,
    'hydroponics' => (int)$structures->hydroponics,
    'water_plant' => (int)$structures->water_plant,
    'habitat_dome' => (int)$structures->habitat_dome,
    'energy_reactor' => (int)$structures->energy_reactor,
];

$sgBonus = rh_stargate_bonus($s, $uid);
$rates = rh_rates($baseData, $tech, $planetCount, $levels, $sgBonus);

$resQ = $s->query("SELECT metal,crystal,deuterium,food,water,population,energy,last_tick_at FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
$res = $resQ ? $resQ->fetch_object() : (object)[
    'metal' => 80000,
    'crystal' => 60000,
    'deuterium' => 45000,
    'food' => 55000,
    'water' => 55000,
    'population' => 120000,
    'energy' => 50000,
    'last_tick_at' => date('Y-m-d H:i:s'),
];

$lastTickTs = strtotime((string)$res->last_tick_at);
if ($lastTickTs === false) {
    $lastTickTs = time();
}
$ticks = (int)floor(max(0, time() - $lastTickTs) / 1800);
if ($ticks > 0) {
    $metal = max(0, (int)$res->metal + ($rates['metal'] * $ticks));
    $crystal = max(0, (int)$res->crystal + ($rates['crystal'] * $ticks));
    $deuterium = max(0, (int)$res->deuterium + ($rates['deuterium'] * $ticks));
    $food = max(0, (int)$res->food + ($rates['food'] * $ticks));
    $water = max(0, (int)$res->water + ($rates['water'] * $ticks));
    $population = max(0, (int)$res->population + ($rates['population'] * $ticks));
    $energy = max(0, (int)$res->energy + ($rates['energy'] * $ticks));

    $foodUse = (int)round($population * 0.008 * $ticks);
    $waterUse = (int)round($population * 0.007 * $ticks);
    $energyUse = (int)round($population * 0.005 * $ticks);
    $food = max(0, $food - $foodUse);
    $water = max(0, $water - $waterUse);
    $energy = max(0, $energy - $energyUse);

    if ($food === 0 || $water === 0 || $energy === 0) {
        $popDrop = max(150, (int)round($population * 0.02));
        $population = max(0, $population - $popDrop);
    }

    $s->query("UPDATE player_resources SET
        metal=" . (int)$metal . ",
        crystal=" . (int)$crystal . ",
        deuterium=" . (int)$deuterium . ",
        food=" . (int)$food . ",
        water=" . (int)$water . ",
        population=" . (int)$population . ",
        energy=" . (int)$energy . ",
        last_tick_at=NOW()
        WHERE uid=" . $uid . " LIMIT 1");

    $res = (object)[
        'metal' => $metal,
        'crystal' => $crystal,
        'deuterium' => $deuterium,
        'food' => $food,
        'water' => $water,
        'population' => $population,
        'energy' => $energy,
    ];
}

if (isset($_GET['id']) && $_GET['id'] === 'upgrade') {
    $building = isset($_GET['atype']) ? (string)$_GET['atype'] : '';
    $defs = [
        'metal_mine' => ['name' => 'Metal Mine', 'base' => ['metal' => 2000, 'crystal' => 700, 'deuterium' => 0, 'food' => 0, 'water' => 0], 'scale' => 1.55],
        'crystal_lab' => ['name' => 'Crystal Lab', 'base' => ['metal' => 1800, 'crystal' => 900, 'deuterium' => 200, 'food' => 0, 'water' => 0], 'scale' => 1.56],
        'deuterium_refinery' => ['name' => 'Deuterium Refinery', 'base' => ['metal' => 2400, 'crystal' => 1300, 'deuterium' => 0, 'food' => 0, 'water' => 0], 'scale' => 1.58],
        'hydroponics' => ['name' => 'Hydroponics', 'base' => ['metal' => 1600, 'crystal' => 600, 'deuterium' => 0, 'food' => 0, 'water' => 300], 'scale' => 1.48],
        'water_plant' => ['name' => 'Water Plant', 'base' => ['metal' => 1500, 'crystal' => 500, 'deuterium' => 0, 'food' => 300, 'water' => 0], 'scale' => 1.48],
        'habitat_dome' => ['name' => 'Habitat Dome', 'base' => ['metal' => 3000, 'crystal' => 1400, 'deuterium' => 500, 'food' => 800, 'water' => 800], 'scale' => 1.62],
        'energy_reactor' => ['name' => 'Energy Reactor', 'base' => ['metal' => 2800, 'crystal' => 1500, 'deuterium' => 700, 'food' => 400, 'water' => 400, 'energy' => 0], 'scale' => 1.60],
    ];

    if (!isset($defs[$building])) {
        $status = 'Unknown structure.';
    } else {
        $lvl = max(1, $levels[$building]);
        $def = $defs[$building];
        $cost = [];
        foreach ($def['base'] as $k => $v) {
            $cost[$k] = (int)round($v * pow($def['scale'], $lvl - 1));
        }

        $costEnergy = (int)($cost['energy'] ?? 0);

        if ((int)$res->metal < $cost['metal'] || (int)$res->crystal < $cost['crystal'] || (int)$res->deuterium < $cost['deuterium'] || (int)$res->food < $cost['food'] || (int)$res->water < $cost['water'] || (int)$res->energy < $costEnergy) {
            $status = 'Insufficient resources for ' . $def['name'] . ' upgrade.';
        } else {
            $s->query("UPDATE player_resources SET
                metal=metal-" . (int)$cost['metal'] . ",
                crystal=crystal-" . (int)$cost['crystal'] . ",
                deuterium=deuterium-" . (int)$cost['deuterium'] . ",
                food=food-" . (int)$cost['food'] . ",
                water=water-" . (int)$cost['water'] . ",
                energy=energy-" . $costEnergy . "
                WHERE uid=" . $uid . " LIMIT 1");
            $s->query("UPDATE resource_structures SET " . $building . "=" . $building . "+1 WHERE uid=" . $uid . " LIMIT 1");
            $status = $def['name'] . ' upgraded to level ' . ($lvl + 1) . '.';

            $structQ = $s->query("SELECT metal_mine,crystal_lab,deuterium_refinery,hydroponics,water_plant,habitat_dome,energy_reactor FROM resource_structures WHERE uid=" . $uid . " LIMIT 1");
            $structures = $structQ ? $structQ->fetch_object() : $structures;
            $levels = [
                'metal_mine' => (int)$structures->metal_mine,
                'crystal_lab' => (int)$structures->crystal_lab,
                'deuterium_refinery' => (int)$structures->deuterium_refinery,
                'hydroponics' => (int)$structures->hydroponics,
                'water_plant' => (int)$structures->water_plant,
                'habitat_dome' => (int)$structures->habitat_dome,
                'energy_reactor' => (int)$structures->energy_reactor,
            ];

            $resQ = $s->query("SELECT metal,crystal,deuterium,food,water,population,energy,last_tick_at FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
            $res = $resQ ? $resQ->fetch_object() : $res;
            $rates = rh_rates($baseData, $tech, $planetCount, $levels, $sgBonus);
        }
    }
}

if (isset($_GET['id']) && $_GET['id'] === 'trade') {
    $spec = isset($_GET['atype']) ? (string)$_GET['atype'] : '';
    $parts = explode('|', $spec);
    $from = isset($parts[0]) ? trim($parts[0]) : '';
    $to = isset($parts[1]) ? trim($parts[1]) : '';
    $amount = isset($parts[2]) ? (int)$parts[2] : 0;

    $allowed = ['metal', 'crystal', 'deuterium', 'food', 'water', 'energy'];
    $val = ['metal' => 1.0, 'crystal' => 1.6, 'deuterium' => 2.8, 'food' => 1.2, 'water' => 1.1, 'energy' => 2.4];

    if (!in_array($from, $allowed, true) || !in_array($to, $allowed, true) || $from === $to || $amount < 1) {
        $status = 'Invalid trade parameters.';
    } elseif ((int)$res->$from < $amount) {
        $status = 'Insufficient ' . ucfirst($from) . ' for trade.';
    } else {
        $recv = (int)floor(($amount * $val[$from] / $val[$to]) * 0.92);
        if ($recv < 1) {
            $status = 'Trade amount too small after broker fee.';
        } else {
            $s->query("UPDATE player_resources SET " . $from . "=" . $from . "-" . $amount . ", " . $to . "=" . $to . "+" . $recv . " WHERE uid=" . $uid . " LIMIT 1");
            $status = 'Trade complete: ' . rh_num($amount) . ' ' . ucfirst($from) . ' -> ' . rh_num($recv) . ' ' . ucfirst($to) . ' (8% fee).';
            $resQ = $s->query("SELECT metal,crystal,deuterium,food,water,population,energy,last_tick_at FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
            $res = $resQ ? $resQ->fetch_object() : $res;
        }
    }
}

$defs = [
    'metal_mine' => ['label' => 'Metal Mine', 'key' => 'metal'],
    'crystal_lab' => ['label' => 'Crystal Lab', 'key' => 'crystal'],
    'deuterium_refinery' => ['label' => 'Deuterium Refinery', 'key' => 'deuterium'],
    'hydroponics' => ['label' => 'Hydroponics', 'key' => 'food'],
    'water_plant' => ['label' => 'Water Plant', 'key' => 'water'],
    'habitat_dome' => ['label' => 'Habitat Dome', 'key' => 'population'],
    'energy_reactor' => ['label' => 'Energy Reactor', 'key' => 'energy'],
];
?>
<div class="page-hub">
    <div class="page-hub-head">
        <h3>Resource HQ</h3>
        <p>strategic resource control with mines, sustainment lines, and live trade routing.</p>
    </div>

    <?php if ($status !== '') { ?>
        <div class="card full"><strong><?= rh_h($status); ?></strong></div>
    <?php } ?>

    <div class="page-grid">
        <div class="card">
            <h4>Stockpile</h4>
            <p><strong>Metal:</strong> <?= rh_num((int)$res->metal); ?></p>
            <p><strong>Crystal:</strong> <?= rh_num((int)$res->crystal); ?></p>
            <p><strong>Deuterium:</strong> <?= rh_num((int)$res->deuterium); ?></p>
            <p><strong>Food:</strong> <?= rh_num((int)$res->food); ?></p>
            <p><strong>Water:</strong> <?= rh_num((int)$res->water); ?></p>
            <p><strong>Population:</strong> <?= rh_num((int)$res->population); ?></p>
            <p><strong>Energy:</strong> <?= rh_num((int)$res->energy); ?></p>
        </div>

        <div class="card">
            <h4>Production / Turn</h4>
            <p><strong>Metal:</strong> <?= rh_num($rates['metal']); ?></p>
            <p><strong>Crystal:</strong> <?= rh_num($rates['crystal']); ?></p>
            <p><strong>Deuterium:</strong> <?= rh_num($rates['deuterium']); ?></p>
            <p><strong>Food:</strong> <?= rh_num($rates['food']); ?></p>
            <p><strong>Water:</strong> <?= rh_num($rates['water']); ?></p>
            <p><strong>Population:</strong> <?= rh_num($rates['population']); ?></p>
            <p><strong>Energy:</strong> <?= rh_num($rates['energy']); ?></p>
            <p><a href="javascript:void(0)" onclick="sendData('pages','get','economy','production'); return false">Back to Economy Production</a></p>
        </div>

        <div class="card">
            <h4>Stargate Economy Sync</h4>
            <p><strong>Production Multiplier:</strong> <?= rh_num((float)$sgBonus['production'] * 100); ?>%</p>
            <p><strong>Energy Multiplier:</strong> <?= rh_num((float)$sgBonus['energy'] * 100); ?>%</p>
            <p><strong>Deuterium Multiplier:</strong> <?= rh_num((float)$sgBonus['deuterium'] * 100); ?>%</p>
            <p><strong>Population Multiplier:</strong> <?= rh_num((float)$sgBonus['population'] * 100); ?>%</p>
            <p><a href="javascript:void(0)" onclick="sendData('stargatetech','get','mainDisplay'); return false">Open Stargate Technology Command</a></p>
        </div>

        <div class="card full">
            <h4>Structure Upgrades</h4>
            <table class="mini-table" border="0" width="100%">
                <tr>
                    <th align="left">Structure</th>
                    <th align="left">Level</th>
                    <th align="left">Boosted Line</th>
                    <th align="left">Action</th>
                </tr>
                <?php foreach ($defs as $k => $meta) { ?>
                    <tr>
                        <td><?= rh_h($meta['label']); ?></td>
                        <td><?= rh_num((int)$levels[$k]); ?></td>
                        <td><?= rh_h(ucfirst($meta['key'])); ?></td>
                        <td><a href="javascript:void(0)" onclick="sendData('resourcehq','get','upgrade','<?= rh_h($k); ?>'); return false">Upgrade</a></td>
                    </tr>
                <?php } ?>
            </table>
        </div>

        <div class="card full">
            <h4>Market Exchange (8% Broker Fee)</h4>
            <p>
                <a href="javascript:void(0)" onclick="sendData('resourcehq','get','trade','metal|crystal|10000'); return false">10k Metal -> Crystal</a> |
                <a href="javascript:void(0)" onclick="sendData('resourcehq','get','trade','crystal|deuterium|8000'); return false">8k Crystal -> Deuterium</a> |
                <a href="javascript:void(0)" onclick="sendData('resourcehq','get','trade','metal|food|12000'); return false">12k Metal -> Food</a> |
                <a href="javascript:void(0)" onclick="sendData('resourcehq','get','trade','water|metal|10000'); return false">10k Water -> Metal</a> |
                <a href="javascript:void(0)" onclick="sendData('resourcehq','get','trade','energy|deuterium|7000'); return false">7k Energy -> Deuterium</a>
            </p>
            <p>
                Custom trade:
                <select id="rhFrom">
                    <option value="metal">Metal</option>
                    <option value="crystal">Crystal</option>
                    <option value="deuterium">Deuterium</option>
                    <option value="food">Food</option>
                    <option value="water">Water</option>
                    <option value="energy">Energy</option>
                </select>
                <span>to</span>
                <select id="rhTo">
                    <option value="metal">Metal</option>
                    <option value="crystal">Crystal</option>
                    <option value="deuterium">Deuterium</option>
                    <option value="food">Food</option>
                    <option value="water">Water</option>
                    <option value="energy">Energy</option>
                </select>
                <input id="rhAmt" type="number" min="1" value="1000" style="width:100px;" />
                <a href="javascript:void(0)" onclick="(function(){var f=document.getElementById('rhFrom').value;var t=document.getElementById('rhTo').value;var a=parseInt(document.getElementById('rhAmt').value,10)||0;if(a>0&&f!==t){sendData('resourcehq','get','trade',f+'|'+t+'|'+a);}return false;})(); return false;">Trade</a>
            </p>
        </div>
    </div>
</div>
<?php
echo "Query Count: " . $s->queryCount . "<br>";
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());
?>
