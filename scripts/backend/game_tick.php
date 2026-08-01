<?php
// Global game tick processor for cron usage.
// Usage:
//   php scripts/backend/game_tick.php
//   php scripts/backend/game_tick.php --uid=123
//   php scripts/backend/game_tick.php --dry-run

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only\n";
    exit(1);
}

$root = dirname(__DIR__, 2);
require_once $root . "/config.php";

$uidFilter = null;
$dryRun = false;
foreach ($argv as $arg) {
    if (strpos($arg, "--uid=") === 0) {
        $uidFilter = (int)substr($arg, 6);
    }
    if ($arg === "--dry-run") {
        $dryRun = true;
    }
}

if (!class_exists('mysqli')) {
    fwrite(STDERR, "Missing PHP MySQL driver in CLI runtime. Install/enable mysqli (or run with a PHP build that has mysql support).\n");
    fwrite(STDERR, "Detected PDO drivers: " . implode(', ', PDO::getAvailableDrivers()) . "\n");
    exit(2);
}

$db = new mysqli($conf['db_server'], $conf['db_username'], $conf['db_password'], $conf['db_name']);
if ($db->connect_error) {
    fwrite(STDERR, "DB connect error: " . $db->connect_error . "\n");
    exit(1);
}
$db->set_charset('utf8mb4');

function q(mysqli $db, string $sql): void {
    if (!$db->query($sql)) {
        fwrite(STDERR, "SQL error: " . $db->error . " | " . $sql . "\n");
    }
}

function one(mysqli $db, string $sql): ?array {
    $res = $db->query($sql);
    if (!$res) {
        return null;
    }
    $row = $res->fetch_assoc();
    $res->free();
    return $row ?: null;
}

function stargateBonus(mysqli $db, int $uid): array {
    $bonus = [
        'production' => 1.0,
        'energy' => 1.0,
        'deuterium' => 1.0,
        'population' => 1.0,
    ];

    $has = $db->query("SHOW TABLES LIKE 'stargate_tech_levels'");
    if (!$has || $has->num_rows === 0) {
        return $bonus;
    }

    $tech = [];
    $res = $db->query("SELECT tech_key, level FROM stargate_tech_levels WHERE uid=" . $uid);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $tech[$row['tech_key']] = (int)$row['level'];
        }
        $res->free();
    }

    $bonus['production'] +=
        (($tech['lantian_knowledge_matrix'] ?? 0) * 0.008) +
        (($tech['time_dilation_calculus'] ?? 0) * 0.007) +
        (($tech['transit_manifest_ai'] ?? 0) * 0.005);
    $bonus['energy'] +=
        (($tech['zero_point_theory'] ?? 0) * 0.020) +
        (($tech['zpm_focusing'] ?? 0) * 0.018) +
        (($tech['reactor_overdrive'] ?? 0) * 0.015) +
        (($tech['grid_redundancy'] ?? 0) * 0.010);
    $bonus['deuterium'] +=
        (($tech['wormhole_topology'] ?? 0) * 0.010) +
        (($tech['destiny_navigation'] ?? 0) * 0.008) +
        (($tech['phase_inverters'] ?? 0) * 0.007);
    $bonus['population'] +=
        (($tech['ascension_interface'] ?? 0) * 0.005) +
        (($tech['fortress_polarization'] ?? 0) * 0.004);

    return $bonus;
}

