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

function ob_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function ob_num($value): string {
    return number_format((float)$value);
}

function ob_catalog(): array {
    return [
        ['key' => 'metal_mine', 'name' => 'Metal Mine', 'category' => 'Resources', 'base' => ['metal' => 60, 'crystal' => 15, 'deuterium' => 0, 'energy' => 0], 'scale' => 1.50, 'desc' => 'Increases metal production.'],
        ['key' => 'crystal_mine', 'name' => 'Crystal Mine', 'category' => 'Resources', 'base' => ['metal' => 48, 'crystal' => 24, 'deuterium' => 0, 'energy' => 0], 'scale' => 1.50, 'desc' => 'Increases crystal production.'],
        ['key' => 'deuterium_synthesizer', 'name' => 'Deuterium Synthesizer', 'category' => 'Resources', 'base' => ['metal' => 225, 'crystal' => 75, 'deuterium' => 0, 'energy' => 0], 'scale' => 1.50, 'desc' => 'Increases deuterium output.'],
        ['key' => 'solar_plant', 'name' => 'Solar Plant', 'category' => 'Resources', 'base' => ['metal' => 75, 'crystal' => 30, 'deuterium' => 0, 'energy' => 0], 'scale' => 1.50, 'desc' => 'Generates basic energy output.'],
        ['key' => 'fusion_reactor', 'name' => 'Fusion Reactor', 'category' => 'Resources', 'base' => ['metal' => 900, 'crystal' => 360, 'deuterium' => 180, 'energy' => 0], 'scale' => 1.80, 'desc' => 'High-end energy generation from deuterium.'],
        ['key' => 'metal_storage', 'name' => 'Metal Storage', 'category' => 'Resources', 'base' => ['metal' => 1000, 'crystal' => 0, 'deuterium' => 0, 'energy' => 0], 'scale' => 2.00, 'desc' => 'Increases metal cap.'],
        ['key' => 'crystal_storage', 'name' => 'Crystal Storage', 'category' => 'Resources', 'base' => ['metal' => 1000, 'crystal' => 500, 'deuterium' => 0, 'energy' => 0], 'scale' => 2.00, 'desc' => 'Increases crystal cap.'],
        ['key' => 'deuterium_tank', 'name' => 'Deuterium Tank', 'category' => 'Resources', 'base' => ['metal' => 1000, 'crystal' => 1000, 'deuterium' => 0, 'energy' => 0], 'scale' => 2.00, 'desc' => 'Increases deuterium cap.'],

        ['key' => 'robotics_factory', 'name' => 'Robotics Factory', 'category' => 'Facilities', 'base' => ['metal' => 400, 'crystal' => 120, 'deuterium' => 200, 'energy' => 0], 'scale' => 2.00, 'desc' => 'Speeds up construction.'],
        ['key' => 'shipyard', 'name' => 'Shipyard', 'category' => 'Facilities', 'base' => ['metal' => 400, 'crystal' => 200, 'deuterium' => 100, 'energy' => 0], 'scale' => 2.00, 'desc' => 'Unlocks faster fleet and defense production.'],
        ['key' => 'research_lab', 'name' => 'Research Lab', 'category' => 'Facilities', 'base' => ['metal' => 200, 'crystal' => 400, 'deuterium' => 200, 'energy' => 0], 'scale' => 2.00, 'desc' => 'Boosts research development.'],
        ['key' => 'alliance_depot', 'name' => 'Alliance Depot', 'category' => 'Facilities', 'base' => ['metal' => 20000, 'crystal' => 40000, 'deuterium' => 0, 'energy' => 0], 'scale' => 2.00, 'desc' => 'Fleet support logistics for allies.'],
        ['key' => 'missile_silo', 'name' => 'Missile Silo', 'category' => 'Facilities', 'base' => ['metal' => 20000, 'crystal' => 20000, 'deuterium' => 1000, 'energy' => 0], 'scale' => 2.00, 'desc' => 'Stores missile defenses.'],
        ['key' => 'nanite_factory', 'name' => 'Nanite Factory', 'category' => 'Facilities', 'base' => ['metal' => 1000000, 'crystal' => 500000, 'deuterium' => 100000, 'energy' => 150000], 'scale' => 2.00, 'desc' => 'Extreme build speed acceleration.'],
        ['key' => 'terraformer', 'name' => 'Terraformer', 'category' => 'Facilities', 'base' => ['metal' => 0, 'crystal' => 50000, 'deuterium' => 100000, 'energy' => 1000], 'scale' => 2.00, 'desc' => 'Expands planet build space.'],
        ['key' => 'space_dock', 'name' => 'Space Dock', 'category' => 'Facilities', 'base' => ['metal' => 20000, 'crystal' => 20000, 'deuterium' => 10000, 'energy' => 5000], 'scale' => 2.00, 'desc' => 'Supports fleet repair cycles.'],

        ['key' => 'lunar_base', 'name' => 'Lunar Base', 'category' => 'Lunar', 'base' => ['metal' => 20000, 'crystal' => 40000, 'deuterium' => 20000, 'energy' => 5000], 'scale' => 2.00, 'desc' => 'Unlocks moon infrastructure.'],
        ['key' => 'sensor_phalanx', 'name' => 'Sensor Phalanx', 'category' => 'Lunar', 'base' => ['metal' => 20000, 'crystal' => 40000, 'deuterium' => 20000, 'energy' => 0], 'scale' => 2.00, 'desc' => 'Scans nearby fleet movements.'],
        ['key' => 'jump_gate', 'name' => 'Jump Gate', 'category' => 'Lunar', 'base' => ['metal' => 2000000, 'crystal' => 4000000, 'deuterium' => 2000000, 'energy' => 250000], 'scale' => 2.00, 'desc' => 'Instant moon-to-moon fleet transfer.'],

        ['key' => 'rocket_launcher', 'name' => 'Rocket Launcher', 'category' => 'Defense', 'base' => ['metal' => 2000, 'crystal' => 0, 'deuterium' => 0, 'energy' => 0], 'scale' => 1.20, 'desc' => 'Basic planetary defense turret.'],
        ['key' => 'light_laser', 'name' => 'Light Laser', 'category' => 'Defense', 'base' => ['metal' => 1500, 'crystal' => 500, 'deuterium' => 0, 'energy' => 0], 'scale' => 1.20, 'desc' => 'Fast anti-light hull defense.'],
        ['key' => 'heavy_laser', 'name' => 'Heavy Laser', 'category' => 'Defense', 'base' => ['metal' => 6000, 'crystal' => 2000, 'deuterium' => 0, 'energy' => 0], 'scale' => 1.25, 'desc' => 'High output beam defense.'],
        ['key' => 'gauss_cannon', 'name' => 'Gauss Cannon', 'category' => 'Defense', 'base' => ['metal' => 20000, 'crystal' => 15000, 'deuterium' => 2000, 'energy' => 0], 'scale' => 1.28, 'desc' => 'Heavy kinetic planetary defense.'],
        ['key' => 'ion_cannon', 'name' => 'Ion Cannon', 'category' => 'Defense', 'base' => ['metal' => 2000, 'crystal' => 6000, 'deuterium' => 0, 'energy' => 0], 'scale' => 1.24, 'desc' => 'Shield-piercing defense battery.'],
        ['key' => 'plasma_turret', 'name' => 'Plasma Turret', 'category' => 'Defense', 'base' => ['metal' => 50000, 'crystal' => 50000, 'deuterium' => 30000, 'energy' => 3000], 'scale' => 1.30, 'desc' => 'Top-tier defense emplacement.'],
        ['key' => 'small_shield_dome', 'name' => 'Small Shield Dome', 'category' => 'Defense', 'base' => ['metal' => 10000, 'crystal' => 10000, 'deuterium' => 0, 'energy' => 0], 'scale' => 1.18, 'desc' => 'Defensive shield layer.'],
        ['key' => 'large_shield_dome', 'name' => 'Large Shield Dome', 'category' => 'Defense', 'base' => ['metal' => 50000, 'crystal' => 50000, 'deuterium' => 0, 'energy' => 0], 'scale' => 1.20, 'desc' => 'Enhanced shield envelope.'],
        ['key' => 'anti_ballistic_missiles', 'name' => 'Anti-Ballistic Missiles', 'category' => 'Defense', 'base' => ['metal' => 8000, 'crystal' => 2000, 'deuterium' => 0, 'energy' => 0], 'scale' => 1.16, 'desc' => 'Intercepts incoming missile attacks.'],
        ['key' => 'interplanetary_missiles', 'name' => 'Interplanetary Missiles', 'category' => 'Defense', 'base' => ['metal' => 12500, 'crystal' => 2500, 'deuterium' => 10000, 'energy' => 0], 'scale' => 1.18, 'desc' => 'Long-range missile strike assets.'],

        ['key' => 'solar_satellite', 'name' => 'Solar Satellite', 'category' => 'Orbitals', 'base' => ['metal' => 0, 'crystal' => 2000, 'deuterium' => 500, 'energy' => 0], 'scale' => 1.18, 'desc' => 'Orbital energy support array.'],
        ['key' => 'crawler', 'name' => 'Crawler', 'category' => 'Orbitals', 'base' => ['metal' => 2000, 'crystal' => 2000, 'deuterium' => 1000, 'energy' => 300], 'scale' => 1.18, 'desc' => 'Mining throughput support unit.'],
    ];
}

