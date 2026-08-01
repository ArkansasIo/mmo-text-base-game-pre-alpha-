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

function mf_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function mf_num($value): string {
    return number_format((float)$value);
}

function mf_safeToken(string $value): string {
    return preg_replace('/[^A-Za-z0-9 _-]/', '', $value) ?? '';
}

function mf_catalogSeed(Game $s): void {
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

    $s->query("CREATE TABLE IF NOT EXISTS mega_starship_catalog (
        starship_id INT NOT NULL PRIMARY KEY,
        class_name VARCHAR(120) NOT NULL,
        class_group VARCHAR(60) NOT NULL,
        role_name VARCHAR(60) NOT NULL,
        tier INT NOT NULL DEFAULT 1,
        hull_type VARCHAR(40) NOT NULL,
        metal_cost INT NOT NULL DEFAULT 0,
        crystal_cost INT NOT NULL DEFAULT 0,
        deut_cost INT NOT NULL DEFAULT 0,
        food_cost INT NOT NULL DEFAULT 0,
        water_cost INT NOT NULL DEFAULT 0,
        pop_cost INT NOT NULL DEFAULT 0,
        power INT NOT NULL DEFAULT 0
    )");

    $s->query("CREATE TABLE IF NOT EXISTS mega_building_catalog (
        building_id INT NOT NULL PRIMARY KEY,
        building_name VARCHAR(120) NOT NULL,
        zone_name VARCHAR(60) NOT NULL,
        focus_name VARCHAR(60) NOT NULL,
        tier INT NOT NULL DEFAULT 1,
        metal_cost INT NOT NULL DEFAULT 0,
        crystal_cost INT NOT NULL DEFAULT 0,
        deut_cost INT NOT NULL DEFAULT 0,
        food_cost INT NOT NULL DEFAULT 0,
        water_cost INT NOT NULL DEFAULT 0,
        pop_cost INT NOT NULL DEFAULT 0,
        power INT NOT NULL DEFAULT 0
    )");

    $s->query("CREATE TABLE IF NOT EXISTS mega_unit_catalog (
        unit_id INT NOT NULL PRIMARY KEY,
        unit_name VARCHAR(120) NOT NULL,
        branch_name VARCHAR(20) NOT NULL,
        role_name VARCHAR(60) NOT NULL,
        tier INT NOT NULL DEFAULT 1,
        metal_cost INT NOT NULL DEFAULT 0,
        crystal_cost INT NOT NULL DEFAULT 0,
        deut_cost INT NOT NULL DEFAULT 0,
        food_cost INT NOT NULL DEFAULT 0,
        water_cost INT NOT NULL DEFAULT 0,
        pop_cost INT NOT NULL DEFAULT 0,
        power INT NOT NULL DEFAULT 0
    )");

    $s->query("CREATE TABLE IF NOT EXISTS mega_owned_assets (
        uid INT NOT NULL,
        asset_kind VARCHAR(20) NOT NULL,
        asset_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY(uid, asset_kind, asset_id)
    )");

    $starCountQ = $s->query("SELECT COUNT(*) AS c FROM mega_starship_catalog");
    $buildCountQ = $s->query("SELECT COUNT(*) AS c FROM mega_building_catalog");
    $unitCountQ = $s->query("SELECT COUNT(*) AS c FROM mega_unit_catalog");

    $starCount = $starCountQ ? (int)($starCountQ->fetch_object()->c ?? 0) : 0;
    $buildCount = $buildCountQ ? (int)($buildCountQ->fetch_object()->c ?? 0) : 0;
    $unitCount = $unitCountQ ? (int)($unitCountQ->fetch_object()->c ?? 0) : 0;

    if ($starCount !== 90) {
        $families = ['Aegis', 'Nova', 'Vanguard', 'Tempest', 'Orion', 'Helios', 'Nyx', 'Atlas', 'Leviathan', 'Draco'];
        $roles = ['Scout', 'Frigate', 'Destroyer', 'Cruiser', 'Battlecruiser', 'Carrier', 'Dreadnought', 'Titan', 'Mothership'];
        $hulls = ['Light', 'Balanced', 'Heavy', 'Siege', 'Command', 'Expedition', 'Fortress', 'Relic', 'Prime'];
        $id = 1;
        foreach ($families as $fi => $family) {
            foreach ($roles as $ri => $role) {
                $tier = $ri + 1;
                $metal = (int)(2200 + ($tier * 3200) + ($fi * 550));
                $crystal = (int)(1800 + ($tier * 2600) + ($fi * 450));
                $deut = (int)(900 + ($tier * 1800) + ($fi * 300));
                $food = (int)(350 + ($tier * 320));
                $water = (int)(350 + ($tier * 300));
                $pop = (int)(22 + ($tier * 14));
                $power = (int)(12 + ($tier * 18) + ($fi * 2));
                $name = $family . ' ' . $role;
                $hull = $hulls[$ri % count($hulls)];
                $safeName = mf_safeToken($name);
                $safeFamily = mf_safeToken($family);
                $safeRole = mf_safeToken($role);
                $safeHull = mf_safeToken($hull);
                $s->query("REPLACE INTO mega_starship_catalog (starship_id, class_name, class_group, role_name, tier, hull_type, metal_cost, crystal_cost, deut_cost, food_cost, water_cost, pop_cost, power)
                    VALUES (" . $id . ", '" . $safeName . "', '" . $safeFamily . "', '" . $safeRole . "', " . $tier . ", '" . $safeHull . "', " . $metal . ", " . $crystal . ", " . $deut . ", " . $food . ", " . $water . ", " . $pop . ", " . $power . ")");
                $id++;
            }
        }
    }

    if ($buildCount !== 90) {
        $zones = ['Orbital', 'Planetary', 'Industrial', 'Defense', 'Command', 'Science', 'Civilian', 'Logistics', 'Energy', 'Frontier'];
        $focuses = ['Array', 'Hub', 'Complex', 'Bastion', 'Network', 'Foundry', 'Depot', 'Matrix', 'Citadel'];
        $id = 1;
        foreach ($zones as $zi => $zone) {
            foreach ($focuses as $fi => $focus) {
                $tier = $fi + 1;
                $metal = (int)(1800 + ($tier * 1900) + ($zi * 420));
                $crystal = (int)(1400 + ($tier * 1700) + ($zi * 350));
                $deut = (int)(800 + ($tier * 1200) + ($zi * 240));
                $food = (int)(500 + ($tier * 280));
                $water = (int)(500 + ($tier * 260));
                $pop = (int)(30 + ($tier * 16));
                $power = (int)(10 + ($tier * 15) + ($zi * 2));
                $name = $zone . ' ' . $focus;
                $safeName = mf_safeToken($name);
                $safeZone = mf_safeToken($zone);
                $safeFocus = mf_safeToken($focus);
                $s->query("REPLACE INTO mega_building_catalog (building_id, building_name, zone_name, focus_name, tier, metal_cost, crystal_cost, deut_cost, food_cost, water_cost, pop_cost, power)
                    VALUES (" . $id . ", '" . $safeName . "', '" . $safeZone . "', '" . $safeFocus . "', " . $tier . ", " . $metal . ", " . $crystal . ", " . $deut . ", " . $food . ", " . $water . ", " . $pop . ", " . $power . ")");
                $id++;
            }
        }
    }

    if ($unitCount !== 90) {
        $branches = ['gov', 'civi', 'military'];
        $cadres = ['Alpha', 'Bravo', 'Gamma', 'Delta', 'Epsilon', 'Zeta', 'Sigma', 'Omega', 'Prime', 'Vector'];
        $roles = ['Officer', 'Specialist', 'Commandant'];
        $id = 1;
        foreach ($branches as $bi => $branch) {
            foreach ($cadres as $ci => $cadre) {
                foreach ($roles as $ri => $role) {
                    $tier = $ri + 1 + ($ci % 3);
                    $metal = (int)(700 + ($tier * 700) + ($bi * 280));
                    $crystal = (int)(600 + ($tier * 620) + ($bi * 220));
                    $deut = (int)(300 + ($tier * 420) + ($bi * 160));
                    $food = (int)(260 + ($tier * 210));
                    $water = (int)(260 + ($tier * 190));
                    $pop = (int)(12 + ($tier * 9));
                    $power = (int)(6 + ($tier * 8) + ($bi * 4));
                    $name = strtoupper($branch) . ' ' . $cadre . ' ' . $role;
                    $safeName = mf_safeToken($name);
                    $safeBranch = mf_safeToken($branch);
                    $safeRole = mf_safeToken($cadre . ' ' . $role);
                    $s->query("REPLACE INTO mega_unit_catalog (unit_id, unit_name, branch_name, role_name, tier, metal_cost, crystal_cost, deut_cost, food_cost, water_cost, pop_cost, power)
                        VALUES (" . $id . ", '" . $safeName . "', '" . $safeBranch . "', '" . $safeRole . "', " . $tier . ", " . $metal . ", " . $crystal . ", " . $deut . ", " . $food . ", " . $water . ", " . $pop . ", " . $power . ")");
                    $id++;
                }
            }
        }
    }
}

mf_catalogSeed($s);
$s->query("INSERT IGNORE INTO player_resources (uid) VALUES (" . $uid . ")");

if (isset($_GET['id']) && $_GET['id'] === 'construct') {
    $spec = isset($_GET['atype']) ? (string)$_GET['atype'] : '';
    $parts = explode('|', $spec);
    $kind = isset($parts[0]) ? trim($parts[0]) : '';
    $assetId = isset($parts[1]) ? (int)$parts[1] : 0;
    $amount = isset($parts[2]) ? (int)$parts[2] : 1;

    if ($amount < 1) {
        $amount = 1;
    }
    if ($amount > 200) {
        $amount = 200;
    }

    $kindMap = [
        'starship' => ['table' => 'mega_starship_catalog', 'id_col' => 'starship_id', 'name_col' => 'class_name'],
        'building' => ['table' => 'mega_building_catalog', 'id_col' => 'building_id', 'name_col' => 'building_name'],
        'unit' => ['table' => 'mega_unit_catalog', 'id_col' => 'unit_id', 'name_col' => 'unit_name'],
    ];

    if (!isset($kindMap[$kind]) || $assetId < 1) {
        $status = 'Invalid construction request.';
    } else {
        $cfg = $kindMap[$kind];
        $rowQ = $s->query("SELECT * FROM " . $cfg['table'] . " WHERE " . $cfg['id_col'] . "=" . $assetId . " LIMIT 1");
        $row = $rowQ ? $rowQ->fetch_object() : null;

        if (!$row) {
            $status = 'Catalog entry not found.';
        } else {
            $resQ = $s->query("SELECT metal,crystal,deuterium,food,water,population FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
            $res = $resQ ? $resQ->fetch_object() : (object)['metal' => 0, 'crystal' => 0, 'deuterium' => 0, 'food' => 0, 'water' => 0, 'population' => 0];

            $metalCost = (int)$row->metal_cost * $amount;
            $crystalCost = (int)$row->crystal_cost * $amount;
            $deutCost = (int)$row->deut_cost * $amount;
            $foodCost = (int)$row->food_cost * $amount;
            $waterCost = (int)$row->water_cost * $amount;
            $popCost = (int)$row->pop_cost * $amount;

            $needsCrew = ($kind === 'unit' && isset($row->branch_name) && (string)$row->branch_name === 'military');
            $crewCost = $needsCrew ? max(1, (int)round($popCost * 0.6)) : 0;
            $unitsQ = $s->query("SELECT untrained FROM units WHERE uid=" . $uid . " LIMIT 1");
            $units = $unitsQ ? $unitsQ->fetch_object() : (object)['untrained' => 0];

            if ((int)$res->metal < $metalCost || (int)$res->crystal < $crystalCost || (int)$res->deuterium < $deutCost || (int)$res->food < $foodCost || (int)$res->water < $waterCost || (int)$res->population < $popCost) {
                $status = 'Insufficient resources for this order.';
            } elseif ($needsCrew && (int)$units->untrained < $crewCost) {
                $status = 'Insufficient untrained units for military conversion.';
            } else {
                $s->query("UPDATE player_resources SET
                    metal=metal-" . $metalCost . ",
                    crystal=crystal-" . $crystalCost . ",
                    deuterium=deuterium-" . $deutCost . ",
                    food=food-" . $foodCost . ",
                    water=water-" . $waterCost . ",
                    population=population-" . $popCost . "
                    WHERE uid=" . $uid . " LIMIT 1");

                if ($needsCrew) {
                    $s->query("UPDATE units SET untrained=untrained-" . $crewCost . " WHERE uid=" . $uid . " LIMIT 1");
                }

                $safeKind = mf_safeToken($kind);
                $s->query("INSERT INTO mega_owned_assets (uid, asset_kind, asset_id, quantity)
                    VALUES (" . $uid . ", '" . $safeKind . "', " . $assetId . ", " . $amount . ")
                    ON DUPLICATE KEY UPDATE quantity=quantity+" . $amount);

                $itemName = (string)$row->{$cfg['name_col']};
                $status = 'Order complete: ' . mf_num($amount) . ' x ' . $itemName . ' (' . $kind . ').';
            }
        }
    }
}

$resourcesQ = $s->query("SELECT metal,crystal,deuterium,food,water,population FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
$resources = $resourcesQ ? $resourcesQ->fetch_object() : (object)['metal' => 0, 'crystal' => 0, 'deuterium' => 0, 'food' => 0, 'water' => 0, 'population' => 0];
$unitsQ = $s->query("SELECT untrained FROM units WHERE uid=" . $uid . " LIMIT 1");
$units = $unitsQ ? $unitsQ->fetch_object() : (object)['untrained' => 0];

$starships = [];
$starQ = $s->query("SELECT * FROM mega_starship_catalog ORDER BY starship_id ASC");
if ($starQ) {
    while ($row = $starQ->fetch_assoc()) {
        $starships[] = $row;
    }
}

$buildings = [];
$buildQ = $s->query("SELECT * FROM mega_building_catalog ORDER BY building_id ASC");
if ($buildQ) {
    while ($row = $buildQ->fetch_assoc()) {
        $buildings[] = $row;
    }
}

$unitsCatalog = [];
$unitQ = $s->query("SELECT * FROM mega_unit_catalog ORDER BY unit_id ASC");
if ($unitQ) {
    while ($row = $unitQ->fetch_assoc()) {
        $unitsCatalog[] = $row;
    }
}

$owned = [];
$ownedQ = $s->query("SELECT asset_kind,asset_id,quantity FROM mega_owned_assets WHERE uid=" . $uid);
if ($ownedQ) {
    while ($row = $ownedQ->fetch_assoc()) {
        $owned[$row['asset_kind'] . ':' . $row['asset_id']] = (int)$row['quantity'];
    }
}

$govCount = 0;
$civiCount = 0;
$milCount = 0;
foreach ($unitsCatalog as $entry) {
    if ($entry['branch_name'] === 'gov') {
        $govCount++;
    } elseif ($entry['branch_name'] === 'civi') {
        $civiCount++;
    } elseif ($entry['branch_name'] === 'military') {
        $milCount++;
    }
}
?>
<div class="page-hub">
    <div class="page-hub-head">
        <h3>Mega Forge: 90/90/90 Arsenal</h3>
        <p>Build and recruit 90 starship classes, 90 building classes, and 90 GOV/CIVI/MIL units with live game costs.</p>
    </div>

    <?php if ($status !== '') { ?>
        <div class="card full"><strong><?= mf_h($status); ?></strong></div>
    <?php } ?>

    <div class="page-grid">
        <div class="card">
            <h4>Catalog Totals</h4>
            <p><strong>Starship Classes:</strong> 90</p>
            <p><strong>Building Classes:</strong> 90</p>
            <p><strong>Units Total:</strong> 90</p>
            <p><strong>GOV Units:</strong> <?= mf_num($govCount); ?></p>
            <p><strong>CIVI Units:</strong> <?= mf_num($civiCount); ?></p>
            <p><strong>MIL Units:</strong> <?= mf_num($milCount); ?></p>
        </div>

        <div class="card">
            <h4>Resource Stockpile</h4>
            <p><strong>Metal:</strong> <?= mf_num((int)$resources->metal); ?></p>
            <p><strong>Crystal:</strong> <?= mf_num((int)$resources->crystal); ?></p>
            <p><strong>Deuterium:</strong> <?= mf_num((int)$resources->deuterium); ?></p>
            <p><strong>Food:</strong> <?= mf_num((int)$resources->food); ?></p>
            <p><strong>Water:</strong> <?= mf_num((int)$resources->water); ?></p>
            <p><strong>Population:</strong> <?= mf_num((int)$resources->population); ?></p>
            <p><strong>Untrained Units:</strong> <?= mf_num((int)$units->untrained); ?></p>
        </div>

        <div class="card full">
            <h4>Quick Orders</h4>
            <p>
                <a href="javascript:void(0)" onclick="sendData('megaforge','get','construct','starship|1|1'); return false;">Build Starship #1</a> |
                <a href="javascript:void(0)" onclick="sendData('megaforge','get','construct','building|1|1'); return false;">Build Building #1</a> |
                <a href="javascript:void(0)" onclick="sendData('megaforge','get','construct','unit|1|5'); return false;">Recruit GOV Unit #1 x5</a> |
                <a href="javascript:void(0)" onclick="sendData('megaforge','get','construct','unit|61|5'); return false;">Recruit MIL Unit #61 x5</a>
            </p>
            <p>
                Custom order: Kind
                <select id="mfKind">
                    <option value="starship">starship</option>
                    <option value="building">building</option>
                    <option value="unit">unit</option>
                </select>
                ID <input id="mfId" type="number" min="1" max="90" value="1" style="width:80px;" />
                Qty <input id="mfQty" type="number" min="1" max="200" value="1" style="width:80px;" />
                <a href="javascript:void(0)" onclick="(function(){var k=document.getElementById('mfKind').value;var i=parseInt(document.getElementById('mfId').value,10)||1;var q=parseInt(document.getElementById('mfQty').value,10)||1;sendData('megaforge','get','construct',k+'|'+i+'|'+q);})(); return false;">Execute</a>
            </p>
        </div>

        <div class="card full">
            <h4>90 Starship Classes</h4>
            <table class="mini-table" border="0" width="100%">
                <tr>
                    <th align="left">ID</th>
                    <th align="left">Class</th>
                    <th align="left">Group</th>
                    <th align="left">Tier</th>
                    <th align="left">Cost (M/C/D/F/W/P)</th>
                    <th align="left">Owned</th>
                    <th align="left">Action</th>
                </tr>
                <?php foreach ($starships as $row) {
                    $own = $owned['starship:' . $row['starship_id']] ?? 0;
                ?>
                <tr>
                    <td><?= mf_num((int)$row['starship_id']); ?></td>
                    <td><?= mf_h($row['class_name']); ?></td>
                    <td><?= mf_h($row['class_group']); ?></td>
                    <td><?= mf_num((int)$row['tier']); ?></td>
                    <td><?= mf_num((int)$row['metal_cost']); ?>/<?= mf_num((int)$row['crystal_cost']); ?>/<?= mf_num((int)$row['deut_cost']); ?>/<?= mf_num((int)$row['food_cost']); ?>/<?= mf_num((int)$row['water_cost']); ?>/<?= mf_num((int)$row['pop_cost']); ?></td>
                    <td><?= mf_num((int)$own); ?></td>
                    <td><a href="javascript:void(0)" onclick="sendData('megaforge','get','construct','starship|<?= (int)$row['starship_id']; ?>|1'); return false;">Build 1</a></td>
                </tr>
                <?php } ?>
            </table>
        </div>

        <div class="card full">
            <h4>90 Building Classes</h4>
            <table class="mini-table" border="0" width="100%">
                <tr>
                    <th align="left">ID</th>
                    <th align="left">Building</th>
                    <th align="left">Zone</th>
                    <th align="left">Tier</th>
                    <th align="left">Cost (M/C/D/F/W/P)</th>
                    <th align="left">Owned</th>
                    <th align="left">Action</th>
                </tr>
                <?php foreach ($buildings as $row) {
                    $own = $owned['building:' . $row['building_id']] ?? 0;
                ?>
                <tr>
                    <td><?= mf_num((int)$row['building_id']); ?></td>
                    <td><?= mf_h($row['building_name']); ?></td>
                    <td><?= mf_h($row['zone_name']); ?></td>
                    <td><?= mf_num((int)$row['tier']); ?></td>
                    <td><?= mf_num((int)$row['metal_cost']); ?>/<?= mf_num((int)$row['crystal_cost']); ?>/<?= mf_num((int)$row['deut_cost']); ?>/<?= mf_num((int)$row['food_cost']); ?>/<?= mf_num((int)$row['water_cost']); ?>/<?= mf_num((int)$row['pop_cost']); ?></td>
                    <td><?= mf_num((int)$own); ?></td>
                    <td><a href="javascript:void(0)" onclick="sendData('megaforge','get','construct','building|<?= (int)$row['building_id']; ?>|1'); return false;">Build 1</a></td>
                </tr>
                <?php } ?>
            </table>
        </div>

        <div class="card full">
            <h4>90 GOV/CIVI/MIL Units</h4>
            <table class="mini-table" border="0" width="100%">
                <tr>
                    <th align="left">ID</th>
                    <th align="left">Unit</th>
                    <th align="left">Branch</th>
                    <th align="left">Tier</th>
                    <th align="left">Cost (M/C/D/F/W/P)</th>
                    <th align="left">Owned</th>
                    <th align="left">Action</th>
                </tr>
                <?php foreach ($unitsCatalog as $row) {
                    $own = $owned['unit:' . $row['unit_id']] ?? 0;
                ?>
                <tr>
                    <td><?= mf_num((int)$row['unit_id']); ?></td>
                    <td><?= mf_h($row['unit_name']); ?></td>
                    <td><?= mf_h(strtoupper((string)$row['branch_name'])); ?></td>
                    <td><?= mf_num((int)$row['tier']); ?></td>
                    <td><?= mf_num((int)$row['metal_cost']); ?>/<?= mf_num((int)$row['crystal_cost']); ?>/<?= mf_num((int)$row['deut_cost']); ?>/<?= mf_num((int)$row['food_cost']); ?>/<?= mf_num((int)$row['water_cost']); ?>/<?= mf_num((int)$row['pop_cost']); ?></td>
                    <td><?= mf_num((int)$own); ?></td>
                    <td><a href="javascript:void(0)" onclick="sendData('megaforge','get','construct','unit|<?= (int)$row['unit_id']; ?>|1'); return false;">Recruit 1</a></td>
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