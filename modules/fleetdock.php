<?php
include("../config.php");

$pagegen = new page_gen();
$pagegen->round_to = 4;
$pagegen->start();

$s = new Game();
if (!$s->loggedIn || !$_GET['time']) {
    header("Location: https://realmbattles.org/SGWnew/index.php?");
    exit;
}
$s->updatePower($_SESSION['userid']);

$uid = (int)$_SESSION['userid'];
$status = '';

function fd_h($value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function fd_num($value): string {
        return number_format((float)$value);
}

function fd_shipDefs(): array {
        return [
                'probe' => ['name' => 'Scout Probe', 'metal' => 2000, 'crystal' => 1000, 'deut' => 400, 'crew' => 3, 'power' => 1],
                'light_fighter' => ['name' => 'Light Fighter', 'metal' => 3500, 'crystal' => 1500, 'deut' => 800, 'crew' => 8, 'power' => 4],
                'heavy_fighter' => ['name' => 'Heavy Fighter', 'metal' => 7000, 'crystal' => 3500, 'deut' => 1500, 'crew' => 14, 'power' => 9],
                'cruiser' => ['name' => 'Cruiser', 'metal' => 16000, 'crystal' => 9000, 'deut' => 5000, 'crew' => 30, 'power' => 24],
                'battleship' => ['name' => 'Battleship', 'metal' => 30000, 'crystal' => 22000, 'deut' => 12000, 'crew' => 55, 'power' => 50],
                'carrier' => ['name' => 'Carrier', 'metal' => 45000, 'crystal' => 30000, 'deut' => 20000, 'crew' => 80, 'power' => 70],
                'recycler' => ['name' => 'Recycler', 'metal' => 12000, 'crystal' => 8000, 'deut' => 6000, 'crew' => 18, 'power' => 6],
                'colony_ship' => ['name' => 'Colony Ship', 'metal' => 22000, 'crystal' => 18000, 'deut' => 14000, 'crew' => 35, 'power' => 12],
                'mothership' => ['name' => 'Mothership', 'metal' => 90000, 'crystal' => 70000, 'deut' => 55000, 'crew' => 120, 'power' => 180],
        ];
}

function fd_missionLabel(string $missionType): string {
        $labels = [
                'spy' => 'Spy Sweep',
                'expedition' => 'Deep Expedition',
                'raid' => 'Resource Raid',
                'patrol' => 'Defensive Patrol',
        ];
        return $labels[$missionType] ?? 'Fleet Mission';
}

$s->query("CREATE TABLE IF NOT EXISTS shipyard (
        uid INT NOT NULL PRIMARY KEY,
        level INT NOT NULL DEFAULT 1,
        mothership_bay INT NOT NULL DEFAULT 0,
        dock_efficiency INT NOT NULL DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$s->query("CREATE TABLE IF NOT EXISTS fleet (
        uid INT NOT NULL PRIMARY KEY,
        probe INT NOT NULL DEFAULT 0,
        light_fighter INT NOT NULL DEFAULT 0,
        heavy_fighter INT NOT NULL DEFAULT 0,
        cruiser INT NOT NULL DEFAULT 0,
        battleship INT NOT NULL DEFAULT 0,
        carrier INT NOT NULL DEFAULT 0,
        recycler INT NOT NULL DEFAULT 0,
        colony_ship INT NOT NULL DEFAULT 0,
        mothership INT NOT NULL DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$s->query("CREATE TABLE IF NOT EXISTS fleet_missions (
        mission_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        uid INT NOT NULL,
        mission_type VARCHAR(24) NOT NULL,
        ship_type VARCHAR(32) NOT NULL,
        ship_count INT NOT NULL DEFAULT 0,
        target_uid INT NOT NULL DEFAULT 0,
        duration_minutes INT NOT NULL DEFAULT 15,
        eta_at DATETIME NOT NULL,
        return_at DATETIME NOT NULL,
        status VARCHAR(16) NOT NULL DEFAULT 'enroute',
        reward_naquadah INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_uid_status (uid, status),
        INDEX idx_uid_eta (uid, eta_at),
        INDEX idx_uid_return (uid, return_at)
)");

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

$s->query("INSERT IGNORE INTO shipyard (uid) VALUES (" . $uid . ")");
$s->query("INSERT IGNORE INTO fleet (uid) VALUES (" . $uid . ")");
$s->query("INSERT IGNORE INTO player_resources (uid) VALUES (" . $uid . ")");

$shipyardTable = $s->query("SHOW TABLES LIKE 'shipyard'");
$fleetTable = $s->query("SHOW TABLES LIKE 'fleet'");
$missionTable = $s->query("SHOW TABLES LIKE 'fleet_missions'");
$dockBackendReady = ($shipyardTable && $shipyardTable->num_rows > 0 && $fleetTable && $fleetTable->num_rows > 0 && $missionTable && $missionTable->num_rows > 0);

if (!$dockBackendReady) {
        $status = "Shipyard backend tables are unavailable for this DB user. Contact an admin to grant table create privileges.";
}

$defs = fd_shipDefs();

if ($dockBackendReady) {
        $arrivals = $s->query("SELECT mission_id,mission_type,ship_type,ship_count,target_uid,reward_naquadah FROM fleet_missions WHERE uid=" . $uid . " AND status='enroute' AND eta_at <= NOW() ORDER BY mission_id ASC");
        if ($arrivals) {
                while ($mission = $arrivals->fetch_object()) {
                        $missionId = (int)$mission->mission_id;
                        $reward = 0;
                        if ($mission->mission_type === 'expedition') {
                                $reward = rand(5000, 65000);
                                $s->query("UPDATE bank SET onHand=onHand+" . $reward . " WHERE uid=" . $uid . " LIMIT 1");
                                $s->query("UPDATE fleet_missions SET reward_naquadah=" . $reward . " WHERE mission_id=" . $missionId . " AND uid=" . $uid . " LIMIT 1");
                        }
                        $s->query("UPDATE fleet_missions SET status='arrived' WHERE mission_id=" . $missionId . " AND uid=" . $uid . " LIMIT 1");
                        if ($status === '') {
                                $status = fd_missionLabel((string)$mission->mission_type) . " reached target " . (int)$mission->target_uid . ".";
                        }
                }
        }

        $returns = $s->query("SELECT mission_id,ship_type,ship_count,mission_type FROM fleet_missions WHERE uid=" . $uid . " AND status='arrived' AND return_at <= NOW() ORDER BY mission_id ASC");
        if ($returns) {
                while ($mission = $returns->fetch_object()) {
                        $missionId = (int)$mission->mission_id;
                        $shipType = (string)$mission->ship_type;
                        $shipCount = max(0, (int)$mission->ship_count);
                        if (isset($defs[$shipType]) && $shipCount > 0) {
                                $s->query("UPDATE fleet SET " . $shipType . "=" . $shipType . "+" . $shipCount . " WHERE uid=" . $uid . " LIMIT 1");
                        }
                        $s->query("UPDATE fleet_missions SET status='completed' WHERE mission_id=" . $missionId . " AND uid=" . $uid . " LIMIT 1");
                        if ($status === '') {
                                $status = fd_missionLabel((string)$mission->mission_type) . " returned to dock.";
                        }
                }
        }
}

if (isset($_GET['id']) && $_GET['id'] === 'upgrade_shipyard') {
        if (!$dockBackendReady) {
                $status = "Shipyard upgrade is unavailable until backend tables can be created.";
        } else {
                $yardQ = $s->query("SELECT level FROM shipyard WHERE uid=" . $uid . " LIMIT 1");
                $yard = $yardQ ? $yardQ->fetch_object() : (object)['level' => 1];
                $curr = (int)($yard->level ?? 1);
                $cost = 120000 * $curr;

                $bankQ = $s->query("SELECT onHand FROM bank WHERE uid=" . $uid . " LIMIT 1");
                $bank = $bankQ ? $bankQ->fetch_object() : (object)['onHand' => 0];
                if ((int)$bank->onHand >= $cost) {
                        $s->query("UPDATE bank SET onHand=onHand-" . (int)$cost . " WHERE uid=" . $uid . " LIMIT 1");
                        $s->query("UPDATE shipyard SET level=level+1 WHERE uid=" . $uid . " LIMIT 1");
                        $status = "Shipyard upgraded to level " . ($curr + 1) . ".";
                } else {
                        $status = "Insufficient Naquadah for shipyard upgrade.";
                }
        }
}

if (isset($_GET['id']) && $_GET['id'] === 'dispatch_mission') {
        if (!$dockBackendReady) {
                $status = "Mission dispatch is unavailable until backend tables can be created.";
        } else {
                $spec = isset($_GET['atype']) ? (string)$_GET['atype'] : '';
                $parts = explode('|', $spec);
                $missionType = isset($parts[0]) ? trim($parts[0]) : '';
                $shipType = isset($parts[1]) ? trim($parts[1]) : '';
                $targetUid = isset($parts[2]) ? (int)$parts[2] : 0;
                $shipCount = isset($parts[3]) ? (int)$parts[3] : 0;
                $durationMinutes = isset($parts[4]) ? (int)$parts[4] : 15;

                $allowedMissions = ['spy', 'expedition', 'raid', 'patrol'];
                if (!in_array($missionType, $allowedMissions, true)) {
                        $status = "Unknown mission type.";
                } elseif (!isset($defs[$shipType])) {
                        $status = "Unknown ship type for dispatch.";
                } elseif ($shipCount < 1) {
                        $status = "Dispatch requires at least one ship.";
                } else {
                        if ($durationMinutes < 5) {
                                $durationMinutes = 5;
                        }
                        if ($durationMinutes > 180) {
                                $durationMinutes = 180;
                        }

                        if ($targetUid <= 0) {
                                $targetUid = 1;
                        }
                        if ($targetUid === $uid) {
                                $targetUid = max(1, $uid - 1);
                        }

                        $fleetQ = $s->query("SELECT " . $shipType . " FROM fleet WHERE uid=" . $uid . " LIMIT 1");
                        $fleetLine = $fleetQ ? $fleetQ->fetch_object() : (object)[$shipType => 0];
                        $ownedShips = (int)($fleetLine->$shipType ?? 0);

                        if ($ownedShips < $shipCount) {
                                $status = "Insufficient available " . $defs[$shipType]['name'] . " for dispatch.";
                        } else {
                                $s->query("UPDATE fleet SET " . $shipType . "=" . $shipType . "-" . $shipCount . " WHERE uid=" . $uid . " LIMIT 1");
                                $safeMissionType = $s->real_escape_string($missionType);
                                $safeShipType = $s->real_escape_string($shipType);
                                $s->query("INSERT INTO fleet_missions (uid, mission_type, ship_type, ship_count, target_uid, duration_minutes, eta_at, return_at, status)
                                        VALUES (" . $uid . ", '" . $safeMissionType . "', '" . $safeShipType . "', " . $shipCount . ", " . $targetUid . ", " . $durationMinutes . ", DATE_ADD(NOW(), INTERVAL " . $durationMinutes . " MINUTE), DATE_ADD(NOW(), INTERVAL " . ($durationMinutes * 2) . " MINUTE), 'enroute')");
                                $status = fd_missionLabel($missionType) . " launched with " . fd_num($shipCount) . " " . $defs[$shipType]['name'] . ".";
                        }
                }
        }
}

if (isset($_GET['id']) && $_GET['id'] === 'upgrade_bay') {
        if (!$dockBackendReady) {
                $status = "Mothership bay upgrade is unavailable until backend tables can be created.";
        } else {
                $yardQ = $s->query("SELECT mothership_bay FROM shipyard WHERE uid=" . $uid . " LIMIT 1");
                $yard = $yardQ ? $yardQ->fetch_object() : (object)['mothership_bay' => 0];
                $curr = (int)($yard->mothership_bay ?? 0);
                $cost = 250000 * ($curr + 1);

                $bankQ = $s->query("SELECT onHand FROM bank WHERE uid=" . $uid . " LIMIT 1");
                $bank = $bankQ ? $bankQ->fetch_object() : (object)['onHand' => 0];
                if ((int)$bank->onHand >= $cost) {
                        $s->query("UPDATE bank SET onHand=onHand-" . (int)$cost . " WHERE uid=" . $uid . " LIMIT 1");
                        $s->query("UPDATE shipyard SET mothership_bay=mothership_bay+1 WHERE uid=" . $uid . " LIMIT 1");
                        $status = "Mothership bay upgraded to level " . ($curr + 1) . ".";
                } else {
                        $status = "Insufficient Naquadah for bay upgrade.";
                }
        }
}

if (!empty($_POST) && isset($_GET['id']) && $_GET['id'] === 'build_ship') {
        if (!$dockBackendReady) {
                $status = "Ship construction is unavailable until backend tables can be created.";
        } else {
                $defs = fd_shipDefs();
                $shipType = isset($_POST['shipType']) ? (string)$_POST['shipType'] : '';
                $amount = isset($_POST['amount']) ? (int)$_POST['amount'] : 0;
                if ($amount < 1) {
                        $amount = 1;
                }
                if ($amount > 5000) {
                        $amount = 5000;
                }

                if (!isset($defs[$shipType])) {
                        $status = "Unknown ship type.";
                } else {
                        $ship = $defs[$shipType];
                        $metal = (int)$ship['metal'] * $amount;
                        $crystal = (int)$ship['crystal'] * $amount;
                        $deut = (int)$ship['deut'] * $amount;
                        $totalCost = $metal + $crystal + $deut;
                        $crewCost = (int)$ship['crew'] * $amount;

                        $bankQ = $s->query("SELECT onHand FROM bank WHERE uid=" . $uid . " LIMIT 1");
                        $bank = $bankQ ? $bankQ->fetch_object() : (object)['onHand' => 0];

                        $unitQ = $s->query("SELECT untrained FROM units WHERE uid=" . $uid . " LIMIT 1");
                        $units = $unitQ ? $unitQ->fetch_object() : (object)['untrained' => 0];

                        $yardQ = $s->query("SELECT level,mothership_bay FROM shipyard WHERE uid=" . $uid . " LIMIT 1");
                        $yard = $yardQ ? $yardQ->fetch_object() : (object)['level' => 1, 'mothership_bay' => 0];

                        $queueCap = max(5, (int)$yard->level * 20);
                        if ($amount > $queueCap) {
                                $status = "Build limit exceeded for current shipyard level. Max per order: " . $queueCap . ".";
                        } elseif ($shipType === 'mothership' && (int)$yard->mothership_bay <= 0) {
                                $status = "Mothership bay level 1+ is required before building motherships.";
                        } elseif ((int)$units->untrained < $crewCost) {
                                $status = "Insufficient untrained units for crew assignment.";
                        } else {
                                $resQ = $s->query("SELECT metal,crystal,deuterium FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
                                $res = $resQ ? $resQ->fetch_object() : (object)['metal' => 0, 'crystal' => 0, 'deuterium' => 0];

                                if ((int)$res->metal < $metal || (int)$res->crystal < $crystal || (int)$res->deuterium < $deut) {
                                        $status = "Insufficient Metal/Crystal/Deuterium for construction.";
                                } else {
                                        $s->query("UPDATE player_resources SET metal=metal-" . (int)$metal . ", crystal=crystal-" . (int)$crystal . ", deuterium=deuterium-" . (int)$deut . " WHERE uid=" . $uid . " LIMIT 1");
                                        $s->query("UPDATE units SET untrained=untrained-" . (int)$crewCost . " WHERE uid=" . $uid . " LIMIT 1");
                                        $s->query("UPDATE fleet SET " . $shipType . "=" . $shipType . "+" . (int)$amount . " WHERE uid=" . $uid . " LIMIT 1");
                                        $status = "Construction complete: " . fd_num($amount) . " " . $ship['name'] . " built. Cost M" . fd_num($metal) . " C" . fd_num($crystal) . " D" . fd_num($deut) . ".";
                                }
                        }
                }
        }
}

$yardQ = $s->query("SELECT level,mothership_bay,dock_efficiency FROM shipyard WHERE uid=" . $uid . " LIMIT 1");
$yard = $yardQ ? $yardQ->fetch_object() : (object)['level' => 1, 'mothership_bay' => 0, 'dock_efficiency' => 0];

$fleetQ = $s->query("SELECT * FROM fleet WHERE uid=" . $uid . " LIMIT 1");
$fleet = $fleetQ ? $fleetQ->fetch_object() : (object)[];

$bankQ = $s->query("SELECT onHand,inBank FROM bank WHERE uid=" . $uid . " LIMIT 1");
$bank = $bankQ ? $bankQ->fetch_object() : (object)['onHand' => 0, 'inBank' => 0];

$unitQ = $s->query("SELECT untrained FROM units WHERE uid=" . $uid . " LIMIT 1");
$units = $unitQ ? $unitQ->fetch_object() : (object)['untrained' => 0];

$resQ = $s->query("SELECT metal,crystal,deuterium,food,water,population FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
$resources = $resQ ? $resQ->fetch_object() : (object)['metal' => 0, 'crystal' => 0, 'deuterium' => 0, 'food' => 0, 'water' => 0, 'population' => 0];

$fleetPower = 0;
$fleetCount = 0;
$recyclerCapacity = 0;
foreach ($defs as $key => $meta) {
        $qty = (int)($fleet->$key ?? 0);
        $fleetCount += $qty;
        $fleetPower += $qty * (int)$meta['power'];
        if ($key === 'recycler') {
                $recyclerCapacity += $qty * 20000;
        }
}

$missionsQ = $s->query("SELECT mission_id,mission_type,ship_type,ship_count,target_uid,duration_minutes,status,reward_naquadah,DATE_FORMAT(eta_at, '%Y-%m-%d %H:%i:%s') AS eta_time,DATE_FORMAT(return_at, '%Y-%m-%d %H:%i:%s') AS return_time
        FROM fleet_missions
        WHERE uid=" . $uid . " AND status IN ('enroute','arrived')
        ORDER BY mission_id DESC
        LIMIT 25");

$shipyardUpgradeCost = 120000 * (int)$yard->level;
$bayUpgradeCost = 250000 * ((int)$yard->mothership_bay + 1);
?>
<div class="page-hub">
    <div class="page-hub-head">
        <h3>Fleet Dock and Shipyard</h3>
        <p>Build starships, unlock mothership production, and stage expedition fleets.</p>
    </div>

    <?php if ($status !== '') { ?>
    <div class="card full"><strong><?= fd_h($status); ?></strong></div>
    <?php } ?>

    <div class="page-grid">
        <div class="card">
            <h4>Shipyard Status</h4>
            <p><strong>Shipyard Level:</strong> <?= fd_num((int)$yard->level); ?></p>
            <p><strong>Mothership Bay:</strong> <?= fd_num((int)$yard->mothership_bay); ?></p>
            <p><strong>Fleet Power Index:</strong> <?= fd_num($fleetPower); ?></p>
            <p><strong>Total Ships:</strong> <?= fd_num($fleetCount); ?></p>
            <p><strong>Recycler Capacity:</strong> <?= fd_num($recyclerCapacity); ?></p>
        </div>

        <div class="card">
            <h4>Strategic Resources</h4>
            <p><strong>On Hand:</strong> <?= fd_num((int)$bank->onHand); ?> Naquadah</p>
            <p><strong>In Bank:</strong> <?= fd_num((int)$bank->inBank); ?> Naquadah</p>
                        <p><strong>Metal:</strong> <?= fd_num((int)$resources->metal); ?></p>
                        <p><strong>Crystal:</strong> <?= fd_num((int)$resources->crystal); ?></p>
                        <p><strong>Deuterium:</strong> <?= fd_num((int)$resources->deuterium); ?></p>
                        <p><strong>Food:</strong> <?= fd_num((int)$resources->food); ?></p>
                        <p><strong>Water:</strong> <?= fd_num((int)$resources->water); ?></p>
                        <p><strong>Population:</strong> <?= fd_num((int)$resources->population); ?></p>
            <p><strong>Untrained Crew Pool:</strong> <?= fd_num((int)$units->untrained); ?></p>
            <p><a href="javascript:void(0)" onclick="sendData('bank','get','mainDisplay'); return false">Open Bank Module</a></p>
            <p><a href="javascript:void(0)" onclick="sendData('pages','get','universe','expedition'); return false">Open Expedition Control</a></p>
        </div>

        <div class="card full">
            <h4>Infrastructure Upgrades</h4>
            <p>
                Shipyard Upgrade Cost: <?= fd_num($shipyardUpgradeCost); ?> Naquadah |
                <a href="javascript:void(0)" onclick="sendData('fleetdock','get','upgrade_shipyard'); return false">Upgrade Shipyard</a>
            </p>
            <p>
                Mothership Bay Upgrade Cost: <?= fd_num($bayUpgradeCost); ?> Naquadah |
                <a href="javascript:void(0)" onclick="sendData('fleetdock','get','upgrade_bay'); return false">Upgrade Bay</a>
            </p>
            <p>Higher shipyard level increases max ships per build order. Mothership bay is required for mothership construction.</p>
        </div>

        <div class="card full">
            <h4>Build Starships</h4>
            <form action="javascript:void(0)" onsubmit="sendData('fleetdock','post','build_ship'); return false;">
                <table class="mini-table" border="0" width="100%">
                    <tr>
                        <th align="left">Ship</th>
                        <th align="left">Metal</th>
                        <th align="left">Crystal</th>
                        <th align="left">Deuterium</th>
                        <th align="left">Crew</th>
                        <th align="left">Combat</th>
                        <th align="left">Owned</th>
                    </tr>
                    <?php foreach ($defs as $k => $ship) { ?>
                    <tr>
                        <td><?= fd_h($ship['name']); ?> (<?= fd_h($k); ?>)</td>
                        <td><?= fd_num((int)$ship['metal']); ?></td>
                        <td><?= fd_num((int)$ship['crystal']); ?></td>
                        <td><?= fd_num((int)$ship['deut']); ?></td>
                        <td><?= fd_num((int)$ship['crew']); ?></td>
                        <td><?= fd_num((int)$ship['power']); ?></td>
                        <td><?= fd_num((int)($fleet->$k ?? 0)); ?></td>
                    </tr>
                    <?php } ?>
                </table>

                <p style="margin-top:10px;">
                    <label>Ship Type
                        <select name="shipType">
                            <?php foreach ($defs as $k => $ship) { ?>
                            <option value="<?= fd_h($k); ?>"><?= fd_h($ship['name']); ?></option>
                            <?php } ?>
                        </select>
                    </label>
                    <label style="margin-left:8px;">Amount
                        <input type="number" min="1" max="5000" name="amount" value="1" style="width:90px;" />
                    </label>
                    <input type="submit" value="Build Fleet" style="margin-left:8px;" />
                </p>
            </form>
        </div>

                <div class="card full">
                        <h4>Mission Control</h4>
                        <p>Launch fleets on spy, raid, patrol, and expedition loops. Ships return automatically after mission completion.</p>
                        <table class="mini-table" border="0" width="100%">
                                <tr>
                                        <th align="left">Mission</th>
                                        <th align="left">Preset</th>
                                        <th align="left">Action</th>
                                </tr>
                                <tr>
                                        <td>Spy Sweep</td>
                                        <td>3 Scout Probes to target UID 1 for 15m</td>
                                        <td><a href="javascript:void(0)" onclick="sendData('fleetdock','get','dispatch_mission','spy|probe|1|3|15'); return false;">Launch</a></td>
                                </tr>
                                <tr>
                                        <td>Deep Expedition</td>
                                        <td>8 Light Fighters to target UID 1 for 30m</td>
                                        <td><a href="javascript:void(0)" onclick="sendData('fleetdock','get','dispatch_mission','expedition|light_fighter|1|8|30'); return false;">Launch</a></td>
                                </tr>
                                <tr>
                                        <td>Resource Raid</td>
                                        <td>5 Cruisers to target UID 1 for 25m</td>
                                        <td><a href="javascript:void(0)" onclick="sendData('fleetdock','get','dispatch_mission','raid|cruiser|1|5|25'); return false;">Launch</a></td>
                                </tr>
                                <tr>
                                        <td>Defensive Patrol</td>
                                        <td>12 Heavy Fighters to target UID 1 for 20m</td>
                                        <td><a href="javascript:void(0)" onclick="sendData('fleetdock','get','dispatch_mission','patrol|heavy_fighter|1|12|20'); return false;">Launch</a></td>
                                </tr>
                        </table>

                        <h4 style="margin-top:12px;">Active Mission Queue</h4>
                        <table class="mini-table" border="0" width="100%">
                                <tr>
                                        <th align="left">Mission</th>
                                        <th align="left">Fleet</th>
                                        <th align="left">Target</th>
                                        <th align="left">ETA</th>
                                        <th align="left">Return</th>
                                        <th align="left">Status</th>
                                        <th align="left">Reward</th>
                                </tr>
                                <?php
                                $hasRows = false;
                                if ($missionsQ) {
                                                while ($m = $missionsQ->fetch_object()) {
                                                                $hasRows = true;
                                                                ?>
                                <tr>
                                        <td><?= fd_h(fd_missionLabel((string)$m->mission_type)); ?></td>
                                        <td><?= fd_num((int)$m->ship_count); ?> <?= fd_h((string)$m->ship_type); ?></td>
                                        <td>UID <?= fd_num((int)$m->target_uid); ?></td>
                                        <td><?= fd_h((string)$m->eta_time); ?></td>
                                        <td><?= fd_h((string)$m->return_time); ?></td>
                                        <td><?= fd_h((string)$m->status); ?></td>
                                        <td><?= fd_num((int)$m->reward_naquadah); ?></td>
                                </tr>
                                                                <?php
                                                }
                                }
                                if (!$hasRows) {
                                                ?>
                                <tr>
                                        <td colspan="7">No active missions. Launch a preset dispatch above.</td>
                                </tr>
                                                <?php
                                }
                                ?>
                        </table>
                </div>

        <div class="card full">
            <h4>Mothership and Shipyard Logic</h4>
            <ul>
                <li>Every starship order consumes Naquadah and untrained units as crew.</li>
                <li>Shipyard level determines max ships per order: level × 20 (minimum 5).</li>
                <li>Mothership construction requires bay level 1 or higher.</li>
                <li>Recycler count defines debris recovery throughput for expedition loops.</li>
                                <li>Active mission queue tracks travel ETA and automatic ship returns.</li>
            </ul>
        </div>
    </div>
</div>
<?php
echo "Query Count: " . $s->queryCount . "<br>";
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());
?>