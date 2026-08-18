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

function hs_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function hs_num($value): string {
    return number_format((float)$value);
}

function hs_random(int $min, int $max): int {
    try {
        return random_int($min, $max);
    } catch (Exception $e) {
        return mt_rand($min, $max);
    }
}

$s->query("CREATE TABLE IF NOT EXISTS player_resources (
    uid INT NOT NULL PRIMARY KEY,
    metal BIGINT NOT NULL DEFAULT 80000,
    crystal BIGINT NOT NULL DEFAULT 60000,
    deuterium BIGINT NOT NULL DEFAULT 45000,
    food BIGINT NOT NULL DEFAULT 55000,
    water BIGINT NOT NULL DEFAULT 55000,
    population BIGINT NOT NULL DEFAULT 120000,
    last_tick_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$s->query("CREATE TABLE IF NOT EXISTS hyperspace_systems (
    uid INT NOT NULL PRIMARY KEY,
    jump_gate_level INT NOT NULL DEFAULT 0,
    stargate_level INT NOT NULL DEFAULT 0,
    hyperspace_core_level INT NOT NULL DEFAULT 0,
    lane_stability INT NOT NULL DEFAULT 0,
    range_bonus INT NOT NULL DEFAULT 0,
    cooldown_reduction INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$s->query("CREATE TABLE IF NOT EXISTS hyperspace_routes (
    route_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    uid INT NOT NULL,
    route_name VARCHAR(80) NOT NULL,
    destination VARCHAR(80) NOT NULL,
    threat_tier INT NOT NULL DEFAULT 1,
    distance_ly INT NOT NULL DEFAULT 10,
    status VARCHAR(16) NOT NULL DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_uid_status (uid, status)
)");

$s->query("CREATE TABLE IF NOT EXISTS hyperspace_transits (
    transit_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    uid INT NOT NULL,
    route_id INT NOT NULL,
    transit_type VARCHAR(20) NOT NULL,
    fleet_tonnage INT NOT NULL DEFAULT 0,
    depart_at DATETIME NOT NULL,
    eta_at DATETIME NOT NULL,
    return_at DATETIME NOT NULL,
    status VARCHAR(16) NOT NULL DEFAULT 'enroute',
    reward_metal INT NOT NULL DEFAULT 0,
    reward_crystal INT NOT NULL DEFAULT 0,
    reward_deuterium INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_uid_status (uid, status),
    INDEX idx_uid_eta (uid, eta_at)
)");

$s->query("ALTER TABLE hyperspace_transits ADD COLUMN IF NOT EXISTS outcome VARCHAR(32) NOT NULL DEFAULT 'pending', ADD COLUMN IF NOT EXISTS outcome_text VARCHAR(255) NOT NULL DEFAULT ''");$s->query("CREATE TABLE IF NOT EXISTS hyperspace_colonies (colony_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,uid INT NOT NULL,route_id INT NOT NULL,transit_id INT NOT NULL,world_name VARCHAR(80) NOT NULL,population BIGINT UNSIGNED NOT NULL DEFAULT 0,status VARCHAR(16) NOT NULL DEFAULT 'founded',founded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY(colony_id),UNIQUE KEY uq_colony_transit(transit_id),KEY idx_colony_uid(uid,status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");$s->query("INSERT IGNORE INTO player_resources (uid) VALUES (" . $uid . ")");
$s->query("INSERT IGNORE INTO hyperspace_systems (uid) VALUES (" . $uid . ")");

$sysQ = $s->query("SELECT jump_gate_level,stargate_level,hyperspace_core_level,lane_stability,range_bonus,cooldown_reduction FROM hyperspace_systems WHERE uid=" . $uid . " LIMIT 1");
$sys = $sysQ ? $sysQ->fetch_object() : (object)[
    'jump_gate_level' => 0,
    'stargate_level' => 0,
    'hyperspace_core_level' => 0,
    'lane_stability' => 0,
    'range_bonus' => 0,
    'cooldown_reduction' => 0,
];

$resQ = $s->query("SELECT metal,crystal,deuterium,food,water,population FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
$res = $resQ ? $resQ->fetch_object() : (object)[
    'metal' => 0,
    'crystal' => 0,
    'deuterium' => 0,
    'food' => 0,
    'water' => 0,
    'population' => 0,
];

$etaQ = $s->query("SELECT transit_id,transit_type,route_id,fleet_tonnage FROM hyperspace_transits WHERE uid=" . $uid . " AND status='enroute' AND eta_at <= NOW() ORDER BY transit_id ASC");
if ($etaQ) {
    while ($t = $etaQ->fetch_object()) {
        $rewardMetal = 0; $rewardCrystal = 0; $rewardDeut = 0; $outcome = 'arrived'; $outcomeText = 'Transit completed.';
        $transitId = (int)$t->transit_id; $tonnage = max(50, (int)$t->fleet_tonnage);
        $s->begin_transaction();
        try {
            if ($t->transit_type === 'expedition') {
                $rewardMetal = hs_random(2500, 12000) + ((int)$sys->hyperspace_core_level * 240);
                $rewardCrystal = hs_random(1800, 9000) + ((int)$sys->stargate_level * 180);
                $rewardDeut = hs_random(1200, 7600) + ((int)$sys->jump_gate_level * 140);
                $outcome = 'expedition_success'; $outcomeText = 'Deep-space survey returned with recovered industrial resources.';
            } elseif ($t->transit_type === 'transfer') {
                $rewardMetal = (int)round($tonnage * (8 + (int)$sys->jump_gate_level));
                $rewardCrystal = (int)round($tonnage * (5 + (int)$sys->stargate_level));
                $rewardDeut = (int)round($tonnage * 2);
                $outcome = 'transfer_delivered'; $outcomeText = 'Supply convoy delivered its cargo through the hyperspace lane.';
            } elseif ($t->transit_type === 'colonize') {
                $worldName = preg_replace('/[^A-Za-z0-9 _-]/', '', (string)$t->route_id . ' ' . (string)$t->transit_id) ?: ('Colony ' . $transitId);
                $colonyPopulation = max(1000, $tonnage * 12);
                $st = $s->prepare('INSERT INTO hyperspace_colonies(uid,route_id,transit_id,world_name,population) VALUES (?,?,?,?,?)');
                $st->bind_param('iiisi', $uid, $t->route_id, $transitId, $worldName, $colonyPopulation);
                if (!$st->execute()) throw new RuntimeException('Colonization settlement failed.');
                $outcome = 'colony_founded'; $outcomeText = 'A frontier colony was founded with ' . number_format($colonyPopulation) . ' settlers.';
            }
            $s->query("UPDATE player_resources SET metal=metal+" . $rewardMetal . ", crystal=crystal+" . $rewardCrystal . ", deuterium=deuterium+" . $rewardDeut . " WHERE uid=" . $uid . " LIMIT 1");
            $safeOutcome = $s->real_escape_string($outcome); $safeText = $s->real_escape_string($outcomeText);
            $updated = $s->query("UPDATE hyperspace_transits SET status='arrived', outcome='" . $safeOutcome . "', outcome_text='" . $safeText . "', reward_metal=" . $rewardMetal . ", reward_crystal=" . $rewardCrystal . ", reward_deuterium=" . $rewardDeut . " WHERE transit_id=" . $transitId . " AND uid=" . $uid . " AND status='enroute' LIMIT 1");
            if (!$updated || $s->affected_rows !== 1) throw new RuntimeException('Transit state update failed.');
            $s->commit();
            if ($status === '') $status = $outcomeText;
        } catch (Throwable $e) { $s->rollback(); if ($status === '') $status = 'Transit resolution failed safely; retry processing on the next tick.'; }
    }
}

$retQ = $s->query("SELECT transit_id FROM hyperspace_transits WHERE uid=" . $uid . " AND status='arrived' AND return_at <= NOW() ORDER BY transit_id ASC");
if ($retQ) {
    while ($t = $retQ->fetch_object()) {
        $s->query("UPDATE hyperspace_transits SET status='completed' WHERE transit_id=" . (int)$t->transit_id . " AND uid=" . $uid . " LIMIT 1");
        if ($status === '') {
            $status = 'A hyperspace wing returned to command.';
        }
    }
}

if (isset($_GET['id']) && $_GET['id'] === 'upgrade') {
    $kind = isset($_GET['atype']) ? strtolower((string)$_GET['atype']) : '';
    $defs = [
        'jumpgate' => [
            'field' => 'jump_gate_level',
            'name' => 'Jump Gate',
            'base' => ['metal' => 16000, 'crystal' => 14000, 'deuterium' => 10000, 'food' => 2800, 'water' => 2800, 'population' => 180],
            'scale' => 1.52,
        ],
        'stargate' => [
            'field' => 'stargate_level',
            'name' => 'Stargate',
            'base' => ['metal' => 26000, 'crystal' => 21000, 'deuterium' => 16000, 'food' => 3600, 'water' => 3600, 'population' => 260],
            'scale' => 1.58,
        ],
        'core' => [
            'field' => 'hyperspace_core_level',
            'name' => 'Hyperspace Core',
            'base' => ['metal' => 22000, 'crystal' => 18000, 'deuterium' => 15000, 'food' => 3200, 'water' => 3200, 'population' => 230],
            'scale' => 1.55,
        ],
    ];

    if (!isset($defs[$kind])) {
        $status = 'Unknown hyperspace installation.';
    } else {
        $def = $defs[$kind];
        $field = $def['field'];
        $curr = (int)($sys->$field ?? 0);

        if ($kind === 'stargate' && (int)$sys->jump_gate_level < 2) {
            $status = 'Stargate requires Jump Gate level 2+';
        } elseif ($kind === 'core' && (int)$sys->stargate_level < 1) {
            $status = 'Hyperspace Core requires Stargate level 1+';
        } else {
            $cost = [];
            foreach ($def['base'] as $rk => $rv) {
                $cost[$rk] = (int)round($rv * pow($def['scale'], $curr));
            }

            if ((int)$res->metal < $cost['metal'] || (int)$res->crystal < $cost['crystal'] || (int)$res->deuterium < $cost['deuterium'] || (int)$res->food < $cost['food'] || (int)$res->water < $cost['water'] || (int)$res->population < $cost['population']) {
                $status = 'Insufficient resources for ' . $def['name'] . ' upgrade.';
            } else {
                $s->query("UPDATE player_resources SET
                    metal=metal-" . $cost['metal'] . ",
                    crystal=crystal-" . $cost['crystal'] . ",
                    deuterium=deuterium-" . $cost['deuterium'] . ",
                    food=food-" . $cost['food'] . ",
                    water=water-" . $cost['water'] . ",
                    population=population-" . $cost['population'] . "
                    WHERE uid=" . $uid . " LIMIT 1");

                $s->query("UPDATE hyperspace_systems SET " . $field . "=" . $field . "+1 WHERE uid=" . $uid . " LIMIT 1");

                $sysQ = $s->query("SELECT jump_gate_level,stargate_level,hyperspace_core_level FROM hyperspace_systems WHERE uid=" . $uid . " LIMIT 1");
                $sysNow = $sysQ ? $sysQ->fetch_object() : (object)['jump_gate_level' => 0, 'stargate_level' => 0, 'hyperspace_core_level' => 0];

                $laneStability = ((int)$sysNow->stargate_level * 6) + ((int)$sysNow->hyperspace_core_level * 9);
                $rangeBonus = ((int)$sysNow->jump_gate_level * 4) + ((int)$sysNow->stargate_level * 5);
                $cooldownReduction = min(55, ((int)$sysNow->hyperspace_core_level * 6) + ((int)$sysNow->jump_gate_level * 3));

                $s->query("UPDATE hyperspace_systems SET lane_stability=" . $laneStability . ", range_bonus=" . $rangeBonus . ", cooldown_reduction=" . $cooldownReduction . " WHERE uid=" . $uid . " LIMIT 1");

                $sysQ = $s->query("SELECT jump_gate_level,stargate_level,hyperspace_core_level,lane_stability,range_bonus,cooldown_reduction FROM hyperspace_systems WHERE uid=" . $uid . " LIMIT 1");
                $sys = $sysQ ? $sysQ->fetch_object() : $sys;
                $resQ = $s->query("SELECT metal,crystal,deuterium,food,water,population FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
                $res = $resQ ? $resQ->fetch_object() : $res;
                $status = $def['name'] . ' upgraded to level ' . ($curr + 1) . '.';
            }
        }
    }
}

if (isset($_GET['id']) && $_GET['id'] === 'open_route') {
    $tier = isset($_GET['atype']) ? (int)$_GET['atype'] : 1;
    if ($tier < 1) {
        $tier = 1;
    }
    if ($tier > 5) {
        $tier = 5;
    }

    if ((int)$sys->jump_gate_level < 1) {
        $status = 'Open Jump Gate level 1+ before mapping routes.';
    } else {
        $routeCostMetal = 1800 * $tier;
        $routeCostCrystal = 1400 * $tier;
        $routeCostDeut = 2200 * $tier;

        if ((int)$res->metal < $routeCostMetal || (int)$res->crystal < $routeCostCrystal || (int)$res->deuterium < $routeCostDeut) {
            $status = 'Insufficient resources to map this route.';
        } else {
            $routeNames = ['Orion Relay', 'Pegasus Gate', 'Tau Meridian', 'Abydos Arc', 'Lantea Drift', 'Prometheus Line', 'Destiny Spur'];
            $destinations = ['Abydos', 'Dakara', 'Atlantis Fringe', 'Pegasus Cluster', 'Ori Expanse', 'Wraith Belt', 'Milky Way Rim'];
            $routeName = $routeNames[hs_random(0, count($routeNames) - 1)] . ' ' . hs_random(2, 98);
            $destination = $destinations[hs_random(0, count($destinations) - 1)];
            $distance = hs_random(8, 26) + ($tier * 6);

            $safeRoute = preg_replace('/[^A-Za-z0-9 _-]/', '', $routeName) ?? 'Route';
            $safeDest = preg_replace('/[^A-Za-z0-9 _-]/', '', $destination) ?? 'Destination';

            $s->query("UPDATE player_resources SET metal=metal-" . $routeCostMetal . ", crystal=crystal-" . $routeCostCrystal . ", deuterium=deuterium-" . $routeCostDeut . " WHERE uid=" . $uid . " LIMIT 1");
            $s->query("INSERT INTO hyperspace_routes (uid, route_name, destination, threat_tier, distance_ly, status) VALUES (" . $uid . ", '" . $safeRoute . "', '" . $safeDest . "', " . $tier . ", " . $distance . ", 'open')");
            $status = 'Route opened: ' . $safeRoute . ' to ' . $safeDest . '.';

            $resQ = $s->query("SELECT metal,crystal,deuterium,food,water,population FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
            $res = $resQ ? $resQ->fetch_object() : $res;
        }
    }
}

if (isset($_GET['id']) && $_GET['id'] === 'launch') {
    $spec = isset($_GET['atype']) ? (string)$_GET['atype'] : '';
    $parts = explode('|', $spec);
    $routeId = isset($parts[0]) ? (int)$parts[0] : 0;
    $transitType = isset($parts[1]) ? strtolower(trim((string)$parts[1])) : 'transfer';
    $tonnage = isset($parts[2]) ? (int)$parts[2] : 100;

    if ($tonnage < 50) {
        $tonnage = 50;
    }
    if ($tonnage > 50000) {
        $tonnage = 50000;
    }

    $allowedTypes = ['transfer', 'expedition', 'colonize'];
    if (!in_array($transitType, $allowedTypes, true)) {
        $transitType = 'transfer';
    }

    if ($routeId < 1) {
        $status = 'Invalid route selected.';
    } elseif ((int)$sys->jump_gate_level < 1 || (int)$sys->stargate_level < 1) {
        $status = 'Jump Gate and Stargate level 1+ are required for hyperspace launch.';
    } else {
        $routeQ = $s->query("SELECT route_id,route_name,destination,threat_tier,distance_ly,status FROM hyperspace_routes WHERE uid=" . $uid . " AND route_id=" . $routeId . " LIMIT 1");
        $route = $routeQ ? $routeQ->fetch_object() : null;

        if (!$route || $route->status !== 'open') {
            $status = 'Route is unavailable.';
        } else {
            $distance = max(6, (int)$route->distance_ly);
            $jumpLv = (int)$sys->jump_gate_level;
            $starLv = (int)$sys->stargate_level;
            $coreLv = (int)$sys->hyperspace_core_level;

            $deutCost = (int)round(($distance * 45) + ($tonnage * 0.35));
            $foodCost = (int)round(($tonnage * 0.06) + ($distance * 4));
            $waterCost = (int)round(($tonnage * 0.05) + ($distance * 3));
            $popCost = max(20, (int)round($tonnage * 0.02));

            $laneSpeed = max(6, 30 - (int)floor(($jumpLv * 1.2) + ($starLv * 1.4) + ($coreLv * 2.0)));
            $travelMinutes = max(8, (int)round(($distance * 3) + $laneSpeed));
            $returnMinutes = max(10, (int)round($travelMinutes * (1.9 - min(0.55, ((int)$sys->cooldown_reduction / 100)))));

            if ((int)$res->deuterium < $deutCost || (int)$res->food < $foodCost || (int)$res->water < $waterCost || (int)$res->population < $popCost) {
                $status = 'Insufficient fuel/sustainment resources for launch.';
            } else {
                $safeTransit = preg_replace('/[^a-z]/', '', $transitType) ?? 'transfer';
                $s->query("UPDATE player_resources SET deuterium=deuterium-" . $deutCost . ", food=food-" . $foodCost . ", water=water-" . $waterCost . ", population=population-" . $popCost . " WHERE uid=" . $uid . " LIMIT 1");
                $s->query("INSERT INTO hyperspace_transits (uid, route_id, transit_type, fleet_tonnage, depart_at, eta_at, return_at, status)
                    VALUES (" . $uid . ", " . $routeId . ", '" . $safeTransit . "', " . $tonnage . ", NOW(), DATE_ADD(NOW(), INTERVAL " . $travelMinutes . " MINUTE), DATE_ADD(NOW(), INTERVAL " . ($travelMinutes + $returnMinutes) . " MINUTE), 'enroute')");
                $status = 'Transit launched via ' . (string)$route->route_name . ' toward ' . (string)$route->destination . '.';

                $resQ = $s->query("SELECT metal,crystal,deuterium,food,water,population FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
                $res = $resQ ? $resQ->fetch_object() : $res;
            }
        }
    }
}

$routes = [];
$routeListQ = $s->query("SELECT route_id,route_name,destination,threat_tier,distance_ly,status FROM hyperspace_routes WHERE uid=" . $uid . " ORDER BY route_id DESC LIMIT 30");
if ($routeListQ) {
    while ($r = $routeListQ->fetch_assoc()) {
        $routes[] = $r;
    }
}

$transitsQ = $s->query("SELECT t.transit_id,t.route_id,t.transit_type,t.fleet_tonnage,t.status,t.reward_metal,t.reward_crystal,t.reward_deuterium,
DATE_FORMAT(t.eta_at, '%Y-%m-%d %H:%i:%s') AS eta_time,
DATE_FORMAT(t.return_at, '%Y-%m-%d %H:%i:%s') AS return_time, t.outcome, t.outcome_text,
IFNULL(r.route_name,'Route') AS route_name, IFNULL(r.destination,'Unknown') AS destination
FROM hyperspace_transits t
LEFT JOIN hyperspace_routes r ON r.route_id=t.route_id AND r.uid=t.uid
WHERE t.uid=" . $uid . " AND t.status IN ('enroute','arrived')
ORDER BY t.transit_id DESC
LIMIT 30");

$jumpLv = (int)$sys->jump_gate_level;
$starLv = (int)$sys->stargate_level;
$coreLv = (int)$sys->hyperspace_core_level;
$laneCapacity = 200 + ($jumpLv * 160) + ($starLv * 220) + ($coreLv * 260);

?>
<div class="page-hub">
    <div class="page-hub-head">
        <h3>Hyperspace Transit Command</h3>
        <p>Build Jump Gates, Stargates, and Hyperspace Cores to open interstellar lanes and launch deep-space transits.</p>
    </div>

    <?php if ($status !== '') { ?>
    <div class="card full"><strong><?= hs_h($status); ?></strong></div>
    <?php } ?>

    <div class="page-grid">
        <div class="card">
            <h4>Gate Network Levels</h4>
            <p><strong>Jump Gate:</strong> <?= hs_num($jumpLv); ?></p>
            <p><strong>Stargate:</strong> <?= hs_num($starLv); ?></p>
            <p><strong>Hyperspace Core:</strong> <?= hs_num($coreLv); ?></p>
            <p><strong>Lane Stability:</strong> <?= hs_num((int)$sys->lane_stability); ?></p>
            <p><strong>Range Bonus:</strong> <?= hs_num((int)$sys->range_bonus); ?>%</p>
            <p><strong>Cooldown Reduction:</strong> <?= hs_num((int)$sys->cooldown_reduction); ?>%</p>
            <p><strong>Lane Capacity:</strong> <?= hs_num($laneCapacity); ?></p>
        </div>

        <div class="card">
            <h4>Resource Fuel Board</h4>
            <p><strong>Metal:</strong> <?= hs_num((int)$res->metal); ?></p>
            <p><strong>Crystal:</strong> <?= hs_num((int)$res->crystal); ?></p>
            <p><strong>Deuterium:</strong> <?= hs_num((int)$res->deuterium); ?></p>
            <p><strong>Food:</strong> <?= hs_num((int)$res->food); ?></p>
            <p><strong>Water:</strong> <?= hs_num((int)$res->water); ?></p>
            <p><strong>Population:</strong> <?= hs_num((int)$res->population); ?></p>
            <p><a href="javascript:void(0)" onclick="sendData('fleetdock','get','mainDisplay'); return false">Open Fleet Dock</a></p>
            <p><a href="javascript:void(0)" onclick="sendData('pages','get','universe','expedition'); return false">Open Universe Expedition</a></p>
        </div>

        <div class="card full">
            <h4>Infrastructure Upgrades</h4>
            <p><a href="javascript:void(0)" onclick="sendData('hyperspace','get','upgrade','jumpgate'); return false">Upgrade Jump Gate</a> (enables route mapping)</p>
            <p><a href="javascript:void(0)" onclick="sendData('hyperspace','get','upgrade','stargate'); return false">Upgrade Stargate</a> (requires Jump Gate level 2+)</p>
            <p><a href="javascript:void(0)" onclick="sendData('hyperspace','get','upgrade','core'); return false">Upgrade Hyperspace Core</a> (requires Stargate level 1+)</p>
        </div>

        <div class="card full">
            <h4>Route Cartography</h4>
            <p><a href="javascript:void(0)" onclick="sendData('hyperspace','get','open_route','1'); return false">Open Tier 1 Route</a> | <a href="javascript:void(0)" onclick="sendData('hyperspace','get','open_route','3'); return false">Open Tier 3 Route</a> | <a href="javascript:void(0)" onclick="sendData('hyperspace','get','open_route','5'); return false">Open Tier 5 Route</a></p>
            <table class="mini-table" border="0" width="100%">
                <tr>
                    <th align="left">Route</th>
                    <th align="left">Destination</th>
                    <th align="left">Threat</th>
                    <th align="left">Distance (LY)</th>
                    <th align="left">Status</th>
                    <th align="left">Launch</th>
                </tr>
                <?php if (count($routes) === 0) { ?>
                <tr>
                    <td colspan="6">No mapped routes. Open your first route from the controls above.</td>
                </tr>
                <?php } else { foreach ($routes as $r) { ?>
                <tr>
                    <td><?= hs_h($r['route_name']); ?></td>
                    <td><?= hs_h($r['destination']); ?></td>
                    <td><?= hs_num((int)$r['threat_tier']); ?></td>
                    <td><?= hs_num((int)$r['distance_ly']); ?></td>
                    <td><?= hs_h($r['status']); ?></td>
                    <td>
                        <a href="javascript:void(0)" onclick="sendData('hyperspace','get','launch','<?= (int)$r['route_id']; ?>|transfer|200'); return false">Transfer</a> |
                        <a href="javascript:void(0)" onclick="sendData('hyperspace','get','launch','<?= (int)$r['route_id']; ?>|expedition|320'); return false">Expedition</a> |
                        <a href="javascript:void(0)" onclick="sendData('hyperspace','get','launch','<?= (int)$r['route_id']; ?>|colonize|280'); return false">Colonize</a>
                    </td>
                </tr>
                <?php }} ?>
            </table>
        </div>

        <div class="card full">
            <h4>Active Transits</h4>
            <table class="mini-table" border="0" width="100%">
                <tr>
                    <th align="left">Route</th>
                    <th align="left">Destination</th>
                    <th align="left">Type</th>
                    <th align="left">Tonnage</th>
                    <th align="left">ETA</th>
                    <th align="left">Return</th>
                    <th align="left">Status</th>
                    <th align="left">Outcome</th>
                    <th align="left">Reward (M/C/D)</th>
                </tr>
                <?php
                $rows = false;
                if ($transitsQ) {
                    while ($t = $transitsQ->fetch_object()) {
                        $rows = true;
                ?>
                <tr>
                    <td><?= hs_h((string)$t->route_name); ?></td>
                    <td><?= hs_h((string)$t->destination); ?></td>
                    <td><?= hs_h((string)$t->transit_type); ?></td>
                    <td><?= hs_num((int)$t->fleet_tonnage); ?></td>
                    <td><?= hs_h((string)$t->eta_time); ?></td>
                    <td><?= hs_h((string)$t->return_time); ?></td>
                    <td><?= hs_h((string)$t->status); ?></td>
                    <td><?= hs_h((string)($t->outcome_text ?: $t->outcome)); ?></td>
                    <td><?= hs_num((int)$t->reward_metal); ?>/<?= hs_num((int)$t->reward_crystal); ?>/<?= hs_num((int)$t->reward_deuterium); ?></td>
                </tr>
                <?php
                    }
                }
                if (!$rows) {
                ?>
                <tr>
                    <td colspan="9">No active hyperspace transits.</td>
                </tr>
                <?php } ?>
            </table>
        </div>

        <div class="card full">
            <h4>Interstellar Doctrine</h4>
            <ul>
                <li>Jump Gates open stable local lanes and reduce launch friction.</li>
                <li>Stargates increase deep-route projection and threat tolerance.</li>
                <li>Hyperspace Cores compress cooldown windows and improve expedition returns.</li>
            </ul>
        </div>
    </div>
</div>
<?php
echo "Query Count: " . $s->queryCount . "<br>";
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());
?>