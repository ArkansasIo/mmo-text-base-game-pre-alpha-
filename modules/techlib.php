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

function tl_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function tl_num($value): string {
    return number_format((float)$value);
}

function tl_catalog(): array {
    return [
        [
            'key' => 'research_campus',
            'name' => 'Research Campus',
            'base' => ['nq' => 120000, 'metal' => 9000, 'crystal' => 13000, 'deut' => 3500, 'energy' => 1200],
            'scale' => 1.57,
            'effect' => 'Research speed +3.0% per level.'
        ],
        [
            'key' => 'data_vault',
            'name' => 'Data Vault',
            'base' => ['nq' => 145000, 'metal' => 7000, 'crystal' => 16000, 'deut' => 2600, 'energy' => 1000],
            'scale' => 1.58,
            'effect' => 'Technology cost reduction +1.5% per level.'
        ],
        [
            'key' => 'simulation_core',
            'name' => 'Simulation Core',
            'base' => ['nq' => 165000, 'metal' => 10500, 'crystal' => 12000, 'deut' => 5200, 'energy' => 1400],
            'scale' => 1.59,
            'effect' => 'Research speed +1.5% and battle modeling quality +2.5% per level.'
        ],
        [
            'key' => 'quantum_archive',
            'name' => 'Quantum Archive',
            'base' => ['nq' => 220000, 'metal' => 12000, 'crystal' => 19000, 'deut' => 6400, 'energy' => 1800],
            'scale' => 1.61,
            'effect' => 'Technology cost reduction +1.0% and archive quality +2.0% per level.'
        ],
        [
            'key' => 'ai_directorate',
            'name' => 'AI Research Directorate',
            'base' => ['nq' => 310000, 'metal' => 18000, 'crystal' => 24000, 'deut' => 9000, 'energy' => 2500],
            'scale' => 1.63,
            'effect' => 'Research speed +2.0% and technology cost reduction +0.5% per level.'
        ],
    ];
}

$catalog = tl_catalog();
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

