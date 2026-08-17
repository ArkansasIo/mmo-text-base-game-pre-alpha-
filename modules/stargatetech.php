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

function sg_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function sg_num($value): string {
    return number_format((float)$value);
}

function sg_research_infra(Game $s, int $uid): array {
    $defaults = [
        'research_campus' => 0,
        'data_vault' => 0,
        'simulation_core' => 0,
        'quantum_archive' => 0,
        'ai_directorate' => 0,
        'cost_discount' => 0.0,
        'research_speed' => 1.0,
    ];

    $has = $s->query("SHOW TABLES LIKE 'research_infrastructure'");
    if (!$has || $has->num_rows === 0) {
        return $defaults;
    }

    $q = $s->query("SELECT research_campus, data_vault, simulation_core, quantum_archive, ai_directorate FROM research_infrastructure WHERE uid=" . $uid . " LIMIT 1");
    if (!$q || $q->num_rows === 0) {
        return $defaults;
    }

    $row = $q->fetch_object();
    $defaults['research_campus'] = (int)($row->research_campus ?? 0);
    $defaults['data_vault'] = (int)($row->data_vault ?? 0);
    $defaults['simulation_core'] = (int)($row->simulation_core ?? 0);
    $defaults['quantum_archive'] = (int)($row->quantum_archive ?? 0);
    $defaults['ai_directorate'] = (int)($row->ai_directorate ?? 0);

    $discount =
        ($defaults['data_vault'] * 0.015) +
        ($defaults['quantum_archive'] * 0.010) +
        ($defaults['ai_directorate'] * 0.005);
    $defaults['cost_discount'] = min(0.45, $discount);

    $speed =
        1.0 +
        ($defaults['research_campus'] * 0.030) +
        ($defaults['simulation_core'] * 0.015) +
        ($defaults['ai_directorate'] * 0.020);
    $defaults['research_speed'] = max(1.0, $speed);

    return $defaults;
}

