<?php
include("../config.php");
require_once __DIR__ . '/../base/FleetDockMissionPolicy.class.php';

$pagegen = new page_gen();
$pagegen->round_to = 4;
$pagegen->start();

$s = new Game();
if (!$s->loggedIn || empty($_GET['time'])) {
    header("Location: ../index.php"); exit;
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

function fd_safeToken(string $value): string {
        return preg_replace('/[^A-Za-z0-9 _:-]/', '', $value) ?? '';
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

function fd_starshipCatalog(): array {
        $families = ['Aegis', 'Nova', 'Vanguard', 'Tempest', 'Orion', 'Helios', 'Nyx', 'Atlas', 'Leviathan', 'Draco'];
        $roles = [
                ['name' => 'Scout', 'class' => 'D', 'sub' => 'I', 'type' => 'Corvette', 'subtype' => 'Recon', 'title' => 'Pathfinder Wing'],
                ['name' => 'Frigate', 'class' => 'E', 'sub' => 'I', 'type' => 'Frigate', 'subtype' => 'Patrol', 'title' => 'Frontier Guard'],
                ['name' => 'Destroyer', 'class' => 'F', 'sub' => 'II', 'type' => 'Destroyer', 'subtype' => 'Assault', 'title' => 'Breakline Division'],
                ['name' => 'Cruiser', 'class' => 'G', 'sub' => 'II', 'type' => 'Cruiser', 'subtype' => 'Siege', 'title' => 'Hammer Spear'],
                ['name' => 'Battlecruiser', 'class' => 'G', 'sub' => 'IV', 'type' => 'Battlecruiser', 'subtype' => 'Command', 'title' => 'Vanguard Command'],
                ['name' => 'Carrier', 'class' => 'H', 'sub' => 'I', 'type' => 'Carrier', 'subtype' => 'Expedition', 'title' => 'Long Reach Group'],
                ['name' => 'Dreadnought', 'class' => 'I', 'sub' => 'II', 'type' => 'Dreadnought', 'subtype' => 'Fortress', 'title' => 'Iron Bastion'],
                ['name' => 'Titan', 'class' => 'J', 'sub' => 'III', 'type' => 'Titan', 'subtype' => 'World Ender', 'title' => 'Apex Lance'],
                ['name' => 'Mothership', 'class' => 'L', 'sub' => 'IV', 'type' => 'Mothership', 'subtype' => 'Sovereign', 'title' => 'Throne Platform'],
        ];
        $legacyMap = [
                'Scout' => 'probe',
                'Frigate' => 'light_fighter',
                'Destroyer' => 'heavy_fighter',
                'Cruiser' => 'cruiser',
                'Battlecruiser' => 'battleship',
                'Carrier' => 'carrier',
                'Dreadnought' => 'recycler',
                'Titan' => 'colony_ship',
                'Mothership' => 'mothership',
        ];

        $catalog = [];
        $id = 1;
        foreach ($families as $fi => $family) {
                foreach ($roles as $ri => $role) {
                        $tier = $ri + 1;
                        $metal = (int)(3200 + ($tier * 4600) + ($fi * 850));
                        $crystal = (int)(2100 + ($tier * 3400) + ($fi * 620));
                        $deut = (int)(1200 + ($tier * 2300) + ($fi * 410));
                        $food = (int)(650 + ($tier * 420));
                        $water = (int)(620 + ($tier * 390));
                        $pop = (int)(26 + ($tier * 16));
                        $crew = (int)(8 + ($tier * 18) + ($fi * 2));
                        $power = (int)(20 + ($tier * 28) + ($fi * 3));
                        $atk = (int)round($power * 1.25);
                        $def = (int)round($power * 1.10);
                        $shield = (int)(60 + ($tier * 75) + ($fi * 9));
                        $speed = max(900, (int)(19500 - ($tier * 1700) - ($fi * 180)));
                        $cargo = (int)(1400 + ($tier * 3600) + ($fi * 430));
                        $sys = (int)(12 + ($tier * 7));
                        $warp = (int)(10 + ($tier * 8));
                        $code = 'SGS-' . str_pad((string)$id, 3, '0', STR_PAD_LEFT);
                        $name = $family . ' ' . $role['name'];
                        $title = $role['title'] . ' ' . $family;

                        $catalog[] = [
                                'starship_id' => $id,
                                'ship_code' => $code,
                                'ship_name' => $name,
                                'ship_title' => $title,
                                'class_letter' => $role['class'],
                                'class_subclass' => $role['sub'],
                                'ship_type' => $role['type'],
                                'ship_subtype' => $role['subtype'],
                                'family_name' => $family,
                                'tier' => $tier,
                                'metal_cost' => $metal,
                                'crystal_cost' => $crystal,
                                'deut_cost' => $deut,
                                'food_cost' => $food,
                                'water_cost' => $water,
                                'pop_cost' => $pop,
                                'crew_required' => $crew,
                                'power_rating' => $power,
                                'attack_stat' => $atk,
                                'defense_stat' => $def,
                                'shield_stat' => $shield,
                                'speed_stat' => $speed,
                                'cargo_stat' => $cargo,
                                'systems_stat' => $sys,
                                'warp_stat' => $warp,
                                'legacy_key' => ($fi === 0) ? ($legacyMap[$role['name']] ?? '') : '',
                        ];
                        $id++;
                }
        }
        return $catalog;
}

function fd_seedStarshipCatalog(Game $s, int $uid, array $catalog): void {
        $s->query("CREATE TABLE IF NOT EXISTS shipyard_starship_catalog (
                starship_id INT NOT NULL PRIMARY KEY,
                ship_code VARCHAR(16) NOT NULL,
                ship_name VARCHAR(120) NOT NULL,
                ship_title VARCHAR(140) NOT NULL,
                class_letter VARCHAR(4) NOT NULL DEFAULT 'D',
                class_subclass VARCHAR(8) NOT NULL DEFAULT 'I',
                ship_type VARCHAR(40) NOT NULL,
                ship_subtype VARCHAR(60) NOT NULL,
                family_name VARCHAR(60) NOT NULL,
                tier INT NOT NULL DEFAULT 1,
                metal_cost INT NOT NULL DEFAULT 0,
                crystal_cost INT NOT NULL DEFAULT 0,
                deut_cost INT NOT NULL DEFAULT 0,
                food_cost INT NOT NULL DEFAULT 0,
                water_cost INT NOT NULL DEFAULT 0,
                pop_cost INT NOT NULL DEFAULT 0,
                crew_required INT NOT NULL DEFAULT 0,
                power_rating INT NOT NULL DEFAULT 0,
                attack_stat INT NOT NULL DEFAULT 0,
                defense_stat INT NOT NULL DEFAULT 0,
                shield_stat INT NOT NULL DEFAULT 0,
                speed_stat INT NOT NULL DEFAULT 0,
                cargo_stat INT NOT NULL DEFAULT 0,
                systems_stat INT NOT NULL DEFAULT 0,
                warp_stat INT NOT NULL DEFAULT 0,
                legacy_key VARCHAR(32) NOT NULL DEFAULT '',
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");

        $s->query("CREATE TABLE IF NOT EXISTS player_starship_owned (
                uid INT NOT NULL,
                starship_id INT NOT NULL,
                quantity INT NOT NULL DEFAULT 0,
                total_power BIGINT NOT NULL DEFAULT 0,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY(uid, starship_id)
        )");

        foreach ($catalog as $ship) {
                $id = (int)$ship['starship_id'];
                $code = fd_safeToken((string)$ship['ship_code']);
                $name = fd_safeToken((string)$ship['ship_name']);
                $title = fd_safeToken((string)$ship['ship_title']);
                $cls = fd_safeToken((string)$ship['class_letter']);
                $sub = fd_safeToken((string)$ship['class_subclass']);
                $type = fd_safeToken((string)$ship['ship_type']);
                $subtype = fd_safeToken((string)$ship['ship_subtype']);
                $family = fd_safeToken((string)$ship['family_name']);
                $legacy = fd_safeToken((string)$ship['legacy_key']);
                $s->query("REPLACE INTO shipyard_starship_catalog (starship_id, ship_code, ship_name, ship_title, class_letter, class_subclass, ship_type, ship_subtype, family_name, tier, metal_cost, crystal_cost, deut_cost, food_cost, water_cost, pop_cost, crew_required, power_rating, attack_stat, defense_stat, shield_stat, speed_stat, cargo_stat, systems_stat, warp_stat, legacy_key)
                        VALUES (" . $id . ", '" . $code . "', '" . $name . "', '" . $title . "', '" . $cls . "', '" . $sub . "', '" . $type . "', '" . $subtype . "', '" . $family . "', " . (int)$ship['tier'] . ", " . (int)$ship['metal_cost'] . ", " . (int)$ship['crystal_cost'] . ", " . (int)$ship['deut_cost'] . ", " . (int)$ship['food_cost'] . ", " . (int)$ship['water_cost'] . ", " . (int)$ship['pop_cost'] . ", " . (int)$ship['crew_required'] . ", " . (int)$ship['power_rating'] . ", " . (int)$ship['attack_stat'] . ", " . (int)$ship['defense_stat'] . ", " . (int)$ship['shield_stat'] . ", " . (int)$ship['speed_stat'] . ", " . (int)$ship['cargo_stat'] . ", " . (int)$ship['systems_stat'] . ", " . (int)$ship['warp_stat'] . ", '" . $legacy . "')");
                $s->query("INSERT IGNORE INTO player_starship_owned (uid, starship_id) VALUES (" . (int)$uid . ", " . $id . ")");
        }
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
        outcome VARCHAR(32) NOT NULL DEFAULT 'pending',
        outcome_text VARCHAR(255) NOT NULL DEFAULT '',
        intel_json TEXT NOT NULL,
        loot_metal BIGINT NOT NULL DEFAULT 0,
        loot_crystal BIGINT NOT NULL DEFAULT 0,
        loot_deuterium BIGINT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_uid_status (uid, status),
        INDEX idx_uid_eta (uid, eta_at),
        INDEX idx_uid_return (uid, return_at)
)" );
$s->query("ALTER TABLE fleet_missions ADD COLUMN IF NOT EXISTS outcome VARCHAR(32) NOT NULL DEFAULT 'pending', ADD COLUMN IF NOT EXISTS outcome_text VARCHAR(255) NOT NULL DEFAULT '', ADD COLUMN IF NOT EXISTS intel_json TEXT NOT NULL, ADD COLUMN IF NOT EXISTS loot_metal BIGINT NOT NULL DEFAULT 0, ADD COLUMN IF NOT EXISTS loot_crystal BIGINT NOT NULL DEFAULT 0, ADD COLUMN IF NOT EXISTS loot_deuterium BIGINT NOT NULL DEFAULT 0");
$s->query("CREATE TABLE IF NOT EXISTS fleet_patrol_state (uid INT NOT NULL PRIMARY KEY, defense_bonus INT NOT NULL DEFAULT 0, expires_at DATETIME NULL, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)");

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
$starshipCatalog = fd_starshipCatalog();
fd_seedStarshipCatalog($s, $uid, $starshipCatalog);

$dockView = 'overview';
if (isset($_GET['id']) && $_GET['id'] === 'mainDisplay' && isset($_GET['atype'])) {
        $dockView = preg_replace('/[^a-z]/', '', strtolower((string)$_GET['atype'])) ?: 'overview';
}
if (!in_array($dockView, ['overview', 'catalog', 'classes', 'types', 'build'], true)) {
        $dockView = 'overview';
}

if ($dockBackendReady) {
        $arrivals = $s->query("SELECT mission_id,mission_type,ship_type,ship_count,target_uid,reward_naquadah FROM fleet_missions WHERE uid=" . $uid . " AND status='enroute' AND eta_at <= NOW() ORDER BY mission_id ASC");
        if ($arrivals) while ($mission = $arrivals->fetch_object()) {
                $missionId=(int)$mission->mission_id; $target=(int)$mission->target_uid; $ships=max(1,(int)$mission->ship_count); $targetPower=0; $metal=0; $crystal=0; $deut=0;
                $targetFleet=$s->query("SELECT probe,light_fighter,heavy_fighter,cruiser,battleship,carrier,recycler,colony_ship,mothership FROM fleet WHERE uid=".$target." LIMIT 1");
                if($targetFleet){$targetRow=$targetFleet->fetch_assoc();foreach($targetRow as $v)$targetPower+=(int)$v;}
                $targetRes=$s->query("SELECT metal,crystal,deuterium FROM player_resources WHERE uid=".$target." LIMIT 1");
                if($targetRes&&($rr=$targetRes->fetch_assoc())){$metal=(int)$rr['metal'];$crystal=(int)$rr['crystal'];$deut=(int)$rr['deuterium'];}
                $result=FleetDockMissionPolicy::outcome((string)$mission->mission_type,$ships,$targetPower,$metal,$crystal,$deut);$intelJson=$s->real_escape_string(json_encode($result['intel'],JSON_THROW_ON_ERROR));$text=$s->real_escape_string((string)$result['text']);$outcome=$result['success']?'success':'failed';
                $s->begin_transaction();$ok=true;
                if((int)$result['reward_naquadah']>0)$ok=$ok&&$s->query("UPDATE bank SET onHand=onHand+".(int)$result['reward_naquadah']." WHERE uid=".$uid." LIMIT 1");
                if(array_sum($result['loot'])>0&&$result['success']){$loot=$result['loot'];$ok=$ok&&$s->query("UPDATE player_resources SET metal=GREATEST(0,metal-".(int)$loot['metal'].") ,crystal=GREATEST(0,crystal-".(int)$loot['crystal']."),deuterium=GREATEST(0,deuterium-".(int)$loot['deuterium'].") WHERE uid=".$target." LIMIT 1");$ok=$ok&&$s->query("UPDATE player_resources SET metal=metal+".(int)$loot['metal'].",crystal=crystal+".(int)$loot['crystal'].",deuterium=deuterium+".(int)$loot['deuterium']." WHERE uid=".$uid." LIMIT 1");}
                if((int)$result['patrol_bonus']>0)$ok=$ok&&$s->query("INSERT INTO fleet_patrol_state(uid,defense_bonus,expires_at) VALUES(".$uid.",".(int)$result['patrol_bonus'].",DATE_ADD(NOW(),INTERVAL ".max(15,(int)$mission->duration_minutes)." MINUTE)) ON DUPLICATE KEY UPDATE defense_bonus=GREATEST(defense_bonus,VALUES(defense_bonus)),expires_at=VALUES(expires_at)");
                $ok=$ok&&$s->query("UPDATE fleet_missions SET status='arrived',outcome='".$outcome."',outcome_text='".$text."',intel_json='".$intelJson."',reward_naquadah=".(int)$result['reward_naquadah'].",loot_metal=".(int)$result['loot']['metal'].",loot_crystal=".(int)$result['loot']['crystal'].",loot_deuterium=".(int)$result['loot']['deuterium']." WHERE mission_id=".$missionId." AND uid=".$uid." AND status='enroute' LIMIT 1");
                if($ok)$s->commit();else$s->rollback();if($status==='')$status=$result['text'];
        }
        $returns=$s->query("SELECT mission_id,ship_type,ship_count,mission_type FROM fleet_missions WHERE uid=".$uid." AND status='arrived' AND return_at<=NOW() ORDER BY mission_id ASC");
        if($returns)while($mission=$returns->fetch_object()){$missionId=(int)$mission->mission_id;$shipType=(string)$mission->ship_type;$shipCount=max(0,(int)$mission->ship_count);$s->begin_transaction();$ok=true;if(isset($defs[$shipType])&&$shipCount>0)$ok=$s->query("UPDATE fleet SET ".$shipType."=".$shipType."+".$shipCount." WHERE uid=".$uid." LIMIT 1");$ok=$ok&&$s->query("UPDATE fleet_missions SET status='completed' WHERE mission_id=".$missionId." AND uid=".$uid." AND status='arrived' LIMIT 1");if($ok)$s->commit();else$s->rollback();if($status==='')$status=fd_missionLabel((string)$mission->mission_type)." returned to dock.";}
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

                        $s->begin_transaction();
                        $fleetQ = $s->query("SELECT " . $shipType . " FROM fleet WHERE uid=" . $uid . " FOR UPDATE");
                        $fleetLine = $fleetQ ? $fleetQ->fetch_object() : (object)[$shipType => 0];
                        $ownedShips = (int)($fleetLine->$shipType ?? 0);
                        if ($ownedShips < $shipCount) {
                                $s->rollback();
                                $status = "Insufficient available " . $defs[$shipType]['name'] . " for dispatch.";
                        } else {
                                $safeMissionType = fd_safeToken($missionType); $safeShipType = fd_safeToken($shipType);
                                $reserved = $s->query("UPDATE fleet SET " . $shipType . "=" . $shipType . "-" . $shipCount . " WHERE uid=" . $uid . " AND " . $shipType . ">=" . $shipCount . " LIMIT 1");
                                $inserted = $reserved && $s->affected_rows === 1 && $s->query("INSERT INTO fleet_missions (uid, mission_type, ship_type, ship_count, target_uid, duration_minutes, eta_at, return_at, status, outcome, intel_json)
                                        VALUES (" . $uid . ", '" . $safeMissionType . "', '" . $safeShipType . "', " . $shipCount . ", " . $targetUid . ", " . $durationMinutes . ", DATE_ADD(NOW(), INTERVAL " . $durationMinutes . " MINUTE), DATE_ADD(NOW(), INTERVAL " . ($durationMinutes * 2) . " MINUTE), 'enroute', 'pending', '{}')");
                                if ($inserted) { $s->commit(); $status = fd_missionLabel($missionType) . " launched with " . fd_num($shipCount) . " " . $defs[$shipType]['name'] . "."; } else { $s->rollback(); $status = 'Mission launch failed safely; no ships were lost.'; }
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

                        $yardQ = $s->query("SELECT level,mothership_bay,dock_efficiency FROM shipyard WHERE uid=" . $uid . " LIMIT 1");
                        $yard = $yardQ ? $yardQ->fetch_object() : (object)['level' => 1, 'mothership_bay' => 0, 'dock_efficiency' => 0];

                        $queueCap = max(5, (int)$yard->level * 20);
                        $efficiency = max(0, (int)$yard->dock_efficiency);
                        $materialFactor = max(0.72, 1 - min(0.28, $efficiency * 0.01));
                        $crewFactor = max(0.80, 1 - min(0.20, $efficiency * 0.006));
                        $metal = (int)round($metal * $materialFactor);
                        $crystal = (int)round($crystal * $materialFactor);
                        $deut = (int)round($deut * $materialFactor);
                        $crewCost = (int)round($crewCost * $crewFactor);
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
                                        $status = "Construction complete: " . fd_num($amount) . " " . $ship['name'] . " built. Cost M" . fd_num($metal) . " C" . fd_num($crystal) . " D" . fd_num($deut) . " | Dock Efficiency " . fd_num($efficiency) . ".";
                                }
                        }
                }
        }
}

if (!empty($_POST) && isset($_GET['id']) && $_GET['id'] === 'dock_action') {
        if (!$dockBackendReady) {
                $status = "Dock action is unavailable until backend tables can be created.";
        } else {
                $actionKey = isset($_POST['actionKey']) ? preg_replace('/[^a-z_]/', '', strtolower((string)$_POST['actionKey'])) : '';
                $yardQ = $s->query("SELECT level,mothership_bay,dock_efficiency FROM shipyard WHERE uid=" . $uid . " LIMIT 1");
                $yard = $yardQ ? $yardQ->fetch_object() : (object)['level' => 1, 'mothership_bay' => 0, 'dock_efficiency' => 0];
                $resQ = $s->query("SELECT metal,crystal,deuterium,food,water,population FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
                $res = $resQ ? $resQ->fetch_object() : (object)['metal' => 0, 'crystal' => 0, 'deuterium' => 0, 'food' => 0, 'water' => 0, 'population' => 0];
                $bankQ = $s->query("SELECT onHand FROM bank WHERE uid=" . $uid . " LIMIT 1");
                $bank = $bankQ ? $bankQ->fetch_object() : (object)['onHand' => 0];

                if ($actionKey === 'calibrate_dock') {
                        $eff = max(0, (int)$yard->dock_efficiency);
                        $needM = 15000 + ($eff * 4000);
                        $needC = 9000 + ($eff * 2600);
                        $needD = 5000 + ($eff * 1700);
                        if ((int)$res->metal < $needM || (int)$res->crystal < $needC || (int)$res->deuterium < $needD) {
                                $status = "Calibration failed: insufficient Metal/Crystal/Deuterium.";
                        } elseif ($eff >= 40) {
                                $status = "Dock efficiency is already at tactical cap (40).";
                        } else {
                                $s->query("UPDATE player_resources SET metal=metal-" . $needM . ", crystal=crystal-" . $needC . ", deuterium=deuterium-" . $needD . " WHERE uid=" . $uid . " LIMIT 1");
                                $s->query("UPDATE shipyard SET dock_efficiency=dock_efficiency+1 WHERE uid=" . $uid . " LIMIT 1");
                                $status = "Dock calibration complete. Efficiency increased to " . fd_num($eff + 1) . ".";
                        }
                } elseif ($actionKey === 'crew_requisition') {
                        $yardLevel = max(1, (int)$yard->level);
                        $needNaq = 60000 + ($yardLevel * 12000);
                        $crewGain = 420 + ($yardLevel * 140);
                        if ((int)$bank->onHand < $needNaq) {
                                $status = "Crew requisition failed: insufficient Naquadah.";
                        } else {
                                $s->query("UPDATE bank SET onHand=onHand-" . $needNaq . " WHERE uid=" . $uid . " LIMIT 1");
                                $s->query("UPDATE units SET untrained=untrained+" . $crewGain . " WHERE uid=" . $uid . " LIMIT 1");
                                $status = "Crew requisition successful: +" . fd_num($crewGain) . " untrained personnel added.";
                        }
                } elseif ($actionKey === 'supply_refit') {
                        $needFood = 12000;
                        $needWater = 12000;
                        $needPop = 2500;
                        if ((int)$res->food < $needFood || (int)$res->water < $needWater || (int)$res->population < $needPop) {
                                $status = "Supply refit failed: requires Food, Water, and Population reserves.";
                        } else {
                                $s->query("UPDATE player_resources SET food=food-" . $needFood . ", water=water-" . $needWater . ", population=population-" . $needPop . " WHERE uid=" . $uid . " LIMIT 1");
                                $s->query("UPDATE shipyard SET dock_efficiency=LEAST(40, dock_efficiency+2) WHERE uid=" . $uid . " LIMIT 1");
                                $status = "Supply refit completed: temporary staffing boost applied (+2 dock efficiency, capped at 40).";
                        }
                } else {
                        $status = "Unknown dock action.";
                }
        }
}

if (!empty($_POST) && isset($_GET['id']) && $_GET['id'] === 'build_starship90') {
        if (!$dockBackendReady) {
                $status = "Starship factory is unavailable until backend tables can be created.";
        } else {
                $starshipId = isset($_POST['starshipId']) ? (int)$_POST['starshipId'] : 0;
                $amount = isset($_POST['amount']) ? (int)$_POST['amount'] : 1;
                if ($amount < 1) {
                        $amount = 1;
                }
                if ($amount > 500) {
                        $amount = 500;
                }

                $shipQ = $s->query("SELECT * FROM shipyard_starship_catalog WHERE starship_id=" . $starshipId . " LIMIT 1");
                $ship = $shipQ ? $shipQ->fetch_object() : null;
                if (!$ship) {
                        $status = "Starship catalog entry not found.";
                } else {
                        $yardQ = $s->query("SELECT level,mothership_bay,dock_efficiency FROM shipyard WHERE uid=" . $uid . " LIMIT 1");
                        $yard = $yardQ ? $yardQ->fetch_object() : (object)['level' => 1, 'mothership_bay' => 0, 'dock_efficiency' => 0];
                        $queueCap = max(12, (int)$yard->level * 25);
                        $efficiency = max(0, (int)$yard->dock_efficiency);
                        $materialFactor = max(0.72, 1 - min(0.28, $efficiency * 0.01));
                        $crewFactor = max(0.80, 1 - min(0.20, $efficiency * 0.006));

                        $needMetal = (int)round(((int)$ship->metal_cost * $amount) * $materialFactor);
                        $needCrystal = (int)round(((int)$ship->crystal_cost * $amount) * $materialFactor);
                        $needDeut = (int)round(((int)$ship->deut_cost * $amount) * $materialFactor);
                        $needFood = (int)round(((int)$ship->food_cost * $amount) * $materialFactor);
                        $needWater = (int)round(((int)$ship->water_cost * $amount) * $materialFactor);
                        $needPop = (int)round(((int)$ship->pop_cost * $amount) * $materialFactor);
                        $needCrew = (int)round(((int)$ship->crew_required * $amount) * $crewFactor);

                        $resQ = $s->query("SELECT metal,crystal,deuterium,food,water,population FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
                        $res = $resQ ? $resQ->fetch_object() : (object)['metal' => 0, 'crystal' => 0, 'deuterium' => 0, 'food' => 0, 'water' => 0, 'population' => 0];
                        $unitQ = $s->query("SELECT untrained FROM units WHERE uid=" . $uid . " LIMIT 1");
                        $units = $unitQ ? $unitQ->fetch_object() : (object)['untrained' => 0];

                        if ($amount > $queueCap) {
                                $status = "Construction queue cap exceeded. Current max order is " . fd_num($queueCap) . ".";
                        } elseif ((string)$ship->ship_type === 'Mothership' && (int)$yard->mothership_bay < 1) {
                                $status = "Mothership Bay level 1+ is required for sovereign-class construction.";
                        } elseif ((int)$res->metal < $needMetal || (int)$res->crystal < $needCrystal || (int)$res->deuterium < $needDeut || (int)$res->food < $needFood || (int)$res->water < $needWater || (int)$res->population < $needPop) {
                                $status = "Insufficient strategic resources for this starship order.";
                        } elseif ((int)$units->untrained < $needCrew) {
                                $status = "Insufficient untrained unit pool for crew assignment.";
                        } else {
                                $s->query("UPDATE player_resources SET metal=metal-" . $needMetal . ", crystal=crystal-" . $needCrystal . ", deuterium=deuterium-" . $needDeut . ", food=food-" . $needFood . ", water=water-" . $needWater . ", population=population-" . $needPop . " WHERE uid=" . $uid . " LIMIT 1");
                                $s->query("UPDATE units SET untrained=untrained-" . $needCrew . " WHERE uid=" . $uid . " LIMIT 1");
                                $s->query("UPDATE player_starship_owned SET quantity=quantity+" . $amount . ", total_power=total_power+" . ((int)$ship->power_rating * $amount) . " WHERE uid=" . $uid . " AND starship_id=" . (int)$ship->starship_id . " LIMIT 1");

                                $legacyKey = (string)($ship->legacy_key ?? '');
                                if ($legacyKey !== '' && isset($defs[$legacyKey])) {
                                        $s->query("UPDATE fleet SET " . $legacyKey . "=" . $legacyKey . "+" . $amount . " WHERE uid=" . $uid . " LIMIT 1");
                                }

                                $status = "Starship construction complete: " . fd_num($amount) . " x " . (string)$ship->ship_name . " [" . (string)$ship->ship_code . "] with efficiency " . fd_num($efficiency) . ".";
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

$missionsQ = $s->query("SELECT mission_id,mission_type,ship_type,ship_count,target_uid,duration_minutes,status,reward_naquadah,outcome,outcome_text,intel_json,loot_metal,loot_crystal,loot_deuterium,DATE_FORMAT(eta_at, '%Y-%m-%d %H:%i:%s') AS eta_time,DATE_FORMAT(return_at, '%Y-%m-%d %H:%i:%s') AS return_time
        FROM fleet_missions
        WHERE uid=" . $uid . " AND status IN ('enroute','arrived')
        ORDER BY mission_id DESC
        LIMIT 25");

$starshipRows = [];
$starshipOwned = [];
$classGroups = [];
$typeGroups = [];

$ownedQ = $s->query("SELECT starship_id, quantity, total_power FROM player_starship_owned WHERE uid=" . $uid);
if ($ownedQ) {
        while ($row = $ownedQ->fetch_assoc()) {
                $starshipOwned[(int)$row['starship_id']] = [
                        'quantity' => (int)$row['quantity'],
                        'total_power' => (int)$row['total_power'],
                ];
        }
}

$catalogQ = $s->query("SELECT * FROM shipyard_starship_catalog ORDER BY tier ASC, family_name ASC, starship_id ASC");
if ($catalogQ) {
        while ($row = $catalogQ->fetch_assoc()) {
                $sid = (int)$row['starship_id'];
                $owned = $starshipOwned[$sid]['quantity'] ?? 0;
                $row['owned_qty'] = $owned;
                $starshipRows[] = $row;

                $classKey = (string)$row['class_letter'] . '-' . (string)$row['class_subclass'];
                if (!isset($classGroups[$classKey])) {
                        $classGroups[$classKey] = ['count' => 0, 'avg_power' => 0, 'avg_attack' => 0, 'avg_defense' => 0, 'sample' => []];
                }
                $classGroups[$classKey]['count']++;
                $classGroups[$classKey]['avg_power'] += (int)$row['power_rating'];
                $classGroups[$classKey]['avg_attack'] += (int)$row['attack_stat'];
                $classGroups[$classKey]['avg_defense'] += (int)$row['defense_stat'];
                if (count($classGroups[$classKey]['sample']) < 3) {
                        $classGroups[$classKey]['sample'][] = (string)$row['ship_name'];
                }

                $typeKey = (string)$row['ship_type'] . ' / ' . (string)$row['ship_subtype'];
                if (!isset($typeGroups[$typeKey])) {
                        $typeGroups[$typeKey] = ['count' => 0, 'avg_speed' => 0, 'avg_cargo' => 0, 'avg_shield' => 0, 'sample' => []];
                }
                $typeGroups[$typeKey]['count']++;
                $typeGroups[$typeKey]['avg_speed'] += (int)$row['speed_stat'];
                $typeGroups[$typeKey]['avg_cargo'] += (int)$row['cargo_stat'];
                $typeGroups[$typeKey]['avg_shield'] += (int)$row['shield_stat'];
                if (count($typeGroups[$typeKey]['sample']) < 3) {
                        $typeGroups[$typeKey]['sample'][] = (string)$row['ship_name'];
                }
        }
}

$shipyardUpgradeCost = 120000 * (int)$yard->level;
$bayUpgradeCost = 250000 * ((int)$yard->mothership_bay + 1);
?>
<div class="page-hub">
    <div class="page-hub-head">
        <h3>Fleet Dock and Shipyard</h3>
                <p>Build starships, unlock mothership production, and manage a 90-title strategic ship catalog with full class and stat systems.</p>
    </div>

    <?php if ($status !== '') { ?>
    <div class="card full"><strong><?= fd_h($status); ?></strong></div>
    <?php } ?>

        <div class="card full">
                <h4>Fleet Function Buttons</h4>
                <div class="page-subnav feature-subnav">
                        <a href="javascript:void(0)" onclick="sendData('fleetdock','get','mainDisplay','overview'); return false">Overview Panel</a>
                        <a href="javascript:void(0)" onclick="sendData('fleetdock','get','mainDisplay','build'); return false">Build Factory</a>
                        <a href="javascript:void(0)" onclick="sendData('fleetdock','get','mainDisplay','catalog'); return false">Catalog 90</a>
                        <a href="javascript:void(0)" onclick="sendData('fleetdock','get','mainDisplay','classes'); return false">Class Matrix</a>
                        <a href="javascript:void(0)" onclick="sendData('fleetdock','get','mainDisplay','types'); return false">Type Matrix</a>
                </div>

                <details>
                        <summary>Shipyard Functions (Sub Buttons)</summary>
                        <div class="page-subnav feature-subnav">
                                <a href="javascript:void(0)" onclick="sendData('fleetdock','get','upgrade_shipyard'); return false">Upgrade Shipyard</a>
                                <a href="javascript:void(0)" onclick="sendData('fleetdock','get','upgrade_bay'); return false">Upgrade Mothership Bay</a>
                                <a href="javascript:void(0)" onclick="sendData('fleetdock','post','dock_action'); return false">Run Dock Action</a>
                        </div>
                </details>

                <details>
                        <summary>Mission Functions (Sub Buttons)</summary>
                        <div class="page-subnav feature-subnav">
                                <a href="javascript:void(0)" onclick="sendData('fleetdock','get','dispatch_mission','spy|probe|1|3|15'); return false">Spy Sweep</a>
                                <a href="javascript:void(0)" onclick="sendData('fleetdock','get','dispatch_mission','expedition|light_fighter|1|8|30'); return false">Deep Expedition</a>
                                <a href="javascript:void(0)" onclick="sendData('fleetdock','get','dispatch_mission','raid|cruiser|1|5|25'); return false">Resource Raid</a>
                                <a href="javascript:void(0)" onclick="sendData('fleetdock','get','dispatch_mission','patrol|heavy_fighter|1|12|20'); return false">Defensive Patrol</a>
                        </div>
                </details>
        </div>

        <div class="page-subnav-title">Fleet Dock Sub Pages</div>
        <div class="page-subnav feature-subnav">
                <a<?= $dockView === 'overview' ? ' class="active"' : ''; ?> href="javascript:void(0)" onclick="sendData('fleetdock','get','mainDisplay','overview'); return false">Overview</a>
                <a<?= $dockView === 'catalog' ? ' class="active"' : ''; ?> href="javascript:void(0)" onclick="sendData('fleetdock','get','mainDisplay','catalog'); return false">Starship Catalog 90</a>
                <a<?= $dockView === 'classes' ? ' class="active"' : ''; ?> href="javascript:void(0)" onclick="sendData('fleetdock','get','mainDisplay','classes'); return false">Class &amp; Subclass</a>
                <a<?= $dockView === 'types' ? ' class="active"' : ''; ?> href="javascript:void(0)" onclick="sendData('fleetdock','get','mainDisplay','types'); return false">Type &amp; Subtype</a>
                <a<?= $dockView === 'build' ? ' class="active"' : ''; ?> href="javascript:void(0)" onclick="sendData('fleetdock','get','mainDisplay','build'); return false">Build Factory</a>
        </div>

    <div class="page-grid">
        <div class="card">
            <h4>Shipyard Status</h4>
            <p><strong>Shipyard Level:</strong> <?= fd_num((int)$yard->level); ?></p>
            <p><strong>Mothership Bay:</strong> <?= fd_num((int)$yard->mothership_bay); ?></p>
                        <p><strong>Dock Efficiency:</strong> <?= fd_num((int)$yard->dock_efficiency); ?></p>
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
                        <p>Higher shipyard level increases max ships per build order. Dock Efficiency lowers build resource and crew costs. Mothership bay is required for mothership construction.</p>
                        <form action="javascript:void(0)" onsubmit="sendData('fleetdock','post','dock_action'); return false;" style="margin-top:10px;">
                                <label>Dock Action Dropdown
                                        <select name="actionKey">
                                                <option value="calibrate_dock">Calibrate Dock (improve efficiency)</option>
                                                <option value="crew_requisition">Crew Requisition (buy untrained pool)</option>
                                                <option value="supply_refit">Supply Refit (+2 efficiency from logistics)</option>
                                        </select>
                                </label>
                                <input type="submit" value="Execute Action" style="margin-left:8px;" />
                        </form>
                        <form action="javascript:void(0)" onsubmit="sendData('fleetdock','get','mainDisplay',this.routeView.value); return false;" style="margin-top:8px;">
                                <label>Sub Page Dropdown
                                        <select name="routeView">
                                                <option value="overview">Overview</option>
                                                <option value="build">Build Factory</option>
                                                <option value="catalog">Catalog 90</option>
                                                <option value="classes">Class/Subclass</option>
                                                <option value="types">Type/Subtype</option>
                                        </select>
                                </label>
                                <input type="submit" value="Open Sub Page" style="margin-left:8px;" />
                        </form>
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

                <?php if ($dockView === 'build') { ?>
                <div class="card full">
                        <h4>Starship Factory 90: Build Console</h4>
                        <p>Construct from 90 named starship titles with class/subclass, type/subtype, and stat/sub-stat game logic.</p>
                        <form action="javascript:void(0)" onsubmit="sendData('fleetdock','post','build_starship90'); return false;">
                                <table class="mini-table" border="0" width="100%">
                                        <tr>
                                                <th align="left">Code</th>
                                                <th align="left">Name &amp; Title</th>
                                                <th align="left">Class</th>
                                                <th align="left">Type</th>
                                                <th align="left">Stats (ATK/DEF/SHD)</th>
                                                <th align="left">Sub Stats (SPD/CGO/SYS/WRP)</th>
                                                <th align="left">Cost (M/C/D/F/W/P)</th>
                                                <th align="left">Crew</th>
                                                <th align="left">Owned</th>
                                        </tr>
                                        <?php foreach ($starshipRows as $row) { ?>
                                        <tr>
                                                <td><?= fd_h($row['ship_code']); ?></td>
                                                <td><?= fd_h($row['ship_name']); ?> - <?= fd_h($row['ship_title']); ?></td>
                                                <td><?= fd_h($row['class_letter']); ?>-<?= fd_h($row['class_subclass']); ?> (T<?= fd_num((int)$row['tier']); ?>)</td>
                                                <td><?= fd_h($row['ship_type']); ?> / <?= fd_h($row['ship_subtype']); ?></td>
                                                <td><?= fd_num((int)$row['attack_stat']); ?>/<?= fd_num((int)$row['defense_stat']); ?>/<?= fd_num((int)$row['shield_stat']); ?></td>
                                                <td><?= fd_num((int)$row['speed_stat']); ?>/<?= fd_num((int)$row['cargo_stat']); ?>/<?= fd_num((int)$row['systems_stat']); ?>/<?= fd_num((int)$row['warp_stat']); ?></td>
                                                <td><?= fd_num((int)$row['metal_cost']); ?>/<?= fd_num((int)$row['crystal_cost']); ?>/<?= fd_num((int)$row['deut_cost']); ?>/<?= fd_num((int)$row['food_cost']); ?>/<?= fd_num((int)$row['water_cost']); ?>/<?= fd_num((int)$row['pop_cost']); ?></td>
                                                <td><?= fd_num((int)$row['crew_required']); ?></td>
                                                <td><?= fd_num((int)$row['owned_qty']); ?></td>
                                        </tr>
                                        <?php } ?>
                                </table>
                                <p style="margin-top:10px;">
                                        <label>Starship
                                                <select name="starshipId">
                                                        <?php foreach ($starshipRows as $row) { ?>
                                                        <option value="<?= fd_h((string)$row['starship_id']); ?>"><?= fd_h($row['ship_code']); ?> - <?= fd_h($row['ship_name']); ?> (<?= fd_h($row['class_letter']); ?>-<?= fd_h($row['class_subclass']); ?>)</option>
                                                        <?php } ?>
                                                </select>
                                        </label>
                                        <label style="margin-left:8px;">Amount
                                                <input type="number" min="1" max="500" name="amount" value="1" style="width:90px;" />
                                        </label>
                                        <input type="submit" value="Construct Starship" style="margin-left:8px;" />
                                </p>
                        </form>
                </div>
                <?php } ?>

                <?php if ($dockView === 'catalog') { ?>
                <div class="card full">
                        <h4>90 Starship Catalog</h4>
                        <table class="mini-table" border="0" width="100%">
                                <tr>
                                        <th align="left">Code</th>
                                        <th align="left">Name</th>
                                        <th align="left">Title</th>
                                        <th align="left">Family</th>
                                        <th align="left">Class</th>
                                        <th align="left">Type/Subtype</th>
                                        <th align="left">Power</th>
                                </tr>
                                <?php foreach ($starshipRows as $row) { ?>
                                <tr>
                                        <td><?= fd_h($row['ship_code']); ?></td>
                                        <td><?= fd_h($row['ship_name']); ?></td>
                                        <td><?= fd_h($row['ship_title']); ?></td>
                                        <td><?= fd_h($row['family_name']); ?></td>
                                        <td><?= fd_h($row['class_letter']); ?>-<?= fd_h($row['class_subclass']); ?> | Tier <?= fd_num((int)$row['tier']); ?></td>
                                        <td><?= fd_h($row['ship_type']); ?> / <?= fd_h($row['ship_subtype']); ?></td>
                                        <td><?= fd_num((int)$row['power_rating']); ?></td>
                                </tr>
                                <?php } ?>
                        </table>
                </div>
                <?php } ?>

                <?php if ($dockView === 'classes') { ?>
                <div class="card full">
                        <h4>Starship Class and Subclass Matrix</h4>
                        <table class="mini-table" border="0" width="100%">
                                <tr>
                                        <th align="left">Class/Subclass</th>
                                        <th align="left">Ships</th>
                                        <th align="left">Avg Power</th>
                                        <th align="left">Avg ATK</th>
                                        <th align="left">Avg DEF</th>
                                        <th align="left">Examples</th>
                                </tr>
                                <?php foreach ($classGroups as $classKey => $info) {
                                        $count = max(1, (int)$info['count']);
                                ?>
                                <tr>
                                        <td><?= fd_h($classKey); ?></td>
                                        <td><?= fd_num((int)$info['count']); ?></td>
                                        <td><?= fd_num((int)round($info['avg_power'] / $count)); ?></td>
                                        <td><?= fd_num((int)round($info['avg_attack'] / $count)); ?></td>
                                        <td><?= fd_num((int)round($info['avg_defense'] / $count)); ?></td>
                                        <td><?= fd_h(implode(', ', $info['sample'])); ?></td>
                                </tr>
                                <?php } ?>
                        </table>
                </div>
                <?php } ?>

                <?php if ($dockView === 'types') { ?>
                <div class="card full">
                        <h4>Starship Type and Subtype Matrix</h4>
                        <table class="mini-table" border="0" width="100%">
                                <tr>
                                        <th align="left">Type/Subtype</th>
                                        <th align="left">Ships</th>
                                        <th align="left">Avg Speed</th>
                                        <th align="left">Avg Cargo</th>
                                        <th align="left">Avg Shield</th>
                                        <th align="left">Examples</th>
                                </tr>
                                <?php foreach ($typeGroups as $typeKey => $info) {
                                        $count = max(1, (int)$info['count']);
                                ?>
                                <tr>
                                        <td><?= fd_h($typeKey); ?></td>
                                        <td><?= fd_num((int)$info['count']); ?></td>
                                        <td><?= fd_num((int)round($info['avg_speed'] / $count)); ?></td>
                                        <td><?= fd_num((int)round($info['avg_cargo'] / $count)); ?></td>
                                        <td><?= fd_num((int)round($info['avg_shield'] / $count)); ?></td>
                                        <td><?= fd_h(implode(', ', $info['sample'])); ?></td>
                                </tr>
                                <?php } ?>
                        </table>
                </div>
                <?php } ?>

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
                                        <th align="left">Outcome</th>
                                        <th align="left">Reward / Loot</th>
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
                                        <td><?= fd_h((string)($m->outcome_text ?: $m->outcome)); ?></td>
                                        <td><?= fd_num((int)$m->reward_naquadah); ?> N · <?= fd_num((int)$m->loot_metal); ?> M / <?= fd_num((int)$m->loot_crystal); ?> C / <?= fd_num((int)$m->loot_deuterium); ?> D</td>
                                </tr>
                                                                <?php
                                                }
                                }
                                if (!$hasRows) {
                                                ?>
                                <tr>
                                        <td colspan="8">No active missions. Launch a preset dispatch above.</td>
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
                                <li>Dock Efficiency lowers material and crew requirements for both legacy and 90-starship builds.</li>
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