if (isset($_GET['id']) && $_GET['id'] === 'upgrade') {
    $key = isset($_GET['atype']) ? strtolower((string)$_GET['atype']) : '';
    if (!isset($catalogByKey[$key])) {
        $status = 'Unknown Tech Library building.';
    } else {
        $lvlQ = $s->query("SELECT " . $key . " AS lvl FROM research_infrastructure WHERE uid=" . $uid . " LIMIT 1");
        $cur = ($lvlQ && $lvlQ->num_rows > 0) ? (int)($lvlQ->fetch_object()->lvl ?? 0) : 0;

        $def = $catalogByKey[$key];
        $costNq = (int)round($def['base']['nq'] * pow($def['scale'], $cur));
        $costM = (int)round($def['base']['metal'] * pow($def['scale'], $cur));
        $costC = (int)round($def['base']['crystal'] * pow($def['scale'], $cur));
        $costD = (int)round($def['base']['deut'] * pow($def['scale'], $cur));
        $costE = (int)round($def['base']['energy'] * pow($def['scale'], $cur));

        $bankQ = $s->query("SELECT onHand FROM bank WHERE uid=" . $uid . " LIMIT 1");
        $bank = $bankQ ? $bankQ->fetch_object() : (object)['onHand' => 0];
        $resQ = $s->query("SELECT metal,crystal,deuterium,energy FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
        $res = $resQ ? $resQ->fetch_object() : (object)['metal' => 0, 'crystal' => 0, 'deuterium' => 0, 'energy' => 0];

        if ((int)$bank->onHand < $costNq || (int)$res->metal < $costM || (int)$res->crystal < $costC || (int)$res->deuterium < $costD || (int)$res->energy < $costE) {
            $status = 'Insufficient resources for ' . $def['name'] . ' level ' . ($cur + 1) . '.';
        } else {
            $s->query("UPDATE bank SET onHand=onHand-" . $costNq . " WHERE uid=" . $uid . " LIMIT 1");
            $s->query("UPDATE player_resources SET metal=metal-" . $costM . ", crystal=crystal-" . $costC . ", deuterium=deuterium-" . $costD . ", energy=energy-" . $costE . " WHERE uid=" . $uid . " LIMIT 1");
            $s->query("UPDATE research_infrastructure SET " . $key . "=" . $key . "+1 WHERE uid=" . $uid . " LIMIT 1");
            $status = $def['name'] . ' upgraded to level ' . ($cur + 1) . '.';
        }
    }
}

$infraQ = $s->query("SELECT research_campus, data_vault, simulation_core, quantum_archive, ai_directorate FROM research_infrastructure WHERE uid=" . $uid . " LIMIT 1");
$infra = $infraQ ? $infraQ->fetch_object() : (object)[
    'research_campus' => 0,
    'data_vault' => 0,
    'simulation_core' => 0,
    'quantum_archive' => 0,
    'ai_directorate' => 0,
];

$levels = [
    'research_campus' => (int)$infra->research_campus,
    'data_vault' => (int)$infra->data_vault,
    'simulation_core' => (int)$infra->simulation_core,
    'quantum_archive' => (int)$infra->quantum_archive,
    'ai_directorate' => (int)$infra->ai_directorate,
];

$costDiscount = min(45, ($levels['data_vault'] * 1.5) + ($levels['quantum_archive'] * 1.0) + ($levels['ai_directorate'] * 0.5));
$researchSpeed = 1 + (($levels['research_campus'] * 0.03) + ($levels['simulation_core'] * 0.015) + ($levels['ai_directorate'] * 0.02));
$modelQuality = 1 + (($levels['simulation_core'] * 0.025) + ($levels['quantum_archive'] * 0.02));

$resQ = $s->query("SELECT metal,crystal,deuterium,energy FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
$res = $resQ ? $resQ->fetch_object() : (object)['metal' => 0, 'crystal' => 0, 'deuterium' => 0, 'energy' => 0];
$bankQ = $s->query("SELECT onHand FROM bank WHERE uid=" . $uid . " LIMIT 1");
$bank = $bankQ ? $bankQ->fetch_object() : (object)['onHand' => 0];
?>
<div class="page-hub">
    <div class="page-hub-head">
        <h3>Tech Library Buildings</h3>
        <p>Upgrade research infrastructure to accelerate discovery, reduce technology costs, and improve strategic battle modeling.</p>
    </div>

    <?php if ($status !== '') { ?>
    <div class="card full"><strong><?= tl_h($status); ?></strong></div>
    <?php } ?>

    <div class="page-grid">
        <div class="card">
            <h4>Research Reserves</h4>
            <p><strong>Naquadah:</strong> <?= tl_num((int)$bank->onHand); ?></p>
            <p><strong>Metal:</strong> <?= tl_num((int)$res->metal); ?></p>
            <p><strong>Crystal:</strong> <?= tl_num((int)$res->crystal); ?></p>
            <p><strong>Deuterium:</strong> <?= tl_num((int)$res->deuterium); ?></p>
            <p><strong>Energy:</strong> <?= tl_num((int)$res->energy); ?></p>
        </div>

        <div class="card">
            <h4>Library Effects</h4>
            <p><strong>Research Speed:</strong> <?= tl_num($researchSpeed); ?>x</p>
            <p><strong>Tech Cost Reduction:</strong> <?= tl_num($costDiscount); ?>%</p>
            <p><strong>Battle Model Quality:</strong> <?= tl_num($modelQuality); ?>x</p>
            <p><a href="javascript:void(0)" onclick="sendData('stargatetech','get','mainDisplay'); return false">Open Stargate Tech</a></p>
            <p><a href="javascript:void(0)" onclick="sendData('pages','get','research','techlib'); return false">Open Research Tech Tree</a></p>
        </div>

        <div class="card full">
            <h4>Tech Library Infrastructure Matrix</h4>
            <table class="mini-table" border="0" width="100%">
                <tr>
                    <th align="left">Building</th>
                    <th align="left">Level</th>
                    <th align="left">Next Cost (NQ/M/C/D/E)</th>
                    <th align="left">Effect</th>
                    <th align="left">Action</th>
                </tr>
                <?php foreach ($catalog as $row) {
                    $cur = (int)($levels[$row['key']] ?? 0);
                    $needNq = (int)round($row['base']['nq'] * pow($row['scale'], $cur));
                    $needM = (int)round($row['base']['metal'] * pow($row['scale'], $cur));
                    $needC = (int)round($row['base']['crystal'] * pow($row['scale'], $cur));
                    $needD = (int)round($row['base']['deut'] * pow($row['scale'], $cur));
                    $needE = (int)round($row['base']['energy'] * pow($row['scale'], $cur));
                ?>
                <tr>
                    <td><?= tl_h($row['name']); ?> (<?= tl_h($row['key']); ?>)</td>
                    <td><?= tl_num($cur); ?></td>
                    <td><?= tl_num($needNq); ?>/<?= tl_num($needM); ?>/<?= tl_num($needC); ?>/<?= tl_num($needD); ?>/<?= tl_num($needE); ?></td>
                    <td><?= tl_h($row['effect']); ?></td>
                    <td><a href="javascript:void(0)" onclick="sendData('techlib','get','upgrade','<?= tl_h($row['key']); ?>'); return false">Upgrade</a></td>
                </tr>
                <?php } ?>
            </table>
        </div>
    </div>
</div>
<?php

echo "Query Count: " . $s->queryCount . "<br>";
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());
?>