function sg_catalog(): array {
    return [
        ['key' => 'naquadah_physics', 'name' => 'Naquadah Physics', 'domain' => 'Core Science', 'base' => ['nq' => 140000, 'metal' => 6000, 'crystal' => 9000, 'deut' => 3000, 'energy' => 1400], 'scale' => 1.58, 'effect' => 'Stargate core stability +2.5% per level.'],
        ['key' => 'subspace_harmonics', 'name' => 'Subspace Harmonics', 'domain' => 'Core Science', 'base' => ['nq' => 175000, 'metal' => 7000, 'crystal' => 11000, 'deut' => 3500, 'energy' => 1800], 'scale' => 1.60, 'effect' => 'Dialing precision and route lock speed +2.2% per level.'],
        ['key' => 'wormhole_topology', 'name' => 'Wormhole Topology', 'domain' => 'Core Science', 'base' => ['nq' => 210000, 'metal' => 10000, 'crystal' => 13000, 'deut' => 4500, 'energy' => 2100], 'scale' => 1.62, 'effect' => 'Jump lane reliability +2.1% per level.'],
        ['key' => 'event_horizon_dynamics', 'name' => 'Event Horizon Dynamics', 'domain' => 'Core Science', 'base' => ['nq' => 260000, 'metal' => 12000, 'crystal' => 15000, 'deut' => 5500, 'energy' => 2600], 'scale' => 1.63, 'effect' => 'Transit flow efficiency +2.4% per level.'],
        ['key' => 'zero_point_theory', 'name' => 'Zero Point Theory', 'domain' => 'Core Science', 'base' => ['nq' => 420000, 'metal' => 18000, 'crystal' => 22000, 'deut' => 9000, 'energy' => 3500], 'scale' => 1.66, 'effect' => 'Energy conversion rate +3.0% per level.'],

        ['key' => 'gate_dialing_protocols', 'name' => 'Gate Dialing Protocols', 'domain' => 'Gate Operations', 'base' => ['nq' => 120000, 'metal' => 5000, 'crystal' => 7000, 'deut' => 2500, 'energy' => 900], 'scale' => 1.55, 'effect' => 'Dial speed and queue throughput +2.0% per level.'],
        ['key' => 'address_archives', 'name' => 'Address Archives', 'domain' => 'Gate Operations', 'base' => ['nq' => 98000, 'metal' => 3500, 'crystal' => 6000, 'deut' => 1800, 'energy' => 650], 'scale' => 1.54, 'effect' => 'Known destination count and scan detail +2.3% per level.'],
        ['key' => 'gate_bridge_arrays', 'name' => 'Gate Bridge Arrays', 'domain' => 'Gate Operations', 'base' => ['nq' => 195000, 'metal' => 9000, 'crystal' => 12000, 'deut' => 4200, 'energy' => 1500], 'scale' => 1.60, 'effect' => 'Inter-galaxy transfer throughput +2.0% per level.'],
        ['key' => 'auto_retargeting', 'name' => 'Auto Retargeting', 'domain' => 'Gate Operations', 'base' => ['nq' => 145000, 'metal' => 6500, 'crystal' => 8000, 'deut' => 3000, 'energy' => 1100], 'scale' => 1.57, 'effect' => 'Failed lock fallback and route recovery +2.1% per level.'],
        ['key' => 'gate_encryption', 'name' => 'Gate Encryption', 'domain' => 'Gate Operations', 'base' => ['nq' => 215000, 'metal' => 7000, 'crystal' => 14500, 'deut' => 5200, 'energy' => 1700], 'scale' => 1.61, 'effect' => 'Unauthorized dialing resistance +2.4% per level.'],

        ['key' => 'zpm_focusing', 'name' => 'ZPM Focusing', 'domain' => 'Power Systems', 'base' => ['nq' => 350000, 'metal' => 13000, 'crystal' => 20000, 'deut' => 7500, 'energy' => 3600], 'scale' => 1.65, 'effect' => 'High-density power routing +2.8% per level.'],
        ['key' => 'reactor_overdrive', 'name' => 'Reactor Overdrive', 'domain' => 'Power Systems', 'base' => ['nq' => 230000, 'metal' => 9500, 'crystal' => 12000, 'deut' => 6000, 'energy' => 2500], 'scale' => 1.60, 'effect' => 'Energy reactor boost +2.5% per level.'],
        ['key' => 'capacitor_lattices', 'name' => 'Capacitor Lattices', 'domain' => 'Power Systems', 'base' => ['nq' => 155000, 'metal' => 7200, 'crystal' => 9000, 'deut' => 3200, 'energy' => 1200], 'scale' => 1.56, 'effect' => 'Peak-load buffering +2.1% per level.'],
        ['key' => 'phase_inverters', 'name' => 'Phase Inverters', 'domain' => 'Power Systems', 'base' => ['nq' => 180000, 'metal' => 7800, 'crystal' => 10500, 'deut' => 3500, 'energy' => 1400], 'scale' => 1.58, 'effect' => 'Power loss mitigation +2.0% per level.'],
        ['key' => 'grid_redundancy', 'name' => 'Grid Redundancy', 'domain' => 'Power Systems', 'base' => ['nq' => 125000, 'metal' => 6000, 'crystal' => 7000, 'deut' => 2400, 'energy' => 800], 'scale' => 1.54, 'effect' => 'Energy network survivability +2.0% per level.'],

        ['key' => 'bc304_navigation', 'name' => 'BC-304 Navigation Matrix', 'domain' => 'Fleet Integration', 'base' => ['nq' => 190000, 'metal' => 12000, 'crystal' => 9500, 'deut' => 6500, 'energy' => 1500], 'scale' => 1.59, 'effect' => 'Fleet transit timing +2.2% per level.'],
        ['key' => 'asgard_beam_sync', 'name' => 'Asgard Beam Sync', 'domain' => 'Fleet Integration', 'base' => ['nq' => 240000, 'metal' => 14000, 'crystal' => 13500, 'deut' => 7500, 'energy' => 1900], 'scale' => 1.61, 'effect' => 'Support beam coordination +2.3% per level.'],
        ['key' => 'atlantis_docklink', 'name' => 'Atlantis Docklink', 'domain' => 'Fleet Integration', 'base' => ['nq' => 225000, 'metal' => 12500, 'crystal' => 12000, 'deut' => 6800, 'energy' => 1800], 'scale' => 1.60, 'effect' => 'Docked fleet turnaround speed +2.3% per level.'],
        ['key' => 'transit_manifest_ai', 'name' => 'Transit Manifest AI', 'domain' => 'Fleet Integration', 'base' => ['nq' => 130000, 'metal' => 5500, 'crystal' => 8500, 'deut' => 2800, 'energy' => 1000], 'scale' => 1.55, 'effect' => 'Cargo and formation optimization +2.0% per level.'],
        ['key' => 'escort_weave_tactics', 'name' => 'Escort Weave Tactics', 'domain' => 'Fleet Integration', 'base' => ['nq' => 160000, 'metal' => 7000, 'crystal' => 9200, 'deut' => 3300, 'energy' => 1200], 'scale' => 1.57, 'effect' => 'Transit escort survivability +2.1% per level.'],

        ['key' => 'atlantis_city_shields', 'name' => 'Atlantis City Shields', 'domain' => 'Defense Tech', 'base' => ['nq' => 280000, 'metal' => 14500, 'crystal' => 18500, 'deut' => 7300, 'energy' => 2400], 'scale' => 1.62, 'effect' => 'Base shield capacity +2.6% per level.'],
        ['key' => 'iris_reinforcement', 'name' => 'Iris Reinforcement', 'domain' => 'Defense Tech', 'base' => ['nq' => 170000, 'metal' => 8000, 'crystal' => 9000, 'deut' => 4000, 'energy' => 1300], 'scale' => 1.58, 'effect' => 'Gate breach resistance +2.3% per level.'],
        ['key' => 'point_defense_web', 'name' => 'Point Defense Web', 'domain' => 'Defense Tech', 'base' => ['nq' => 185000, 'metal' => 10000, 'crystal' => 9800, 'deut' => 4200, 'energy' => 1450], 'scale' => 1.58, 'effect' => 'Base anti-raid fire coverage +2.2% per level.'],
        ['key' => 'drone_command_uplink', 'name' => 'Drone Command Uplink', 'domain' => 'Defense Tech', 'base' => ['nq' => 260000, 'metal' => 11800, 'crystal' => 17000, 'deut' => 6800, 'energy' => 2100], 'scale' => 1.61, 'effect' => 'Drone response speed and precision +2.4% per level.'],
        ['key' => 'fortress_polarization', 'name' => 'Fortress Polarization', 'domain' => 'Defense Tech', 'base' => ['nq' => 300000, 'metal' => 15500, 'crystal' => 19500, 'deut' => 7900, 'energy' => 2600], 'scale' => 1.63, 'effect' => 'Planetary hardening +2.5% per level.'],

        ['key' => 'wraith_countermeasures', 'name' => 'Wraith Countermeasures', 'domain' => 'Threat Response', 'base' => ['nq' => 205000, 'metal' => 9200, 'crystal' => 11800, 'deut' => 4600, 'energy' => 1500], 'scale' => 1.59, 'effect' => 'Wraith interception and attrition +2.3% per level.'],
        ['key' => 'ori_null_fields', 'name' => 'Ori Null Fields', 'domain' => 'Threat Response', 'base' => ['nq' => 295000, 'metal' => 14500, 'crystal' => 17500, 'deut' => 7200, 'energy' => 2300], 'scale' => 1.63, 'effect' => 'Ori tech dampening +2.5% per level.'],
        ['key' => 'replicator_scramblers', 'name' => 'Replicator Scramblers', 'domain' => 'Threat Response', 'base' => ['nq' => 245000, 'metal' => 11500, 'crystal' => 15500, 'deut' => 6400, 'energy' => 2000], 'scale' => 1.61, 'effect' => 'Replicator adaptation disruption +2.4% per level.'],
        ['key' => 'goauld_signal_masks', 'name' => 'Goa\'uld Signal Masks', 'domain' => 'Threat Response', 'base' => ['nq' => 145000, 'metal' => 7000, 'crystal' => 8700, 'deut' => 3200, 'energy' => 1000], 'scale' => 1.56, 'effect' => 'Sensor signature obfuscation +2.1% per level.'],
        ['key' => 'anubis_warhead_hardening', 'name' => 'Anubis Warhead Hardening', 'domain' => 'Threat Response', 'base' => ['nq' => 255000, 'metal' => 13000, 'crystal' => 14800, 'deut' => 6200, 'energy' => 1900], 'scale' => 1.61, 'effect' => 'Missile and bombardment resistance +2.3% per level.'],

        ['key' => 'destiny_navigation', 'name' => 'Destiny Navigation', 'domain' => 'Ancient Systems', 'base' => ['nq' => 330000, 'metal' => 15500, 'crystal' => 21000, 'deut' => 8300, 'energy' => 2900], 'scale' => 1.64, 'effect' => 'Deep-space route success +2.6% per level.'],
        ['key' => 'lantian_knowledge_matrix', 'name' => 'Lantian Knowledge Matrix', 'domain' => 'Ancient Systems', 'base' => ['nq' => 360000, 'metal' => 16500, 'crystal' => 23000, 'deut' => 9200, 'energy' => 3200], 'scale' => 1.65, 'effect' => 'Global research throughput +2.7% per level.'],
        ['key' => 'time_dilation_calculus', 'name' => 'Time Dilation Calculus', 'domain' => 'Ancient Systems', 'base' => ['nq' => 390000, 'metal' => 17500, 'crystal' => 24500, 'deut' => 10000, 'energy' => 3500], 'scale' => 1.66, 'effect' => 'Action-cycle optimization +2.8% per level.'],
        ['key' => 'ascension_interface', 'name' => 'Ascension Interface', 'domain' => 'Ancient Systems', 'base' => ['nq' => 480000, 'metal' => 22000, 'crystal' => 29000, 'deut' => 12800, 'energy' => 4200], 'scale' => 1.68, 'effect' => 'Strategic doctrine amplification +3.0% per level.'],
        ['key' => 'zpm_containment', 'name' => 'ZPM Containment', 'domain' => 'Ancient Systems', 'base' => ['nq' => 520000, 'metal' => 25000, 'crystal' => 32000, 'deut' => 14500, 'energy' => 4800], 'scale' => 1.69, 'effect' => 'High-output energy containment +3.1% per level.'],
    ];
}