function calcRates(array $ctx, array $levels, array $sgBonus): array {
    $incomeBase = max(220, (int)$ctx['income']);
    $upBase = max(10, (int)$ctx['up']);
    $techIncome = max(0, (int)$ctx['tech_income']);
    $techProd = max(0, (int)$ctx['tech_unit_prod']);
    $planetCount = max(1, (int)$ctx['planet_count']);

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

// Schema safety for shared systems.
q($db, "CREATE TABLE IF NOT EXISTS player_resources (
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
q($db, "ALTER TABLE player_resources ADD COLUMN IF NOT EXISTS energy BIGINT NOT NULL DEFAULT 50000");

q($db, "CREATE TABLE IF NOT EXISTS resource_structures (
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
q($db, "ALTER TABLE resource_structures ADD COLUMN IF NOT EXISTS energy_reactor INT NOT NULL DEFAULT 1");

q($db, "CREATE TABLE IF NOT EXISTS hyperspace_systems (
    uid INT NOT NULL PRIMARY KEY,
    jump_gate_level INT NOT NULL DEFAULT 0,
    stargate_level INT NOT NULL DEFAULT 0,
    hyperspace_core_level INT NOT NULL DEFAULT 0,
    lane_stability INT NOT NULL DEFAULT 0,
    range_bonus INT NOT NULL DEFAULT 0,
    cooldown_reduction INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

q($db, "CREATE TABLE IF NOT EXISTS hyperspace_transits (
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

$uidSql = "SELECT uid FROM bank";
if ($uidFilter !== null && $uidFilter > 0) {
    $uidSql .= " WHERE uid=" . $uidFilter;
}
$uidsRes = $db->query($uidSql);
if (!$uidsRes) {
    fwrite(STDERR, "Unable to fetch users from bank table.\n");
    exit(1);
}

$processedUsers = 0;
$resourceUpdates = 0;
$arrivedTransits = 0;
$completedTransits = 0;

while ($u = $uidsRes->fetch_assoc()) {
    $uid = (int)$u['uid'];
    if ($uid <= 0) {
        continue;
    }
    $processedUsers++;

    q($db, "INSERT IGNORE INTO player_resources (uid) VALUES (" . $uid . ")");
    q($db, "INSERT IGNORE INTO resource_structures (uid) VALUES (" . $uid . ")");
    q($db, "INSERT IGNORE INTO hyperspace_systems (uid) VALUES (" . $uid . ")");

    $baseRow = one($db, "SELECT 
        IFNULL(((units.miners*(80+technology.income)) + (units.lifers*(80+technology.income)) + IFNULL(SUM(planets.income_bonus),0) + (race.income_bonus*((units.miners*(80+technology.income)) + (units.lifers*(80+technology.income))))),220) AS income,
        IFNULL(((technology.unitProd*(3+technology.uppl)) + IFNULL(SUM(planets.up_bonus),0) + (race.up_bonus*(technology.unitProd*(3+technology.uppl)))),10) AS up,
        IFNULL(technology.income,0) AS tech_income,
        IFNULL(technology.unitProd,0) AS tech_unit_prod
        FROM userdata
        LEFT JOIN units ON units.uid = userdata.uid
        LEFT JOIN planets ON planets.uid = userdata.uid
        LEFT JOIN race ON race.rid = userdata.rid
        LEFT JOIN technology ON technology.uid = userdata.uid
        WHERE userdata.uid=" . $uid . "
        GROUP BY userdata.uid");

    $planetRow = one($db, "SELECT COUNT(*) AS c FROM planets WHERE uid=" . $uid);
    $planetCount = (int)($planetRow['c'] ?? 0);

    $ctx = [
        'income' => (int)($baseRow['income'] ?? 220),
        'up' => (int)($baseRow['up'] ?? 10),
        'tech_income' => (int)($baseRow['tech_income'] ?? 0),
        'tech_unit_prod' => (int)($baseRow['tech_unit_prod'] ?? 0),
        'planet_count' => max(1, $planetCount),
    ];

    $sRow = one($db, "SELECT metal_mine,crystal_lab,deuterium_refinery,hydroponics,water_plant,habitat_dome,energy_reactor FROM resource_structures WHERE uid=" . $uid . " LIMIT 1");
    $levels = [
        'metal_mine' => (int)($sRow['metal_mine'] ?? 1),
        'crystal_lab' => (int)($sRow['crystal_lab'] ?? 1),
        'deuterium_refinery' => (int)($sRow['deuterium_refinery'] ?? 1),
        'hydroponics' => (int)($sRow['hydroponics'] ?? 1),
        'water_plant' => (int)($sRow['water_plant'] ?? 1),
        'habitat_dome' => (int)($sRow['habitat_dome'] ?? 1),
        'energy_reactor' => (int)($sRow['energy_reactor'] ?? 1),
    ];

    $sgBonus = stargateBonus($db, $uid);
    $rates = calcRates($ctx, $levels, $sgBonus);

    $rRow = one($db, "SELECT metal,crystal,deuterium,food,water,population,energy,last_tick_at FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
    if (!$rRow) {
        continue;
    }

    $lastTick = strtotime((string)($rRow['last_tick_at'] ?? ''));
    if ($lastTick === false) {
        $lastTick = time();
    }
    $ticks = (int)floor(max(0, time() - $lastTick) / 1800);

    if ($ticks > 0) {
        $metal = max(0, (int)$rRow['metal'] + ($rates['metal'] * $ticks));
        $crystal = max(0, (int)$rRow['crystal'] + ($rates['crystal'] * $ticks));
        $deuterium = max(0, (int)$rRow['deuterium'] + ($rates['deuterium'] * $ticks));
        $food = max(0, (int)$rRow['food'] + ($rates['food'] * $ticks));
        $water = max(0, (int)$rRow['water'] + ($rates['water'] * $ticks));
        $population = max(0, (int)$rRow['population'] + ($rates['population'] * $ticks));
        $energy = max(0, (int)$rRow['energy'] + ($rates['energy'] * $ticks));

        $foodUse = (int)round($population * 0.008 * $ticks);
        $waterUse = (int)round($population * 0.007 * $ticks);
        $energyUse = (int)round($population * 0.005 * $ticks);

        $food = max(0, $food - $foodUse);
        $water = max(0, $water - $waterUse);
        $energy = max(0, $energy - $energyUse);

        if ($food === 0 || $water === 0 || $energy === 0) {
            $population = max(0, $population - max(150, (int)round($population * 0.02)));
        }

        if (!$dryRun) {
            q($db, "UPDATE player_resources SET
                metal=" . $metal . ",
                crystal=" . $crystal . ",
                deuterium=" . $deuterium . ",
                food=" . $food . ",
                water=" . $water . ",
                population=" . $population . ",
                energy=" . $energy . ",
                last_tick_at=NOW()
                WHERE uid=" . $uid . " LIMIT 1");
        }
        $resourceUpdates++;
    }

    $sys = one($db, "SELECT jump_gate_level,stargate_level,hyperspace_core_level FROM hyperspace_systems WHERE uid=" . $uid . " LIMIT 1");
    $jump = (int)($sys['jump_gate_level'] ?? 0);
    $stargate = (int)($sys['stargate_level'] ?? 0);
    $core = (int)($sys['hyperspace_core_level'] ?? 0);

    $enroute = $db->query("SELECT transit_id, transit_type FROM hyperspace_transits WHERE uid=" . $uid . " AND status='enroute' AND eta_at <= NOW() ORDER BY transit_id ASC");
    if ($enroute) {
        while ($t = $enroute->fetch_assoc()) {
            $tid = (int)$t['transit_id'];
            $m = 0;
            $c = 0;
            $d = 0;
            if ($t['transit_type'] === 'expedition') {
                $m = random_int(2500, 12000) + ($core * 240);
                $c = random_int(1800, 9000) + ($stargate * 180);
                $d = random_int(1200, 7600) + ($jump * 140);
                if (!$dryRun) {
                    q($db, "UPDATE player_resources SET metal=metal+" . $m . ", crystal=crystal+" . $c . ", deuterium=deuterium+" . $d . " WHERE uid=" . $uid . " LIMIT 1");
                }
            }
            if (!$dryRun) {
                q($db, "UPDATE hyperspace_transits SET status='arrived', reward_metal=" . $m . ", reward_crystal=" . $c . ", reward_deuterium=" . $d . " WHERE transit_id=" . $tid . " AND uid=" . $uid . " LIMIT 1");
            }
            $arrivedTransits++;
        }
        $enroute->free();
    }

    $arrived = $db->query("SELECT transit_id FROM hyperspace_transits WHERE uid=" . $uid . " AND status='arrived' AND return_at <= NOW() ORDER BY transit_id ASC");
    if ($arrived) {
        while ($t = $arrived->fetch_assoc()) {
            $tid = (int)$t['transit_id'];
            if (!$dryRun) {
                q($db, "UPDATE hyperspace_transits SET status='completed' WHERE transit_id=" . $tid . " AND uid=" . $uid . " LIMIT 1");
            }
            $completedTransits++;
        }
        $arrived->free();
    }
}
$uidsRes->free();

echo "Game tick complete" . ($dryRun ? " (dry-run)" : "") . "\n";
echo "Users processed: " . $processedUsers . "\n";
echo "Resource updates: " . $resourceUpdates . "\n";
echo "Transits arrived: " . $arrivedTransits . "\n";
echo "Transits completed: " . $completedTransits . "\n";

$db->close();