$catalog = ob_catalog();
$catalogMap = [];
foreach ($catalog as $entry) {
    $catalogMap[$entry['key']] = $entry;
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

$s->query("CREATE TABLE IF NOT EXISTS ogame_building_levels (
    uid INT NOT NULL,
    building_key VARCHAR(64) NOT NULL,
    level INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY(uid, building_key)
)");

$s->query("INSERT IGNORE INTO player_resources (uid) VALUES (" . $uid . ")");

if (isset($_GET['id']) && $_GET['id'] === 'upgrade') {
    $bKey = isset($_GET['atype']) ? strtolower((string)$_GET['atype']) : '';
    if (!isset($catalogMap[$bKey])) {
        $status = 'Unknown building key.';
    } else {
        $entry = $catalogMap[$bKey];
        $lvlQ = $s->query("SELECT level FROM ogame_building_levels WHERE uid=" . $uid . " AND building_key='" . $bKey . "' LIMIT 1");
        $currentLevel = 0;
        if ($lvlQ && $lvlQ->num_rows > 0) {
            $currentLevel = (int)($lvlQ->fetch_object()->level ?? 0);
        }

        $costMetal = (int)round($entry['base']['metal'] * pow($entry['scale'], $currentLevel));
        $costCrystal = (int)round($entry['base']['crystal'] * pow($entry['scale'], $currentLevel));
        $costDeut = (int)round($entry['base']['deuterium'] * pow($entry['scale'], $currentLevel));
        $costEnergy = (int)round($entry['base']['energy'] * pow($entry['scale'], $currentLevel));

        $resQ = $s->query("SELECT metal,crystal,deuterium,energy FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
        $res = $resQ ? $resQ->fetch_object() : (object)['metal' => 0, 'crystal' => 0, 'deuterium' => 0, 'energy' => 0];

        if ((int)$res->metal < $costMetal || (int)$res->crystal < $costCrystal || (int)$res->deuterium < $costDeut || (int)$res->energy < $costEnergy) {
            $status = 'Insufficient resources for ' . $entry['name'] . ' upgrade.';
        } else {
            $s->query("UPDATE player_resources SET
                metal=metal-" . $costMetal . ",
                crystal=crystal-" . $costCrystal . ",
                deuterium=deuterium-" . $costDeut . ",
                energy=energy-" . $costEnergy . "
                WHERE uid=" . $uid . " LIMIT 1");

            $s->query("INSERT INTO ogame_building_levels (uid, building_key, level)
                VALUES (" . $uid . ", '" . $bKey . "', 1)
                ON DUPLICATE KEY UPDATE level=level+1");

            $status = $entry['name'] . ' upgraded to level ' . ($currentLevel + 1) . '.';
        }
    }
}

$resQ = $s->query("SELECT metal,crystal,deuterium,food,water,population,energy FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
$resources = $resQ ? $resQ->fetch_object() : (object)[
    'metal' => 0,
    'crystal' => 0,
    'deuterium' => 0,
    'food' => 0,
    'water' => 0,
    'population' => 0,
    'energy' => 0,
];

$levels = [];
$lvlRows = $s->query("SELECT building_key,level FROM ogame_building_levels WHERE uid=" . $uid);
if ($lvlRows) {
    while ($row = $lvlRows->fetch_assoc()) {
        $levels[$row['building_key']] = (int)$row['level'];
    }
}

$grouped = [];
foreach ($catalog as $entry) {
    $grouped[$entry['category']][] = $entry;
}
?>
<div class="page-hub">
    <div class="page-hub-head">
        <h3>Infrastructure Command</h3>
        <p>Manage the full strategic building grid: resources, facilities, lunar systems, defenses, and orbital support.</p>
    </div>

    <?php if ($status !== '') { ?>
        <div class="card full"><strong><?= ob_h($status); ?></strong></div>
    <?php } ?>

    <div class="page-grid">
        <div class="card">
            <h4>Strategic Stockpile</h4>
            <p><strong>Metal:</strong> <?= ob_num((int)$resources->metal); ?></p>
            <p><strong>Crystal:</strong> <?= ob_num((int)$resources->crystal); ?></p>
            <p><strong>Deuterium:</strong> <?= ob_num((int)$resources->deuterium); ?></p>
            <p><strong>Energy:</strong> <?= ob_num((int)$resources->energy); ?></p>
            <p><strong>Food:</strong> <?= ob_num((int)$resources->food); ?></p>
            <p><strong>Water:</strong> <?= ob_num((int)$resources->water); ?></p>
            <p><strong>Population:</strong> <?= ob_num((int)$resources->population); ?></p>
        </div>

        <div class="card">
            <h4>Integration Links</h4>
            <p><a href="javascript:void(0)" onclick="sendData('resourcehq','get','mainDisplay'); return false">Open Resource HQ</a></p>
            <p><a href="javascript:void(0)" onclick="sendData('fleetdock','get','mainDisplay'); return false">Open Fleet Dock</a></p>
            <p><a href="javascript:void(0)" onclick="sendData('stations','get','mainDisplay'); return false">Open Stations Command</a></p>
            <p><a href="javascript:void(0)" onclick="sendData('hyperspace','get','mainDisplay'); return false">Open Hyperspace Command</a></p>
            <p><a href="javascript:void(0)" onclick="sendData('megaforge','get','mainDisplay'); return false">Open Mega Forge</a></p>
        </div>

        <?php foreach ($grouped as $category => $rows) { ?>
        <div class="card full">
            <h4><?= ob_h($category); ?> Buildings</h4>
            <table class="mini-table" border="0" width="100%">
                <tr>
                    <th align="left">Building</th>
                    <th align="left">Level</th>
                    <th align="left">Next Cost (M/C/D/E)</th>
                    <th align="left">Notes</th>
                    <th align="left">Action</th>
                </tr>
                <?php foreach ($rows as $entry) {
                    $curr = (int)($levels[$entry['key']] ?? 0);
                    $needM = (int)round($entry['base']['metal'] * pow($entry['scale'], $curr));
                    $needC = (int)round($entry['base']['crystal'] * pow($entry['scale'], $curr));
                    $needD = (int)round($entry['base']['deuterium'] * pow($entry['scale'], $curr));
                    $needE = (int)round($entry['base']['energy'] * pow($entry['scale'], $curr));
                ?>
                <tr>
                    <td><?= ob_h($entry['name']); ?> (<?= ob_h($entry['key']); ?>)</td>
                    <td><?= ob_num($curr); ?></td>
                    <td><?= ob_num($needM); ?>/<?= ob_num($needC); ?>/<?= ob_num($needD); ?>/<?= ob_num($needE); ?></td>
                    <td><?= ob_h($entry['desc']); ?></td>
                    <td><a href="javascript:void(0)" onclick="sendData('infrastructure','get','upgrade','<?= ob_h($entry['key']); ?>'); return false">Upgrade</a></td>
                </tr>
                <?php } ?>
            </table>
        </div>
        <?php } ?>
    </div>
</div>
<?php
echo "Query Count: " . $s->queryCount . "<br>";
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());
?>