$catalog = sg_catalog();
$catalogByKey = [];
foreach ($catalog as $row) {
    $catalogByKey[$row['key']] = $row;
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

$s->query("CREATE TABLE IF NOT EXISTS stargate_tech_levels (
    uid INT NOT NULL,
    tech_key VARCHAR(64) NOT NULL,
    level INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY(uid, tech_key)
)");

$s->query("INSERT IGNORE INTO player_resources (uid) VALUES (" . $uid . ")");

$s->query("CREATE TABLE IF NOT EXISTS research_infrastructure (
    uid INT NOT NULL PRIMARY KEY,
    research_campus INT NOT NULL DEFAULT 0,
    data_vault INT NOT NULL DEFAULT 0,
    simulation_core INT NOT NULL DEFAULT 0,
    quantum_archive INT NOT NULL DEFAULT 0,
    ai_directorate INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");
$s->query("INSERT IGNORE INTO research_infrastructure (uid) VALUES (" . $uid . ")");

$infra = sg_research_infra($s, $uid);

if (isset($_GET['id']) && $_GET['id'] === 'upgrade') {
    $techKey = isset($_GET['atype']) ? strtolower((string)$_GET['atype']) : '';
    if (!isset($catalogByKey[$techKey])) {
        $status = 'Unknown Stargate technology.';
    } else {
        $row = $catalogByKey[$techKey];
        $lvlQ = $s->query("SELECT level FROM stargate_tech_levels WHERE uid=" . $uid . " AND tech_key='" . $techKey . "' LIMIT 1");
        $cur = 0;
        if ($lvlQ && $lvlQ->num_rows > 0) {
            $cur = (int)($lvlQ->fetch_object()->level ?? 0);
        }

        $discountFactor = 1.0 - (float)$infra['cost_discount'];
        if ($discountFactor < 0.55) {
            $discountFactor = 0.55;
        }

        $costNq = (int)round(($row['base']['nq'] * pow($row['scale'], $cur)) * $discountFactor);
        $costM = (int)round(($row['base']['metal'] * pow($row['scale'], $cur)) * $discountFactor);
        $costC = (int)round(($row['base']['crystal'] * pow($row['scale'], $cur)) * $discountFactor);
        $costD = (int)round(($row['base']['deut'] * pow($row['scale'], $cur)) * $discountFactor);
        $costE = (int)round(($row['base']['energy'] * pow($row['scale'], $cur)) * $discountFactor);

        $bankQ = $s->query("SELECT onHand FROM bank WHERE uid=" . $uid . " LIMIT 1");
        $bank = $bankQ ? $bankQ->fetch_object() : (object)['onHand' => 0];
        $resQ = $s->query("SELECT metal,crystal,deuterium,energy FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
        $res = $resQ ? $resQ->fetch_object() : (object)['metal' => 0, 'crystal' => 0, 'deuterium' => 0, 'energy' => 0];

        if ((int)$bank->onHand < $costNq || (int)$res->metal < $costM || (int)$res->crystal < $costC || (int)$res->deuterium < $costD || (int)$res->energy < $costE) {
            $status = 'Insufficient resources for ' . $row['name'] . ' level ' . ($cur + 1) . '.';
        } else {
            $s->query("UPDATE bank SET onHand=onHand-" . $costNq . " WHERE uid=" . $uid . " LIMIT 1");
            $s->query("UPDATE player_resources SET metal=metal-" . $costM . ", crystal=crystal-" . $costC . ", deuterium=deuterium-" . $costD . ", energy=energy-" . $costE . " WHERE uid=" . $uid . " LIMIT 1");
            $s->query("INSERT INTO stargate_tech_levels (uid, tech_key, level) VALUES (" . $uid . ", '" . $techKey . "', 1) ON DUPLICATE KEY UPDATE level=level+1");
            $status = $row['name'] . ' upgraded to level ' . ($cur + 1) . '.';
        }
    }
}

$levels = [];
$levelsQ = $s->query("SELECT tech_key,level FROM stargate_tech_levels WHERE uid=" . $uid);
if ($levelsQ) {
    while ($l = $levelsQ->fetch_assoc()) {
        $levels[$l['tech_key']] = (int)$l['level'];
    }
}

$resourcesQ = $s->query("SELECT metal,crystal,deuterium,energy FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
$resources = $resourcesQ ? $resourcesQ->fetch_object() : (object)['metal' => 0, 'crystal' => 0, 'deuterium' => 0, 'energy' => 0];
$bankQ = $s->query("SELECT onHand FROM bank WHERE uid=" . $uid . " LIMIT 1");
$bank = $bankQ ? $bankQ->fetch_object() : (object)['onHand' => 0];

$groups = [];
foreach ($catalog as $row) {
    $groups[$row['domain']][] = $row;
}

$totalLevels = 0;
foreach ($levels as $lv) {
    $totalLevels += (int)$lv;
}

?>
<div class="page-hub">
    <div class="page-hub-head">
        <h3>Stargate Technology Command</h3>
        <p>Research and upgrade complete Stargate-era technology domains for gates, fleets, power, defense, and ancient systems.</p>
    </div>

    <?php if ($status !== '') { ?>
    <div class="card full"><strong><?= sg_h($status); ?></strong></div>
    <?php } ?>

    <div class="page-grid">
        <div class="card">
            <h4>Research Reserves</h4>
            <p><strong>Naquadah:</strong> <?= sg_num((int)$bank->onHand); ?></p>
            <p><strong>Metal:</strong> <?= sg_num((int)$resources->metal); ?></p>
            <p><strong>Crystal:</strong> <?= sg_num((int)$resources->crystal); ?></p>
            <p><strong>Deuterium:</strong> <?= sg_num((int)$resources->deuterium); ?></p>
            <p><strong>Energy:</strong> <?= sg_num((int)$resources->energy); ?></p>
        </div>

        <div class="card">
            <h4>Program Summary</h4>
            <p><strong>Domains:</strong> <?= sg_num(count($groups)); ?></p>
            <p><strong>Total Technologies:</strong> <?= sg_num(count($catalog)); ?></p>
            <p><strong>Total Tech Levels:</strong> <?= sg_num($totalLevels); ?></p>
            <p><strong>Research Speed Multiplier:</strong> <?= sg_num((float)$infra['research_speed']); ?>x</p>
            <p><strong>Tech Cost Reduction:</strong> <?= sg_num((float)$infra['cost_discount'] * 100); ?>%</p>
            <p><a href="javascript:void(0)" onclick="sendData('technology','get','mainDisplay'); return false">Legacy Technology Module</a></p>
            <p><a href="javascript:void(0)" onclick="sendData('hyperspace','get','mainDisplay'); return false">Hyperspace Command</a></p>
            <p><a href="javascript:void(0)" onclick="sendData('infrastructure','get','mainDisplay'); return false">Infrastructure Systems</a></p>
            <p><a href="javascript:void(0)" onclick="sendData('techlib','get','mainDisplay'); return false">Tech Library Buildings</a></p>
        </div>

        <?php foreach ($groups as $domain => $items) { ?>
        <div class="card full">
            <h4><?= sg_h($domain); ?></h4>
            <table class="mini-table" border="0" width="100%">
                <tr>
                    <th align="left">Technology</th>
                    <th align="left">Level</th>
                    <th align="left">Next Cost (NQ/M/C/D/E)</th>
                    <th align="left">Effect</th>
                    <th align="left">Action</th>
                </tr>
                <?php foreach ($items as $tech) {
                    $cur = (int)($levels[$tech['key']] ?? 0);
                    $discountFactor = 1.0 - (float)$infra['cost_discount'];
                    if ($discountFactor < 0.55) {
                        $discountFactor = 0.55;
                    }
                    $needNq = (int)round(($tech['base']['nq'] * pow($tech['scale'], $cur)) * $discountFactor);
                    $needM = (int)round(($tech['base']['metal'] * pow($tech['scale'], $cur)) * $discountFactor);
                    $needC = (int)round(($tech['base']['crystal'] * pow($tech['scale'], $cur)) * $discountFactor);
                    $needD = (int)round(($tech['base']['deut'] * pow($tech['scale'], $cur)) * $discountFactor);
                    $needE = (int)round(($tech['base']['energy'] * pow($tech['scale'], $cur)) * $discountFactor);
                ?>
                <tr>
                    <td><?= sg_h($tech['name']); ?> (<?= sg_h($tech['key']); ?>)</td>
                    <td><?= sg_num($cur); ?></td>
                    <td><?= sg_num($needNq); ?>/<?= sg_num($needM); ?>/<?= sg_num($needC); ?>/<?= sg_num($needD); ?>/<?= sg_num($needE); ?></td>
                    <td><?= sg_h($tech['effect']); ?></td>
                    <td><a href="javascript:void(0)" onclick="sendData('stargatetech','get','upgrade','<?= sg_h($tech['key']); ?>'); return false">Upgrade</a></td>
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