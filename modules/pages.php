<?php
include_once("../config.php");
require_once __DIR__ . '/../base/PopulationModel.class.php';
require_once __DIR__ . '/../base/ArmyPolicy.class.php';

$pagegen = new page_gen();
$pagegen->round_to = 4;
$pagegen->start();

$s = new Game();
if (!$s->loggedIn || !isset($_GET['time'])) {
    header("Location: ../index.php");
    exit;
}

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function fnum($value): string {
    return number_format((float)$value);
}

function universeRand(int &$seed, int $min, int $max): int {
    $seed = (int)(($seed * 1103515245 + 12345) & 0x7fffffff);
    $span = ($max - $min) + 1;
    return $min + ($seed % $span);
}

function universePick(int &$seed, array $list): string {
    if (count($list) === 0) {
        return '';
    }
    $idx = universeRand($seed, 0, count($list) - 1);
    return (string)$list[$idx];
}

function universeTaxonomy(): array {
    return [
        'worldTypes' => ['Terran', 'Oceanic', 'Arid', 'Volcanic', 'Ice', 'Gas Dwarf', 'Toxic', 'Crystalline', 'Relic'],
        'biomes' => [
            'Terran' => ['Temperate Forest', 'Grassland', 'Rain Basin'],
            'Oceanic' => ['Archipelago', 'Kelp Expanse', 'Storm Sea'],
            'Arid' => ['Dune Basin', 'Canyon Belt', 'Salt Flats'],
            'Volcanic' => ['Magma Rift', 'Ash Plateau', 'Basalt Sea'],
            'Ice' => ['Glacier Shield', 'Frozen Canyons', 'Polar Sink'],
            'Gas Dwarf' => ['Upper Cloudline', 'Ionic Layer', 'Hydrogen Drift'],
            'Toxic' => ['Acid Mire', 'Sulfur Crust', 'Caustic Foglands'],
            'Crystalline' => ['Shard Plains', 'Prism Caves', 'Quartz Highlands'],
            'Relic' => ['Ancient Arcology', 'Derelict Ring', 'Vault Ruins'],
        ],
    ];
}

function buildUniverseSnapshot(int $uid, array $ownedPlanets): array {
    $seed = (($uid + 11) * 7919) & 0x7fffffff;
    $taxonomy = universeTaxonomy();
    $worldTypes = $taxonomy['worldTypes'];
    $biomes = $taxonomy['biomes'];

    $galaxies = [];
    $worlds = [];
    $objects = [];
    $ownedIdx = 0;
    $moonTotal = 0;
    $colonizable = 0;

    for ($g = 1; $g <= 4; $g++) {
        $galName = 'G' . $g;
        $totalHabitability = 0;
        $galWorldCount = 0;
        $galMoonCount = 0;

        for ($sector = 1; $sector <= 6; $sector++) {
            for ($orbit = 1; $orbit <= 6; $orbit++) {
                $type = universePick($seed, $worldTypes);
                $biomePool = $biomes[$type] ?? ['Unknown'];
                $biome = universePick($seed, $biomePool);

                $habitability = universeRand($seed, 18, 98);
                $metal = universeRand($seed, 220, 1200);
                $crystal = universeRand($seed, 120, 980);
                $deut = universeRand($seed, 60, 760);

                $moonCount = ($type === 'Gas Dwarf' || $type === 'Relic') ? universeRand($seed, 1, 3) : universeRand($seed, 0, 2);
                $moonClass = $moonCount > 0 ? universePick($seed, ['Rocky', 'Icy', 'Metallic', 'Ruined']) : '-';
                $slots = max(2, (int)floor($habitability / 12));

                $owner = 'Unclaimed';
                $planetLabel = $galName . '-' . $sector . ':' . $orbit;
                if ($ownedIdx < count($ownedPlanets)) {
                    $owner = 'Player Colony';
                    $planetLabel = (string)$ownedPlanets[$ownedIdx]['name'];
                    $ownedIdx++;
                }

                $worlds[] = [
                    'coord' => $galName . ' [' . $sector . ':' . $orbit . ']',
                    'name' => $planetLabel,
                    'type' => $type,
                    'biome' => $biome,
                    'habitability' => $habitability,
                    'slots' => $slots,
                    'metal' => $metal,
                    'crystal' => $crystal,
                    'deut' => $deut,
                    'moons' => $moonCount,
                    'moonClass' => $moonClass,
                    'owner' => $owner,
                ];

                $totalHabitability += $habitability;
                $galMoonCount += $moonCount;
                $galWorldCount++;
                if ($habitability >= 48 && $owner === 'Unclaimed') {
                    $colonizable++;
                }
            }
        }

        $galaxies[] = [
            'name' => $galName,
            'sectors' => 6,
            'worlds' => $galWorldCount,
            'avgHab' => $galWorldCount > 0 ? (int)round($totalHabitability / $galWorldCount) : 0,
            'moons' => $galMoonCount,
        ];
        $moonTotal += $galMoonCount;

        $objects[] = [
            'galaxy' => $galName,
            'asteroidBelts' => universeRand($seed, 8, 24),
            'debrisFields' => universeRand($seed, 4, 16),
            'nebulae' => universeRand($seed, 2, 9),
            'cometStreams' => universeRand($seed, 1, 7),
            'wormholes' => universeRand($seed, 0, 3),
            'ancientRuins' => universeRand($seed, 1, 5),
        ];
    }

    return [
        'seed' => (($uid + 11) * 7919) & 0x7fffffff,
        'galaxies' => $galaxies,
        'worlds' => $worlds,
        'objects' => $objects,
        'summary' => [
            'totalGalaxies' => count($galaxies),
            'totalWorlds' => count($worlds),
            'totalMoons' => $moonTotal,
            'colonizableWorlds' => $colonizable,
            'ownedColonies' => count($ownedPlanets),
        ],
    ];
}

function universeConfig(): array {
    $galaxies = 134;
    $systemsPerGalaxy = 499;
    $positionsPerSystem = 15;
    $maxWorlds = $galaxies * $systemsPerGalaxy * $positionsPerSystem;

    return [
        'maxWorlds' => $maxWorlds,
        'maxColonies' => 1000000,
        'maxMoons' => 1000000,
        'galaxies' => $galaxies,
        'systemsPerGalaxy' => $systemsPerGalaxy,
        'positionsPerSystem' => $positionsPerSystem,
        'sectorsPerGalaxy' => $systemsPerGalaxy,
        'orbitsPerSector' => $positionsPerSystem,
    ];
}

function universeWorldByIndex(int $uid, array $ownedPlanets, int $index, array $cfg): array {
    $seed = (($uid + 17) * 6151 + ($index * 97)) & 0x7fffffff;
    $taxonomy = universeTaxonomy();
    $worldTypes = $taxonomy['worldTypes'];
    $biomes = $taxonomy['biomes'];

    $type = universePick($seed, $worldTypes);
    $biome = universePick($seed, $biomes[$type] ?? ['Unknown']);
    $habitability = universeRand($seed, 18, 98);
    $metal = universeRand($seed, 220, 1200);
    $crystal = universeRand($seed, 120, 980);
    $deut = universeRand($seed, 60, 760);
    $moonCount = ($type === 'Gas Dwarf' || $type === 'Relic') ? universeRand($seed, 1, 3) : universeRand($seed, 0, 2);
    $moonClass = $moonCount > 0 ? universePick($seed, ['Rocky', 'Icy', 'Metallic', 'Ruined']) : '-';
    $slots = max(2, (int)floor($habitability / 12));

    $systemsPerGalaxy = (int)($cfg['systemsPerGalaxy'] ?? $cfg['sectorsPerGalaxy']);
    $positionsPerSystem = (int)($cfg['positionsPerSystem'] ?? $cfg['orbitsPerSector']);
    $worldsPerGalaxy = (int)($systemsPerGalaxy * $positionsPerSystem);
    $galaxyIndex = (int)floor(($index - 1) / $worldsPerGalaxy) + 1;
    $withinGalaxy = (($index - 1) % $worldsPerGalaxy) + 1;
    $system = (int)floor(($withinGalaxy - 1) / $positionsPerSystem) + 1;
    $position = (($withinGalaxy - 1) % $positionsPerSystem) + 1;

    $owner = 'Unclaimed';
    $planetLabel = 'G' . $galaxyIndex . '-' . $system . ':' . $position;
    if ($index <= count($ownedPlanets)) {
        $owner = 'Player Colony';
        $planetLabel = (string)($ownedPlanets[$index - 1]['name'] ?? $planetLabel);
    }

    return [
        'idx' => $index,
        'coord' => 'G' . $galaxyIndex . ' [' . $system . ':' . $position . ']',
        'system' => $system,
        'position' => $position,
        'name' => $planetLabel,
        'type' => $type,
        'biome' => $biome,
        'habitability' => $habitability,
        'slots' => $slots,
        'metal' => $metal,
        'crystal' => $crystal,
        'deut' => $deut,
        'moons' => $moonCount,
        'moonClass' => $moonClass,
        'owner' => $owner,
    ];
}

function universeWorldSlice(int $uid, array $ownedPlanets, array $cfg, int $page, int $perPage): array {
    $total = (int)$cfg['maxWorlds'];
    $page = max(1, $page);
    $perPage = max(10, min(200, $perPage));
    $maxPage = max(1, (int)ceil($total / $perPage));
    if ($page > $maxPage) {
        $page = $maxPage;
    }
    $start = (($page - 1) * $perPage) + 1;
    $end = min($total, $start + $perPage - 1);

    $rows = [];
    for ($i = $start; $i <= $end; $i++) {
        $rows[] = universeWorldByIndex($uid, $ownedPlanets, $i, $cfg);
    }

    return [
        'rows' => $rows,
        'page' => $page,
        'perPage' => $perPage,
        'maxPage' => $maxPage,
        'start' => $start,
        'end' => $end,
        'total' => $total,
    ];
}

function universeColonizeCosts(array $world): array {
    $habitability = (int)($world['habitability'] ?? 0);
    $slots = (int)($world['slots'] ?? 2);
    $moons = (int)($world['moons'] ?? 0);

    $naqCost = 120000 + ((100 - max(0, min(100, $habitability))) * 1500) + ($moons * 25000);
    $deutCost = 12000 + ($slots * 1300);
    $foodCost = 9000 + ($slots * 900);
    $waterCost = 9000 + ($slots * 900);
    $popCost = 5000 + ($slots * 450);
    $turnCost = 25;

    return [
        'naq' => $naqCost,
        'deut' => $deutCost,
        'food' => $foodCost,
        'water' => $waterCost,
        'pop' => $popCost,
        'turns' => $turnCost,
    ];
}

function universeColonizeWorld(Game $s, int $uid, array $cfg, array $ownedPlanets, int $targetWorld): string {
    if ($targetWorld < 1 || $targetWorld > (int)$cfg['maxWorlds']) {
        return 'Colonization failed: invalid world target.';
    }

    if (count($ownedPlanets) >= (int)$cfg['maxColonies']) {
        return 'Colonization failed: colony cap reached.';
    }

    if ($targetWorld <= count($ownedPlanets)) {
        return 'Colonization failed: target already owned.';
    }

    $world = universeWorldByIndex($uid, $ownedPlanets, $targetWorld, $cfg);
    if ((string)($world['owner'] ?? '') !== 'Unclaimed') {
        return 'Colonization failed: target world is no longer available.';
    }
    if ((int)($world['habitability'] ?? 0) < 46) {
        return 'Colonization failed: habitability too low. Requires 46%+.';
    }

    $turnQ = $s->query("SELECT actionTurns FROM userdata WHERE uid=" . (int)$uid . " LIMIT 1");
    $turns = $turnQ ? (int)($turnQ->fetch_object()->actionTurns ?? 0) : 0;
    $costs = universeColonizeCosts($world);
    if ($turns < (int)$costs['turns']) {
        return 'Colonization failed: not enough action turns.';
    }

    $bank = $s->bank();
    $onHand = (int)($bank->onHand ?? 0);
    if ($onHand < (int)$costs['naq']) {
        return 'Colonization failed: insufficient Naquadah on hand.';
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
    $s->query("INSERT IGNORE INTO player_resources (uid) VALUES (" . (int)$uid . ")");

    $resQ = $s->query("SELECT deuterium,food,water,population FROM player_resources WHERE uid=" . (int)$uid . " LIMIT 1");
    $resObj = $resQ ? $resQ->fetch_object() : null;
    $curDeut = (int)($resObj->deuterium ?? 0);
    $curFood = (int)($resObj->food ?? 0);
    $curWater = (int)($resObj->water ?? 0);
    $curPop = (int)($resObj->population ?? 0);

    if ($curDeut < (int)$costs['deut'] || $curFood < (int)$costs['food'] || $curWater < (int)$costs['water'] || $curPop < (int)$costs['pop']) {
        return 'Colonization failed: insufficient strategic resources (D/F/W/Population).';
    }

    $pidQ = $s->query("SELECT IFNULL(MAX(pid), 0) + 1 AS nextPid FROM planets WHERE uid=" . (int)$uid);
    $nextPid = $pidQ ? (int)($pidQ->fetch_object()->nextPid ?? 1) : 1;
    if ($nextPid < 1) {
        $nextPid = 1;
    }

    $rawName = 'Colony ' . (int)$targetWorld;
    $safeName = preg_replace('/[^A-Za-z0-9 _-]/', '', $rawName);
    if (!$safeName) {
        $safeName = 'Colony ' . (int)$targetWorld;
    }
    $safeText = preg_replace('/[^A-Za-z0-9 _:\[\]-]/', '', (string)($world['coord'] . ' ' . $world['type']));
    $incomeBonus = max(20, (int)floor((int)$world['metal'] / 20));
    $upBonus = max(8, (int)floor((int)$world['habitability'] / 6));
    $sizeCode = max(1, min(9, (int)$world['slots'] - 2));
    $planetPopulation = PopulationModel::randomPlanet($sizeCode, (int)($world['habitability'] ?? 50));

    $insert = "INSERT INTO planets (uid, text, plnt_name, income_bonus, up_bonus, isHome, pid, plnt_size, population, pop_cap) VALUES ("
        . (int)$uid . ", '" . $safeText . "', '" . $safeName . "', " . $incomeBonus . ", " . $upBonus . ", 0, " . $nextPid . ", " . $sizeCode . ", " . $planetPopulation . ", 5000000)";
    if (!$s->query($insert)) {
        return 'Colonization failed: could not create colony record.';
    }

    $s->query("UPDATE bank SET onHand = GREATEST(0, onHand - " . (int)$costs['naq'] . ") WHERE uid=" . (int)$uid . " LIMIT 1");
    $s->query("UPDATE player_resources SET deuterium = GREATEST(0, deuterium - " . (int)$costs['deut'] . "), food = GREATEST(0, food - " . (int)$costs['food'] . "), water = GREATEST(0, water - " . (int)$costs['water'] . "), population = GREATEST(0, population - " . (int)$costs['pop'] . ") WHERE uid=" . (int)$uid . " LIMIT 1");
    $s->query("UPDATE userdata SET actionTurns = GREATEST(0, actionTurns - " . (int)$costs['turns'] . ") WHERE uid=" . (int)$uid . " LIMIT 1");
    $s->updatePower($uid);

    return 'Colonization successful: ' . $safeName . ' established at ' . (string)$world['coord'] . ' (Cost: ' . fnum($costs['naq']) . ' Naquadah, ' . fnum($costs['turns']) . ' turns).';
}

function researchPick(int &$seed, array $list): string {
    if (count($list) === 0) {
        return '';
    }
    $idx = universeRand($seed, 0, count($list) - 1);
    return (string)$list[$idx];
}

function buildResearchDirectorate(int $uid, $techView, $personnel): array {
    $seed = (($uid + 41) * 104729) & 0x7fffffff;

    $domains = ['Quantum', 'Void', 'Psionic', 'Nano', 'Graviton', 'Xeno', 'Bioforge', 'Temporal', 'Stellar', 'Aegis'];
    $focuses = ['Warfare', 'Economy', 'Espionage', 'Logistics', 'Expansion', 'Defense'];
    $typePool = ['Offensive', 'Defensive', 'Support', 'Industrial', 'Recon', 'Colonial'];
    $subTypePool = ['Kinetic', 'Energy', 'Psionic', 'Stealth', 'Command', 'Terraform', 'Recovery', 'Anomaly'];

    $classRoles = ['Architect', 'Sentinel', 'Reaver', 'Oracle', 'Warden', 'Harbinger', 'Cipher', 'Ranger', 'Artificer'];
    $subclassRoles = ['Prime', 'Vanguard', 'Seeker', 'Bastion', 'Catalyst', 'Executor', 'Scholar', 'Pathfinder', 'Anchor'];

    $classes = [];
    $classId = 1;
    foreach ($domains as $domain) {
        foreach ($classRoles as $idx => $role) {
            $type = $typePool[$idx % count($typePool)];
            $subType = $subTypePool[($idx + universeRand($seed, 0, 7)) % count($subTypePool)];
            $classes[] = [
                'id' => $classId,
                'className' => $domain . ' ' . $role,
                'subClass' => $domain . ' ' . $subclassRoles[$idx],
                'type' => $type,
                'subType' => $subType,
            ];
            $classId++;
        }
    }

    $researchTree = [];
    $techTree = [];
    foreach ($domains as $domain) {
        $researchNodes = [];
        $techNodes = [];
        for ($tier = 1; $tier <= 6; $tier++) {
            $researchNodes[] = [
                'name' => $domain . ' Research Tier ' . $tier,
                'focus' => researchPick($seed, $focuses),
                'cost' => (50000 * $tier) + universeRand($seed, 2500, 15000),
                'power' => universeRand($seed, 4, 18) * $tier,
            ];
            $techNodes[] = [
                'name' => $domain . ' Technology Tier ' . $tier,
                'focus' => researchPick($seed, $focuses),
                'cost' => (65000 * $tier) + universeRand($seed, 3500, 18000),
                'power' => universeRand($seed, 5, 22) * $tier,
            ];
        }
        $researchTree[] = ['domain' => $domain, 'nodes' => $researchNodes];
        $techTree[] = ['domain' => $domain, 'nodes' => $techNodes];
    }

    $talentPrefixes = ['Adaptive', 'Warped', 'Hyper', 'Focused', 'Deep', 'Prime', 'Echo', 'Null', 'Stellar', 'Iron', 'Arc', 'Silent'];
    $talentCore = ['Matrix', 'Lattice', 'Protocol', 'Vector', 'Engine', 'Manifold', 'Beacon', 'Circuit', 'Doctrine', 'Kernel'];
    $talentSuffix = ['Surge', 'Lock', 'Burst', 'Thread', 'Field', 'Link', 'Sight', 'Ward', 'Pulse', 'Drive'];

    $talents = [];
    for ($i = 1; $i <= 240; $i++) {
        $isResearch = $i <= 120;
        $tier = 1 + (int)floor(($i - 1) / 30);
        $domain = $domains[($i - 1) % count($domains)];
        $focus = $focuses[($i + 2) % count($focuses)];
        $prefix = $talentPrefixes[($i + universeRand($seed, 0, 11)) % count($talentPrefixes)];
        $core = $talentCore[($i + universeRand($seed, 0, 9)) % count($talentCore)];
        $suffix = $talentSuffix[($i + universeRand($seed, 0, 9)) % count($talentSuffix)];

        $talents[] = [
            'id' => $i,
            'branch' => $isResearch ? 'Research' : 'Technology',
            'domain' => $domain,
            'focus' => $focus,
            'tier' => $tier,
            'name' => $prefix . ' ' . $core . ' ' . $suffix,
            'effect' => ($isResearch ? 'Lab Output +' : 'Tech Throughput +') . universeRand($seed, 2, 12) . '%',
        ];
    }

    $ttl = (int)($techView->ttl ?? 0);
    $asc = (int)($techView->ascend ?? 0);
    $commandLevel = max(1, 1 + (int)floor(($ttl + ($asc * 25)) / 10));
    $xpToNext = ($commandLevel * 1200) + ($asc * 500);

    $stats = [
        'Research Mastery' => 60 + ($commandLevel * 4),
        'Tech Integration' => 55 + ($commandLevel * 3),
        'Doctrine Control' => 50 + ($commandLevel * 3),
        'Fleet Engineering' => 45 + ($commandLevel * 4),
        'Expedition Theory' => 48 + ($commandLevel * 3),
    ];

    $subStats = [
        'Lab Efficiency' => 35 + ((int)($techView->income ?? 0) * 2),
        'Prototype Speed' => 30 + ((int)($techView->uppl ?? 0) * 2),
        'Resource Fidelity' => 28 + ((int)($techView->duRes ?? 0)),
        'Signal Intelligence' => 32 + ((int)($techView->cuEffect ?? 0)),
        'Containment Stability' => 26 + ((int)($techView->pDef ?? 0)),
        'Field Logistics' => 34 + (int)floor((int)($personnel->uuCount ?? 0) / 10000),
    ];

    return [
        'counts' => [
            'classes' => count($classes),
            'subclasses' => count($classes),
            'types' => count($typePool),
            'subtypes' => count($subTypePool),
            'talents' => count($talents),
        ],
        'level' => [
            'commandLevel' => $commandLevel,
            'researchLevel' => max(1, 1 + (int)floor($ttl / 8)),
            'technologyLevel' => max(1, 1 + (int)floor(($ttl + $asc) / 9)),
            'ascension' => $asc,
            'xpToNext' => $xpToNext,
        ],
        'stats' => $stats,
        'subStats' => $subStats,
        'researchTree' => $researchTree,
        'techTree' => $techTree,
        'classes' => $classes,
        'talents' => $talents,
        'types' => $typePool,
        'subTypes' => $subTypePool,
    ];
}

function resourceEnsureAndTick(Game $s, int $uid, $baseData, array $planets, $techView): array {
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

    $s->query("INSERT IGNORE INTO player_resources (uid) VALUES (" . (int)$uid . ")");
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
    $s->query("INSERT IGNORE INTO resource_structures (uid) VALUES (" . (int)$uid . ")");

    $strQ = $s->query("SELECT metal_mine,crystal_lab,deuterium_refinery,hydroponics,water_plant,habitat_dome,energy_reactor FROM resource_structures WHERE uid=" . (int)$uid . " LIMIT 1");
    $structures = $strQ ? $strQ->fetch_object() : (object)[
        'metal_mine' => 1,
        'crystal_lab' => 1,
        'deuterium_refinery' => 1,
        'hydroponics' => 1,
        'water_plant' => 1,
        'habitat_dome' => 1,
        'energy_reactor' => 1,
    ];
    $resQ = $s->query("SELECT metal,crystal,deuterium,food,water,population,energy,last_tick_at FROM player_resources WHERE uid=" . (int)$uid . " LIMIT 1");
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

    $planetCount = max(1, count($planets));
    $incomeBase = max(220, (int)($baseData->income ?? 220));
    $upBase = max(10, (int)($baseData->up ?? 10));
    $techIncome = max(0, (int)($techView->income ?? 0));
    $techProd = max(0, (int)($techView->unitProd ?? 0));

    $rates = [
        'metal' => (int)round((($incomeBase * 0.40) + ($planetCount * 180) + ($upBase * 8) + ($techProd * 20)) * (1 + ((int)$structures->metal_mine * 0.12))),
        'crystal' => (int)round((($incomeBase * 0.28) + ($planetCount * 140) + ($upBase * 5) + ($techIncome * 16)) * (1 + ((int)$structures->crystal_lab * 0.12))),
        'deuterium' => (int)round((($incomeBase * 0.18) + ($planetCount * 120) + ($upBase * 3) + ($techIncome * 12)) * (1 + ((int)$structures->deuterium_refinery * 0.12))),
        'food' => (int)round((($incomeBase * 0.14) + ($planetCount * 220) + ($techIncome * 9)) * (1 + ((int)$structures->hydroponics * 0.10))),
        'water' => (int)round((($incomeBase * 0.12) + ($planetCount * 240) + ($techIncome * 8)) * (1 + ((int)$structures->water_plant * 0.10))),
        'population' => max(25, (int)round((($planetCount * 30) + ($upBase * 0.35)) * (1 + ((int)$structures->habitat_dome * 0.08)))),
        'energy' => (int)round((($incomeBase * 0.22) + ($planetCount * 160) + ($techProd * 14) + ($techIncome * 10)) * (1 + ((int)$structures->energy_reactor * 0.13))),
    ];

    $lastTickTs = strtotime((string)$res->last_tick_at);
    if ($lastTickTs === false) {
        $lastTickTs = time();
    }
    $nowTs = time();
    $tickSeconds = 1800;
    $ticks = (int)floor(max(0, $nowTs - $lastTickTs) / $tickSeconds);

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
            $popDrop = (int)round($population * 0.02);
            $population = max(0, $population - max(150, $popDrop));
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
            WHERE uid=" . (int)$uid . " LIMIT 1");

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

    return [
        'current' => [
            'metal' => (int)($res->metal ?? 0),
            'crystal' => (int)($res->crystal ?? 0),
            'deuterium' => (int)($res->deuterium ?? 0),
            'food' => (int)($res->food ?? 0),
            'water' => (int)($res->water ?? 0),
            'population' => (int)($res->population ?? 0),
            'energy' => (int)($res->energy ?? 0),
        ],
        'rates' => $rates,
        'structures' => [
            'metal_mine' => (int)$structures->metal_mine,
            'crystal_lab' => (int)$structures->crystal_lab,
            'deuterium_refinery' => (int)$structures->deuterium_refinery,
            'hydroponics' => (int)$structures->hydroponics,
            'water_plant' => (int)$structures->water_plant,
            'habitat_dome' => (int)$structures->habitat_dome,
            'energy_reactor' => (int)$structures->energy_reactor,
        ],
        'ticksApplied' => $ticks,
    ];
}

function renderTreeBoard(array $branches, int $level, string $boardId, string $nodePrefix): void {
    echo '<div class="wows-tree" id="' . h($boardId) . '">';
    echo '<div class="wows-tier-head">';
    echo '<span>Domain</span>';
    for ($tier = 1; $tier <= 6; $tier++) {
        echo '<span>T' . $tier . '</span>';
    }
    echo '</div>';

    foreach ($branches as $branch) {
        echo '<div class="wows-tree-row">';
        echo '<div class="wows-domain">' . h($branch['domain']) . '</div>';
        echo '<div class="wows-node-lane">';

        foreach ($branch['nodes'] as $idx => $node) {
            $tier = $idx + 1;
            $state = 'locked';
            if ($level > $tier) {
                $state = 'unlocked';
            } elseif ($level === $tier) {
                $state = 'available';
            }

            echo '<div class="wows-node ' . h($state) . '">';
            echo '<div class="wows-node-title">' . h($nodePrefix . ' ' . $tier) . '</div>';
            echo '<div class="wows-node-name">' . h($node['name']) . '</div>';
            echo '<div class="wows-node-meta">' . h($node['focus']) . '</div>';
            echo '<div class="wows-node-meta">Cost ' . fnum($node['cost']) . ' | Power ' . fnum($node['power']) . '</div>';
            echo '</div>';
        }

        echo '</div>';
        echo '</div>';
    }

    echo '</div>';
}

function renderInfoBlock(array $detail): void {
    echo '<section class="system-detail-panel">';
    echo '<div class="system-detail-brief"><span class="rts-kicker">SYSTEM BRIEF</span><h4>Operational Brief</h4><p>' . h($detail['brief']) . '</p></div>';
    echo '<div class="system-detail-grid">';
    $blocks = [
        ['functions', 'Functions', 'What the commander can do', 'system-function-list'],
        ['features', 'Features', 'What the page exposes', 'system-feature-list'],
        ['logic', 'Game Logic', 'How the server resolves it', 'system-logic-list'],
    ];
    foreach ($blocks as [$key, $label, $caption, $class]) {
        echo '<article class="system-detail-card ' . h($class) . '"><span class="system-detail-label">' . h($label) . '</span><h5>' . h($caption) . '</h5><ul>';
        foreach (($detail[$key] ?? []) as $index => $item) {
            echo '<li><span class="system-index">' . ((int)$index + 1) . '</span><span>' . h($item) . '</span></li>';
        }
        echo '</ul></article>';
    }
    echo '</div></section>';
}

function renderMechanicsMatrix(string $main, string $sub): void {
    $core = [
        'Turn cycle runs every 30 minutes and updates key production resources.',
        'Primary strategic resources are Metal, Crystal, Deuterium, Food, Water, Population, plus Naquadah and turn currencies.',
        'Untrained units are generated by unit production and converted into military roles through training.',
    ];

    $race = [
        'Tauri focus: stronger attack posture.',
        'Goa\'uld focus: stronger income posture.',
        'Asgard focus: stronger defense posture.',
        'Replicator focus: stronger covert posture.',
    ];

    $byPage = [
        'operations:attack' => [
            'Attacks consume action turns and compare attacker offensive power against defender defensive power.',
            'Victory can transfer Naquadah from defender to attacker based on combat outcome.',
            'Anti-covert detachments may engage enemy covert forces during combat phases.',
        ],
        'operations:raid' => [
            'Raids focus on stealing untrained units instead of Naquadah.',
            'Raid missions are high tempo operations with elevated retaliation risk when overused.',
            'Repeated short-cycle raids should be monitored to avoid overextension and diplomatic blowback.',
        ],
        'operations:spy' => [
            'Spy missions consume covert turns, not regular attack turns.',
            'Failure can cost covert agents; success reveals enemy military and economy indicators.',
            'Covert vs anti-covert balance heavily influences reconnaissance reliability.',
        ],
        'operations:logs' => [
            'Logs are a tactical feedback loop for force composition and target quality.',
            'Reviewing losses by mission type helps adjust training and equipment priorities.',
            'Short debrief loops improve campaign consistency over long wars.',
        ],
        'military:fleet' => [
            'Mothership fleets support planet-oriented operations and offensive projection.',
            'Fleet strength can support attacks but does not act as home-planet defense in the same way.',
            'Fleet repair and bay investment should be planned with campaign timing.',
        ],
        'empire:planets' => [
            'Planet conquest commonly uses full mission expenditure and favors strong fleet posture.',
            'Planet acquisition is generally cadence-limited and should be timed around war objectives.',
            'Planet bonuses should be mapped to economy and military specialization plans.',
        ],
        'economy:banking' => [
            'Naquadah is the central purchase currency for units, equipment, and upgrades.',
            'Maintaining split reserves (on-hand and banked) improves shock resistance.',
            'Economic discipline sets the tempo for sustained military operations.',
        ],
        'economy:market' => [
            'Market turns and broker systems convert resources into strategic flexibility.',
            'Trade timing can materially change effective growth rates.',
            'Overtrading for one dimension can leave military or covert gaps.',
        ],
        'diplomacy:relations' => [
            'Relation stance affects war risk, coalition behavior, and target pressure.',
            'Stable stance policy across alliance members improves deterrence.',
            'Repeated actions against the same realm can trigger escalating political response.',
        ],
        'diplomacy:commander' => [
            'Commander chains shape protection structure and economic support flow.',
            'Support transfers should follow command objectives and risk posture.',
            'Leadership churn can reduce alliance execution quality.',
        ],
        'diplomacy:governance' => [
            'Governance systems tune command doctrine, policy cadence, and strategic automation layers.',
            'Enable systems that match current doctrine while avoiding over-specialization in one branch.',
            'Commander settings should align with war, economy, and expansion priorities each cycle.',
        ],
        'help:mechanics' => [
            'Covert sabotage doctrine often accepts lower losses on success and higher losses on failure.',
            'Mothership progression includes high-cost entry, bay expansion, and weapon specialization.',
            'Effective macro play balances production growth, intel quality, and turn efficiency.',
        ],
        'universe:galaxies' => [
            'Universe is divided into strategic galaxies, systems, and orbital positions for expansion routing.',
            'Each world has a biome profile, habitability score, and distinct resource distribution.',
            'strategic growth favors staggered colonies across multiple galaxy lanes to reduce bottlenecks.',
        ],
        'universe:planets' => [
            'Moon presence improves surveillance coverage and tactical deployment windows.',
            'Planet biomes influence long-run mining bias and defensive architecture planning.',
            'Colonization slots should be prioritized for high-habitability worlds with stable debris income nearby.',
        ],
        'universe:objects' => [
            'Debris fields support recycler-style recovery loops for metal and crystal reconstruction.',
            'Nebula and wormhole zones increase expedition variance and scouting risk.',
            'Ancient ruins provide high-variance anomaly opportunities for advanced empires.',
        ],
        'universe:expedition' => [
            'Expeditions are fleet-timed probes with outcome variance tied to mission scale and support posture.',
            'Colonization cadence should match economy reserve and defensive readiness.',
            'Rapid multi-wave expansion increases reach but can weaken local defense if staged too aggressively.',
        ],
    ];

    $key = $main . ':' . $sub;
    $context = $byPage[$key] ?? [
        'This page participates in the 30-minute turn economy and shared resource model.',
        'Actions here should be sequenced with current action-turn, covert-turn, and Naquadah budget.',
        'Use this panel with logs and rankings to continually adjust doctrine.',
    ];

    echo '<div class="card full"><h4>Deep Mechanics Matrix</h4><ul>';
    foreach ($core as $line) {
        echo '<li>' . h($line) . '</li>';
    }
    echo '</ul></div>';

    echo '<div class="card"><h4>Race Meta Effects</h4><ul>';
    foreach ($race as $line) {
        echo '<li>' . h($line) . '</li>';
    }
    echo '</ul></div>';

    echo '<div class="card"><h4>Page-Specific Rules</h4><ul>';
    foreach ($context as $line) {
        echo '<li>' . h($line) . '</li>';
    }
    echo '</ul></div>';
}

function renderInteractiveCalculators(string $main, string $sub, $baseData, $personnel, $bank): void {
    if (($main === 'operations' && ($sub === 'attack' || $sub === 'raid')) || ($main === 'help' && $sub === 'mechanics')) {
        echo '<div class="card full">';
        echo '<h4>Battle Outcome Estimator</h4>';
        echo '<div class="calc-grid">';
        echo '<label>Attack Power<input id="calcAtkPower" type="number" min="0" value="' . h((int)($personnel->attackCount ?? 0)) . '"></label>';
        echo '<label>Defense Power<input id="calcDefPower" type="number" min="0" value="' . h((int)($personnel->defenseCount ?? 0)) . '"></label>';
        echo '<label>Attack Tech %<input id="calcAtkTech" type="number" min="0" value="12"></label>';
        echo '<label>Defense Tech %<input id="calcDefTech" type="number" min="0" value="12"></label>';
        echo '<label>Fleet Strength<input id="calcFleet" type="number" min="0" value="0"></label>';
        echo '<label>Shield/Planet Defense<input id="calcShield" type="number" min="0" value="0"></label>';
        echo '<label>Turns Committed<input id="calcTurns" type="number" min="1" max="15" value="10"></label>';
        echo '<label>Target Naquadah Pool<input id="calcNaqPool" type="number" min="0" value="1000000"></label>';
        echo '</div>';
        echo '<button class="calc-btn" type="button" onclick="(function(){var atk=Math.max(0,parseFloat(document.getElementById(\'calcAtkPower\').value)||0);var def=Math.max(0,parseFloat(document.getElementById(\'calcDefPower\').value)||0);var atkTech=Math.max(0,parseFloat(document.getElementById(\'calcAtkTech\').value)||0);var defTech=Math.max(0,parseFloat(document.getElementById(\'calcDefTech\').value)||0);var fleet=Math.max(0,parseFloat(document.getElementById(\'calcFleet\').value)||0);var shield=Math.max(0,parseFloat(document.getElementById(\'calcShield\').value)||0);var turns=Math.min(15,Math.max(1,parseFloat(document.getElementById(\'calcTurns\').value)||1));var naqPool=Math.max(0,parseFloat(document.getElementById(\'calcNaqPool\').value)||0);var atkScore=(atk*(1+atkTech/100))+fleet*0.35;var defScore=(def*(1+defTech/100))+shield*0.25;var ratio=atkScore/Math.max(defScore,1);var winChance=Math.max(5,Math.min(95,50+((ratio-1)*35)));var lootPct=Math.max(0.01,Math.min(0.25,0.03+Math.max(0,ratio-1)*0.1));var estLoot=naqPool*lootPct*(turns/15);document.getElementById(\'calcBattleOut\').innerHTML=\'Attack Score: \'+Math.round(atkScore).toLocaleString()+\' | Defense Score: \'+Math.round(defScore).toLocaleString()+\'<br>Win Chance: \'+winChance.toFixed(1)+\'% | Est. Naquadah Gain: \'+Math.round(estLoot).toLocaleString()+\'\';})();">Estimate Battle</button>';
        echo '<div id="calcBattleOut" class="calc-output">Adjust values and run estimate.</div>';
        echo '</div>';
    }

    if (($main === 'operations' && $sub === 'spy') || ($main === 'help' && $sub === 'mechanics')) {
        echo '<div class="card full">';
        echo '<h4>Covert Mission Estimator</h4>';
        echo '<div class="calc-grid">';
        echo '<label>Spies Sent<input id="calcSpySent" type="number" min="1" value="10000"></label>';
        echo '<label>Your Covert Tech %<input id="calcCovertTech" type="number" min="0" value="10"></label>';
        echo '<label>Enemy Anti-Covert Units<input id="calcEnemyAnti" type="number" min="0" value="8000"></label>';
        echo '<label>Enemy Anti-Covert Tech %<input id="calcEnemyAntiTech" type="number" min="0" value="10"></label>';
        echo '<label>Covert Turns Used<input id="calcCt" type="number" min="1" max="15" value="5"></label>';
        echo '</div>';
        echo '<button class="calc-btn" type="button" onclick="(function(){var spies=Math.max(1,parseFloat(document.getElementById(\'calcSpySent\').value)||1);var cTech=Math.max(0,parseFloat(document.getElementById(\'calcCovertTech\').value)||0);var enemy=Math.max(0,parseFloat(document.getElementById(\'calcEnemyAnti\').value)||0);var eTech=Math.max(0,parseFloat(document.getElementById(\'calcEnemyAntiTech\').value)||0);var ct=Math.min(15,Math.max(1,parseFloat(document.getElementById(\'calcCt\').value)||1));var covertPower=spies*(1+cTech/100)*Math.sqrt(ct/5);var antiPower=enemy*(1+eTech/100);var success=Math.max(2,Math.min(98,50+((covertPower-antiPower)/Math.max(antiPower,1))*40));var successLoss=spies*0.05;var failLoss=spies*0.50;var expectedLoss=(success/100)*successLoss+((100-success)/100)*failLoss;document.getElementById(\'calcCovertOut\').innerHTML=\'Success Chance: \'+success.toFixed(1)+\'%<br>Expected Spy Loss: \'+Math.round(expectedLoss).toLocaleString()+\' (Success ~5%, Failure ~50%)\';})();">Estimate Covert Mission</button>';
        echo '<div id="calcCovertOut" class="calc-output">Model includes SGW-style high failure penalties for covert actions.</div>';
        echo '</div>';
    }

    if (($main === 'economy' && ($sub === 'banking' || $sub === 'market' || $sub === 'production')) || ($main === 'help' && $sub === 'mechanics') || ($main === 'empire' && ($sub === 'home' || $sub === 'overview'))) {
        echo '<div class="card full">';
        echo '<h4>Turn Economy Planner</h4>';
        echo '<div class="calc-grid">';
        echo '<label>Current On-Hand Naquadah<input id="calcCurrNaq" type="number" min="0" value="' . h((int)($bank->onHand ?? 0)) . '"></label>';
        echo '<label>Income Per Turn<input id="calcIncomeTurn" type="number" min="0" value="' . h((int)($baseData->income ?? 0)) . '"></label>';
        echo '<label>Current Untrained Units<input id="calcCurrUu" type="number" min="0" value="' . h((int)($personnel->uuCount ?? 0)) . '"></label>';
        echo '<label>Unit Production / Turn<input id="calcUpTurn" type="number" min="0" value="' . h((int)($baseData->up ?? 0)) . '"></label>';
        echo '<label>Planning Horizon (turns)<input id="calcHorizon" type="number" min="1" max="200" value="24"></label>';
        echo '</div>';
        echo '<button class="calc-btn" type="button" onclick="(function(){var naq=Math.max(0,parseFloat(document.getElementById(\'calcCurrNaq\').value)||0);var income=Math.max(0,parseFloat(document.getElementById(\'calcIncomeTurn\').value)||0);var uu=Math.max(0,parseFloat(document.getElementById(\'calcCurrUu\').value)||0);var up=Math.max(0,parseFloat(document.getElementById(\'calcUpTurn\').value)||0);var horizon=Math.min(200,Math.max(1,parseFloat(document.getElementById(\'calcHorizon\').value)||1));var projNaq=naq+(income*horizon);var projUu=uu+(up*horizon);var attackBudget=Math.floor(projNaq*0.35);var techBudget=Math.floor(projNaq*0.25);var reserveBudget=Math.floor(projNaq*0.20);document.getElementById(\'calcEcoOut\').innerHTML=\'Projected Naquadah: \'+Math.round(projNaq).toLocaleString()+\' | Projected UU: \'+Math.round(projUu).toLocaleString()+\'<br>Suggested Split -> Military: \'+attackBudget.toLocaleString()+\', Technology: \'+techBudget.toLocaleString()+\', Reserve: \'+reserveBudget.toLocaleString()+\'\';})();">Project Economy</button>';
        echo '<div id="calcEcoOut" class="calc-output">Use this to simulate turn-based growth and budget splits.</div>';
        echo '</div>';
    }
}

function renderFeatureWorkbenches(string $main, string $sub, $baseData, $personnel, $bank, $userStats, array $planets): void {
    if (($main === 'operations' && $sub === 'raid') || ($main === 'help' && $sub === 'mechanics')) {
        echo '<div class="card full">';
        echo '<h4>Raid Yield Planner</h4>';
        echo '<div class="calc-grid">';
        echo '<label>Enemy Untrained Units<input id="raidEnemyUu" type="number" min="0" value="120000"></label>';
        echo '<label>Raid Power<input id="raidPower" type="number" min="0" value="' . h((int)($personnel->attackCount ?? 0)) . '"></label>';
        echo '<label>Enemy Defense Power<input id="raidEnemyDef" type="number" min="0" value="90000"></label>';
        echo '<label>Turns Committed<input id="raidTurns" type="number" min="1" max="15" value="8"></label>';
        echo '</div>';
        echo '<button class="calc-btn" type="button" onclick="(function(){var enemyUU=Math.max(0,parseFloat(document.getElementById(\'raidEnemyUu\').value)||0);var rp=Math.max(0,parseFloat(document.getElementById(\'raidPower\').value)||0);var ed=Math.max(1,parseFloat(document.getElementById(\'raidEnemyDef\').value)||1);var turns=Math.min(15,Math.max(1,parseFloat(document.getElementById(\'raidTurns\').value)||1));var ratio=rp/ed;var success=Math.max(5,Math.min(95,45+((ratio-1)*40)));var stealPct=Math.max(0.01,Math.min(0.18,0.02+Math.max(0,ratio-1)*0.06));var estSteal=enemyUU*stealPct*(turns/15);var retaliation=Math.max(5,Math.min(95,55-((ratio-1)*20)+(turns*1.2)));document.getElementById(\'raidOut\').innerHTML=\'Raid Success Chance: \'+success.toFixed(1)+\'%<br>Estimated UU Captured: \'+Math.round(estSteal).toLocaleString()+\'<br>Retaliation Pressure Index: \'+retaliation.toFixed(1)+\'%\';})();">Estimate Raid</button>';
        echo '<div id="raidOut" class="calc-output">Use this planner to balance raid yield versus retaliation pressure.</div>';
        echo '</div>';
    }

    if (($main === 'operations' && $sub === 'spy') || ($main === 'intel' && $sub === 'reports') || ($main === 'help' && $sub === 'mechanics')) {
        echo '<div class="card full">';
        echo '<h4>Sabotage Impact Planner</h4>';
        echo '<div class="calc-grid">';
        echo '<label>Spies Sent<input id="sabSpies" type="number" min="1" value="15000"></label>';
        echo '<label>Your Covert Tech %<input id="sabTech" type="number" min="0" value="12"></label>';
        echo '<label>Enemy Anti-Covert Power<input id="sabEnemyAc" type="number" min="0" value="12000"></label>';
        echo '<label>Enemy Armory Size<input id="sabArmory" type="number" min="0" value="300000"></label>';
        echo '</div>';
        echo '<button class="calc-btn" type="button" onclick="(function(){var spies=Math.max(1,parseFloat(document.getElementById(\'sabSpies\').value)||1);var tech=Math.max(0,parseFloat(document.getElementById(\'sabTech\').value)||0);var enemy=Math.max(1,parseFloat(document.getElementById(\'sabEnemyAc\').value)||1);var armory=Math.max(0,parseFloat(document.getElementById(\'sabArmory\').value)||0);var covert=spies*(1+tech/100);var success=Math.max(2,Math.min(98,50+((covert-enemy)/enemy)*38));var damagePct=Math.max(0.01,Math.min(0.22,0.03+Math.max(0,success-50)/220));var estDamage=armory*damagePct;var expectedLoss=(success/100)*(spies*0.05)+((100-success)/100)*(spies*0.50);document.getElementById(\'sabOut\').innerHTML=\'Success Chance: \'+success.toFixed(1)+\'%<br>Estimated Armory Damage Index: \'+Math.round(estDamage).toLocaleString()+\'<br>Expected Spy Loss: \'+Math.round(expectedLoss).toLocaleString()+\'\';})();">Estimate Sabotage</button>';
        echo '<div id="sabOut" class="calc-output">Model follows high-risk covert doctrine with larger failure losses.</div>';
        echo '</div>';
    }

    if (($main === 'military' && $sub === 'fleet') || ($main === 'empire' && $sub === 'planets') || ($main === 'help' && $sub === 'mechanics')) {
        echo '<div class="card full">';
        echo '<h4>Planet Conquest Planner</h4>';
        echo '<div class="calc-grid">';
        echo '<label>Fleet Strength<input id="conqFleet" type="number" min="0" value="100000"></label>';
        echo '<label>Target Ground Defense<input id="conqDef" type="number" min="0" value="85000"></label>';
        echo '<label>Beacon Strength<input id="conqBeacon" type="number" min="0" value="100"></label>';
        echo '<label>Attempts Today<input id="conqAttempts" type="number" min="0" max="5" value="0"></label>';
        echo '</div>';
        echo '<button class="calc-btn" type="button" onclick="(function(){var fleet=Math.max(0,parseFloat(document.getElementById(\'conqFleet\').value)||0);var def=Math.max(1,parseFloat(document.getElementById(\'conqDef\').value)||1);var beacon=Math.max(0,parseFloat(document.getElementById(\'conqBeacon\').value)||0);var attempts=Math.max(0,parseFloat(document.getElementById(\'conqAttempts\').value)||0);var ratio=(fleet*(1+(beacon/1000)))/def;var success=Math.max(1,Math.min(97,42+((ratio-1)*45)));var blocked=attempts>=1;var fleetLossPct=Math.max(0.03,Math.min(0.45,0.20-(ratio-1)*0.08));if(blocked){document.getElementById(\'conqOut\').innerHTML=\'Daily limit reached: plan next conquest cycle (24h cadence).\';return;}document.getElementById(\'conqOut\').innerHTML=\'Conquest Success Chance: \'+success.toFixed(1)+\'%<br>Estimated Fleet Risk on Failure: \'+Math.round(fleetLossPct*100)+\'%<br>Reminder: conquest attempts are cadence-limited.\';})();">Estimate Conquest</button>';
        echo '<div id="conqOut" class="calc-output">Plan conquest around fleet risk, beacon context, and daily cadence limits.</div>';
        echo '</div>';
    }

    if (($main === 'diplomacy' && ($sub === 'relations' || $sub === 'alliance')) || ($main === 'help' && $sub === 'support')) {
        echo '<div class="card full">';
        echo '<h4>Diplomacy Policy Engine</h4>';
        echo '<div class="calc-grid">';
        echo '<label>Hits on Same Target (7d)<input id="dipHits" type="number" min="0" value="2"></label>';
        echo '<label>Raids on Same Target (7d)<input id="dipRaids" type="number" min="0" value="1"></label>';
        echo '<label>Alliance Tension Level (0-100)<input id="dipTension" type="number" min="0" max="100" value="35"></label>';
        echo '<label>Incoming Threat Index (0-100)<input id="dipThreat" type="number" min="0" max="100" value="40"></label>';
        echo '</div>';
        echo '<button class="calc-btn" type="button" onclick="(function(){var hits=Math.max(0,parseFloat(document.getElementById(\'dipHits\').value)||0);var raids=Math.max(0,parseFloat(document.getElementById(\'dipRaids\').value)||0);var tension=Math.max(0,Math.min(100,parseFloat(document.getElementById(\'dipTension\').value)||0));var threat=Math.max(0,Math.min(100,parseFloat(document.getElementById(\'dipThreat\').value)||0));var pressure=(hits*10)+(raids*12)+(tension*0.35)+(threat*0.4);var tier=\'Stable\';if(pressure>=95){tier=\'High Escalation\';}else if(pressure>=65){tier=\'Tense\';}else if(pressure>=40){tier=\'Watch\';}var recommendation=(tier===\'High Escalation\')?\'Pause repeat hits, coordinate alliance posture, open direct channel.\':(tier===\'Tense\')?\'Shift to selective targets, diversify operations, document incidents.\':(tier===\'Watch\')?\'Maintain spacing discipline, monitor retaliation signals.\':\'Proceed with standard posture and periodic review.\';document.getElementById(\'dipOut\').innerHTML=\'Policy Tier: \'+tier+\'<br>Escalation Score: \'+pressure.toFixed(1)+\'<br>Recommendation: \'+recommendation;})();">Evaluate Policy</button>';
        echo '<div id="dipOut" class="calc-output">Use this engine to avoid over-farming patterns and unnecessary alliance escalation.</div>';
        echo '</div>';
    }

    if (($main === 'economy' && $sub === 'technology') || ($main === 'economy' && $sub === 'production') || ($main === 'help' && $sub === 'mechanics')) {
        echo '<div class="card full">';
        echo '<h4>Upgrade ROI Workbench</h4>';
        echo '<div class="calc-grid">';
        echo '<label>Upgrade Cost (Naquadah)<input id="roiCost" type="number" min="0" value="500000"></label>';
        echo '<label>Extra Income / Turn<input id="roiIncome" type="number" min="0" value="15000"></label>';
        echo '<label>Extra UP / Turn<input id="roiUp" type="number" min="0" value="400"></label>';
        echo '<label>Horizon (turns)<input id="roiTurns" type="number" min="1" max="500" value="72"></label>';
        echo '</div>';
        echo '<button class="calc-btn" type="button" onclick="(function(){var cost=Math.max(0,parseFloat(document.getElementById(\'roiCost\').value)||0);var inc=Math.max(0,parseFloat(document.getElementById(\'roiIncome\').value)||0);var up=Math.max(0,parseFloat(document.getElementById(\'roiUp\').value)||0);var turns=Math.max(1,Math.min(500,parseFloat(document.getElementById(\'roiTurns\').value)||1));var valuePerUp=120;var gross=(inc*turns)+(up*valuePerUp*turns);var net=gross-cost;var payback=cost/Math.max((inc+(up*valuePerUp)),1);var verdict=(net>0)?\'Positive ROI\':\'Negative ROI\';document.getElementById(\'roiOut\').innerHTML=\'Projected Gross Value: \'+Math.round(gross).toLocaleString()+\'<br>Projected Net Value: \'+Math.round(net).toLocaleString()+\'<br>Payback: \'+payback.toFixed(1)+\' turns | Verdict: \'+verdict;})();">Run ROI</button>';
        echo '<div id="roiOut" class="calc-output">Compare upgrades by payback time before committing strategic reserves.</div>';
        echo '</div>';
    }

    if (($main === 'universe' && $sub === 'objects') || ($main === 'universe' && $sub === 'expedition')) {
        echo '<div class="card full">';
        echo '<h4>Debris Recovery Estimator</h4>';
        echo '<div class="calc-grid">';
        echo '<label>Debris Metal<input id="debrisMetal" type="number" min="0" value="450000"></label>';
        echo '<label>Debris Crystal<input id="debrisCrystal" type="number" min="0" value="320000"></label>';
        echo '<label>Recycler Capacity per Ship<input id="recyclerCap" type="number" min="1" value="20000"></label>';
        echo '<label>Travel Time (minutes)<input id="recyclerTime" type="number" min="1" value="18"></label>';
        echo '</div>';
        echo '<button class="calc-btn" type="button" onclick="(function(){var m=Math.max(0,parseFloat(document.getElementById(\'debrisMetal\').value)||0);var c=Math.max(0,parseFloat(document.getElementById(\'debrisCrystal\').value)||0);var cap=Math.max(1,parseFloat(document.getElementById(\'recyclerCap\').value)||1);var t=Math.max(1,parseFloat(document.getElementById(\'recyclerTime\').value)||1);var total=m+c;var rec=Math.ceil(total/cap);var hourly=Math.round((60/t)*total);document.getElementById(\'debrisOut\').innerHTML=\'Total Debris: \'+Math.round(total).toLocaleString()+\' | Recyclers Needed: \'+rec.toLocaleString()+\'<br>Recovery Throughput/Hour: \'+hourly.toLocaleString()+\' resources\';})();">Estimate Recovery</button>';
        echo '<div id="debrisOut" class="calc-output">Compute recycler fleet size before dispatching recovery waves.</div>';
        echo '</div>';
    }

    if ($main === 'universe' && $sub === 'expedition') {
        echo '<div class="card full">';
        echo '<h4>Expedition Outcome Simulator</h4>';
        echo '<div class="calc-grid">';
        echo '<label>Fleet Value<input id="expFleetValue" type="number" min="1" value="650000"></label>';
        echo '<label>Escort Strength<input id="expEscort" type="number" min="0" value="120000"></label>';
        echo '<label>Astro Tech Level<input id="expAstro" type="number" min="0" value="6"></label>';
        echo '<label>Missions Today<input id="expMissions" type="number" min="0" max="20" value="3"></label>';
        echo '</div>';
        echo '<button class="calc-btn" type="button" onclick="(function(){var fv=Math.max(1,parseFloat(document.getElementById(\'expFleetValue\').value)||1);var es=Math.max(0,parseFloat(document.getElementById(\'expEscort\').value)||0);var astro=Math.max(0,parseFloat(document.getElementById(\'expAstro\').value)||0);var missions=Math.max(0,Math.min(20,parseFloat(document.getElementById(\'expMissions\').value)||0));var safeChance=Math.max(10,Math.min(96,58+(astro*2)+(es/Math.max(fv,1))*20-(missions*1.5)));var haul=Math.round((fv*0.05)+(astro*12000));var risk=Math.max(4,Math.min(80,35-(astro*1.4)+(missions*2)));document.getElementById(\'expOut\').innerHTML=\'Safe Return Chance: \'+safeChance.toFixed(1)+\'%<br>Estimated Resource Haul: \'+haul.toLocaleString()+\'<br>Incident Risk Index: \'+risk.toFixed(1)+\'%\';})();">Simulate Expedition</button>';
        echo '<div id="expOut" class="calc-output">Use this to pace daily expedition waves and avoid over-commitment.</div>';
        echo '</div>';
    }

    if ($main === 'help' && $sub === 'glossary') {
        echo '<div class="card full">';
        echo '<h4>Command Abbreviations Table</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Term</th><th align="left">Meaning</th></tr>';
        echo '<tr><td>AT</td><td>Attack Turns</td></tr>';
        echo '<tr><td>CT</td><td>Covert Turns</td></tr>';
        echo '<tr><td>MT</td><td>Market Turns</td></tr>';
        echo '<tr><td>UU</td><td>Untrained Units</td></tr>';
        echo '<tr><td>UP</td><td>Unit Production</td></tr>';
        echo '<tr><td>MS</td><td>Mothership</td></tr>';
        echo '<tr><td>TIP</td><td>Turn Income Produced</td></tr>';
        echo '<tr><td>RAL</td><td>Realm Alert Level</td></tr>';
        echo '</table>';
        echo '</div>';
    }

    if ($main === 'empire' && ($sub === 'home' || $sub === 'overview')) {
        $planetCount = count($planets);
        echo '<div class="card full">';
        echo '<h4>Empire Operations Board</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">System</th><th align="left">Current State</th><th align="left">Recommended Focus</th></tr>';
        echo '<tr><td>Economy</td><td>On Hand: ' . fnum($bank->onHand ?? 0) . '</td><td>Maintain reserve and push market optimization</td></tr>';
        echo '<tr><td>Military</td><td>Army: ' . fnum($userStats->armySize ?? 0) . '</td><td>Balance offense/defense and covert ratios</td></tr>';
        echo '<tr><td>Production</td><td>UP/Turn: ' . fnum($baseData->up ?? 0) . '</td><td>Prioritize high ROI upgrades</td></tr>';
        echo '<tr><td>Territory</td><td>Planets: ' . fnum($planetCount) . '</td><td>Schedule conquest by fleet readiness</td></tr>';
        echo '</table>';
        echo '</div>';
    }
}

function blueprintCatalog(): array {
    return [
        1 => ['name' => 'Raptor Interceptor', 'hull' => 'Interceptor', 'tier' => 1, 'copy_cost' => 75000, 'base_metal' => 4200, 'base_crystal' => 2400, 'base_deuterium' => 1200, 'base_turns' => 5, 'base_power' => 22],
        2 => ['name' => 'Valkyrie Frigate', 'hull' => 'Frigate', 'tier' => 2, 'copy_cost' => 145000, 'base_metal' => 9800, 'base_crystal' => 5600, 'base_deuterium' => 3400, 'base_turns' => 8, 'base_power' => 52],
        3 => ['name' => 'Argent Cruiser', 'hull' => 'Cruiser', 'tier' => 3, 'copy_cost' => 290000, 'base_metal' => 22000, 'base_crystal' => 13000, 'base_deuterium' => 8200, 'base_turns' => 12, 'base_power' => 124],
        4 => ['name' => 'Leviathan Battleship', 'hull' => 'Battleship', 'tier' => 4, 'copy_cost' => 520000, 'base_metal' => 46000, 'base_crystal' => 29000, 'base_deuterium' => 17000, 'base_turns' => 18, 'base_power' => 248],
        5 => ['name' => 'Aurora Carrier', 'hull' => 'Carrier', 'tier' => 5, 'copy_cost' => 880000, 'base_metal' => 78000, 'base_crystal' => 51000, 'base_deuterium' => 30000, 'base_turns' => 24, 'base_power' => 410],
        6 => ['name' => 'Aegis Dreadnought', 'hull' => 'Dreadnought', 'tier' => 6, 'copy_cost' => 1280000, 'base_metal' => 125000, 'base_crystal' => 86000, 'base_deuterium' => 52000, 'base_turns' => 32, 'base_power' => 680],
    ];
}

function blueprintEnsureTables(Game $s, array $catalog): void {
    $s->query("CREATE TABLE IF NOT EXISTS blueprint_catalog (
        blueprint_id INT NOT NULL PRIMARY KEY,
        bp_name VARCHAR(96) NOT NULL,
        hull_class VARCHAR(40) NOT NULL,
        tier INT NOT NULL DEFAULT 1,
        copy_cost INT NOT NULL DEFAULT 0,
        base_metal INT NOT NULL DEFAULT 0,
        base_crystal INT NOT NULL DEFAULT 0,
        base_deuterium INT NOT NULL DEFAULT 0,
        base_turns INT NOT NULL DEFAULT 1,
        base_power INT NOT NULL DEFAULT 0
    )");

    $s->query("CREATE TABLE IF NOT EXISTS player_blueprints (
        uid INT NOT NULL,
        blueprint_id INT NOT NULL,
        owned_copies INT NOT NULL DEFAULT 0,
        me_level INT NOT NULL DEFAULT 0,
        te_level INT NOT NULL DEFAULT 0,
        run_count INT NOT NULL DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY(uid, blueprint_id)
    )");

    $s->query("CREATE TABLE IF NOT EXISTS blueprint_hangar (
        uid INT NOT NULL,
        blueprint_id INT NOT NULL,
        hull_class VARCHAR(40) NOT NULL,
        quantity INT NOT NULL DEFAULT 0,
        total_power BIGINT NOT NULL DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY(uid, blueprint_id)
    )");

    foreach ($catalog as $id => $bp) {
        $id = (int)$id;
        $name = preg_replace('/[^A-Za-z0-9 _-]/', '', (string)$bp['name']);
        $hull = preg_replace('/[^A-Za-z0-9 _-]/', '', (string)$bp['hull']);
        $tier = (int)$bp['tier'];
        $copy = (int)$bp['copy_cost'];
        $m = (int)$bp['base_metal'];
        $c = (int)$bp['base_crystal'];
        $d = (int)$bp['base_deuterium'];
        $t = (int)$bp['base_turns'];
        $p = (int)$bp['base_power'];
        $s->query("REPLACE INTO blueprint_catalog (blueprint_id,bp_name,hull_class,tier,copy_cost,base_metal,base_crystal,base_deuterium,base_turns,base_power)
            VALUES (" . $id . ", '" . $name . "', '" . $hull . "', " . $tier . ", " . $copy . ", " . $m . ", " . $c . ", " . $d . ", " . $t . ", " . $p . ")");
    }
}

function blueprintOrderCosts(array $bp, int $qty, int $me, int $te): array {
    $qty = max(1, $qty);
    $materialFactor = max(0.55, 1 - min(0.45, $me * 0.02));
    $timeFactor = max(0.45, 1 - min(0.55, $te * 0.03));
    $metal = (int)round(((int)$bp['base_metal'] * $qty) * $materialFactor);
    $crystal = (int)round(((int)$bp['base_crystal'] * $qty) * $materialFactor);
    $deuterium = (int)round(((int)$bp['base_deuterium'] * $qty) * $materialFactor);
    $turns = max(1, (int)ceil((((int)$bp['base_turns'] * $qty) * $timeFactor) / 6));
    $power = (int)$bp['base_power'] * $qty;
    return ['metal' => $metal, 'crystal' => $crystal, 'deuterium' => $deuterium, 'turns' => $turns, 'power' => $power];
}

function universeSeedSystem(int $uid, int $index): array {
    $seed = (($uid + 97) * 1259 + ($index * 4051)) & 0x7fffffff;
    $starClasses = ['Red Dwarf', 'Yellow Main Sequence', 'Blue Giant', 'White Dwarf', 'Neutron', 'Binary'];
    $biomes = ['Lush', 'Arid', 'Frozen', 'Volcanic', 'Toxic', 'Oceanic', 'Irradiated', 'Relic'];
    $hazards = ['Calm', 'Radiation Storms', 'Acid Rain', 'Electro Tempests', 'Cryo Squalls', 'Pirate Activity'];

    $star = universePick($seed, $starClasses);
    $biome = universePick($seed, $biomes);
    $hazard = universePick($seed, $hazards);
    $richness = universeRand($seed, 20, 98);
    $sentinel = universeRand($seed, 0, 5);
    $planets = universeRand($seed, 2, 12);
    $moons = universeRand($seed, 0, 18);
    $glyphA = strtoupper(dechex(universeRand($seed, 16, 255)));
    $glyphB = strtoupper(dechex(universeRand($seed, 16, 255)));
    $seedKey = 'NMS-' . $uid . '-' . $index . '-' . $glyphA . $glyphB;

    return [
        'index' => $index,
        'seedKey' => $seedKey,
        'star' => $star,
        'biome' => $biome,
        'hazard' => $hazard,
        'richness' => $richness,
        'sentinel' => $sentinel,
        'planets' => $planets,
        'moons' => $moons,
    ];
}

function universeSeedSlice(int $uid, int $page, int $perPage): array {
    $total = 50000;
    $page = max(1, $page);
    $perPage = max(10, min(100, $perPage));
    $maxPage = max(1, (int)ceil($total / $perPage));
    if ($page > $maxPage) {
        $page = $maxPage;
    }
    $start = (($page - 1) * $perPage) + 1;
    $end = min($total, $start + $perPage - 1);

    $rows = [];
    for ($i = $start; $i <= $end; $i++) {
        $rows[] = universeSeedSystem($uid, $i);
    }

    return ['rows' => $rows, 'page' => $page, 'perPage' => $perPage, 'maxPage' => $maxPage, 'start' => $start, 'end' => $end, 'total' => $total];
}

function pageSafeToken(string $value): string {
    $clean = preg_replace('/[^A-Za-z0-9 _:\/-]/', '', $value);
    return str_replace("'", "''", $clean ?? '');
}

function militaryTroopCatalog(): array {
    $legions = ['Aegis', 'Vanguard', 'Tempest', 'Orion', 'Helios', 'Nyx', 'Atlas', 'Leviathan', 'Draco', 'Sentinel', 'Obsidian', 'Solaris'];
    $roles = [
        ['rank' => 'Cadet', 'title' => 'Initiate', 'class' => 'Line', 'subclass' => 'Infantry', 'type' => 'Assault', 'subtype' => 'Rifle', 'a1' => 'Resolve', 'a2' => 'Awareness'],
        ['rank' => 'Specialist', 'title' => 'Pathfinder', 'class' => 'Line', 'subclass' => 'Infantry', 'type' => 'Recon', 'subtype' => 'Scout', 'a1' => 'Awareness', 'a2' => 'Mobility'],
        ['rank' => 'Corporal', 'title' => 'Breacher', 'class' => 'Line', 'subclass' => 'Shock', 'type' => 'Assault', 'subtype' => 'Breach', 'a1' => 'Breach', 'a2' => 'Resolve'],
        ['rank' => 'Sergeant', 'title' => 'Shieldbearer', 'class' => 'Line', 'subclass' => 'Defender', 'type' => 'Bulwark', 'subtype' => 'Aegis', 'a1' => 'Fortitude', 'a2' => 'Discipline'],
        ['rank' => 'Staff Sergeant', 'title' => 'Siege Marshal', 'class' => 'Heavy', 'subclass' => 'Artillery', 'type' => 'Siege', 'subtype' => 'Plasma', 'a1' => 'Barrage', 'a2' => 'Control'],
        ['rank' => 'Gunnery Sergeant', 'title' => 'Bastion Gunner', 'class' => 'Heavy', 'subclass' => 'Artillery', 'type' => 'Support', 'subtype' => 'Suppressor', 'a1' => 'Control', 'a2' => 'Discipline'],
        ['rank' => 'Lieutenant', 'title' => 'Field Commander', 'class' => 'Command', 'subclass' => 'Tactics', 'type' => 'Leadership', 'subtype' => 'Battleline', 'a1' => 'Command', 'a2' => 'Tactics'],
        ['rank' => 'Captain', 'title' => 'Strike Captain', 'class' => 'Command', 'subclass' => 'Tactics', 'type' => 'Assault', 'subtype' => 'Spearhead', 'a1' => 'Command', 'a2' => 'Breach'],
        ['rank' => 'Major', 'title' => 'Vanguard Major', 'class' => 'Command', 'subclass' => 'Doctrine', 'type' => 'Coordination', 'subtype' => 'Joint Ops', 'a1' => 'Doctrine', 'a2' => 'Discipline'],
        ['rank' => 'Commander', 'title' => 'Wing Commander', 'class' => 'Aerospace', 'subclass' => 'Interdiction', 'type' => 'Air Superiority', 'subtype' => 'Interceptor', 'a1' => 'Mobility', 'a2' => 'Control'],
        ['rank' => 'Commodore', 'title' => 'Orbital Overseer', 'class' => 'Aerospace', 'subclass' => 'Orbital', 'type' => 'Support', 'subtype' => 'Orbital Fire', 'a1' => 'Command', 'a2' => 'Barrage'],
        ['rank' => 'Colonel', 'title' => 'Task Colonel', 'class' => 'Heavy', 'subclass' => 'Armor', 'type' => 'Shock', 'subtype' => 'Breaker', 'a1' => 'Fortitude', 'a2' => 'Breach'],
        ['rank' => 'Brigadier', 'title' => 'Doctrine Brigadier', 'class' => 'Command', 'subclass' => 'Doctrine', 'type' => 'Planning', 'subtype' => 'War Room', 'a1' => 'Doctrine', 'a2' => 'Command'],
        ['rank' => 'General', 'title' => 'Battle General', 'class' => 'Command', 'subclass' => 'High Command', 'type' => 'Leadership', 'subtype' => 'Theater', 'a1' => 'Command', 'a2' => 'Resolve'],
        ['rank' => 'Marshal', 'title' => 'Front Marshal', 'class' => 'Command', 'subclass' => 'High Command', 'type' => 'Leadership', 'subtype' => 'Grand Strategy', 'a1' => 'Doctrine', 'a2' => 'Discipline'],
        ['rank' => 'Shadow Operative', 'title' => 'Ghost Lance', 'class' => 'Covert', 'subclass' => 'Infiltration', 'type' => 'Stealth', 'subtype' => 'Ghost', 'a1' => 'Stealth', 'a2' => 'Awareness'],
        ['rank' => 'Phantom Operative', 'title' => 'Nightblade', 'class' => 'Covert', 'subclass' => 'Sabotage', 'type' => 'Disruption', 'subtype' => 'Saboteur', 'a1' => 'Stealth', 'a2' => 'Breach'],
        ['rank' => 'Counter Agent', 'title' => 'Signal Warden', 'class' => 'Security', 'subclass' => 'Counterintel', 'type' => 'Counter-Covert', 'subtype' => 'Interceptor', 'a1' => 'Awareness', 'a2' => 'Control'],
        ['rank' => 'Warden', 'title' => 'Citadel Warden', 'class' => 'Security', 'subclass' => 'Fortress', 'type' => 'Defense', 'subtype' => 'Sentinel', 'a1' => 'Fortitude', 'a2' => 'Discipline'],
        ['rank' => 'Ascendant', 'title' => 'Star Legate', 'class' => 'Elite', 'subclass' => 'Ascended', 'type' => 'Mythic', 'subtype' => 'Paragon', 'a1' => 'Resolve', 'a2' => 'Command'],
    ];

    $rows = [];
    $id = 1;
    foreach ($legions as $li => $legion) {
        foreach ($roles as $ri => $role) {
            $tier = $ri + 1;
            $power = (int)(80 + ($tier * 34) + ($li * 7));
            $attack = (int)round($power * (1.02 + (($ri % 3) * 0.08)));
            $defense = (int)round($power * (0.96 + (($ri % 4) * 0.07)));
            $covert = (int)round($power * (0.70 + (($ri % 5) * 0.06)));
            $anti = (int)round($power * (0.68 + (($ri % 6) * 0.05)));
            $mobility = max(24, (int)(170 - ($tier * 4) - ($li % 3)));
            $morale = (int)(52 + ($tier * 2) + ($li % 8));
            $logistics = (int)(42 + ($tier * 2) + ($li % 6));
            $tactics = (int)(30 + ($tier * 3) + ($li % 5));
            $resilience = (int)(34 + ($tier * 3) + ($li % 7));
            $discipline = (int)(38 + ($tier * 2) + ($li % 9));
            $subA = (int)(26 + ($tier * 2) + ($li % 4));
            $subB = (int)(24 + ($tier * 2) + (($li + $ri) % 6));
            $code = 'TRP-' . str_pad((string)$id, 3, '0', STR_PAD_LEFT);
            $name = $legion . ' ' . $role['title'];
            $title = $role['rank'] . ' of ' . $legion;

            $rows[] = [
                'troop_id' => $id,
                'troop_code' => $code,
                'troop_name' => $name,
                'troop_rank' => $role['rank'],
                'troop_title' => $title,
                'class_name' => $role['class'],
                'class_subclass' => $role['subclass'],
                'troop_type' => $role['type'],
                'troop_subtype' => $role['subtype'],
                'power_stat' => $power,
                'attack_stat' => $attack,
                'defense_stat' => $defense,
                'covert_stat' => $covert,
                'anti_covert_stat' => $anti,
                'mobility_stat' => $mobility,
                'morale_stat' => $morale,
                'logistics_stat' => $logistics,
                'tactic_substat' => $tactics,
                'resilience_substat' => $resilience,
                'discipline_substat' => $discipline,
                'attribute_primary' => $role['a1'],
                'attribute_secondary' => $role['a2'],
                'sub_attribute_a' => $subA,
                'sub_attribute_b' => $subB,
                'legion_name' => $legion,
                'tier' => $tier,
            ];
            $id++;
        }
    }

    return $rows;
}

function militaryTroopRoleField(array $troopMeta): string {
    $unitField = 'attack';
    $className = strtolower((string)$troopMeta['class_name']);
    $typeName = strtolower((string)$troopMeta['troop_type']);
    if ($className === 'covert') {
        $unitField = 'covert';
    }
    if ($className === 'security' || $typeName === 'counter-covert') {
        $unitField = 'anticovert';
    }
    if ($className === 'heavy' || $typeName === 'defense' || $typeName === 'bulwark') {
        $unitField = 'defense';
    }
    return $unitField;
}

function militaryRecruitCosts(array $troopMeta, int $qty): array {
    $qty = max(1, $qty);
    return [
        'turns' => max(1, (int)ceil($qty / 20)),
        'units' => $qty,
        'naq' => (int)round(((int)$troopMeta['power_stat'] * 120) * $qty),
        'food' => (int)round(((int)$troopMeta['morale_stat'] * 2) * $qty),
        'water' => (int)round(((int)$troopMeta['logistics_stat'] * 2) * $qty),
        'deuterium' => (int)round(((int)$troopMeta['mobility_stat'] * 4) * $qty),
    ];
}

function militaryRecruitApply(Game $s, int $uid, array $troopMeta, int $qty): string {
    $qty = max(1, min(500, $qty));
    $turnQ = $s->query("SELECT actionTurns FROM userdata WHERE uid=" . $uid . " LIMIT 1");
    $turns = $turnQ ? (int)($turnQ->fetch_object()->actionTurns ?? 0) : 0;
    $resQ = $s->query("SELECT metal,crystal,deuterium,food,water,population FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
    $res = $resQ ? $resQ->fetch_object() : (object)['metal' => 0, 'crystal' => 0, 'deuterium' => 0, 'food' => 0, 'water' => 0, 'population' => 0];
    $unitQ = $s->query("SELECT untrained,attack,defense,covert,anticovert FROM units WHERE uid=" . $uid . " LIMIT 1");
    $unitsObj = $unitQ ? $unitQ->fetch_object() : (object)['untrained' => 0, 'attack' => 0, 'defense' => 0, 'covert' => 0, 'anticovert' => 0];
    $bankQ = $s->query("SELECT onHand FROM bank WHERE uid=" . $uid . " LIMIT 1");
    $bankObj = $bankQ ? $bankQ->fetch_object() : (object)['onHand' => 0];

    $cost = militaryRecruitCosts($troopMeta, $qty);
    if ($turns < (int)$cost['turns']) {
        return 'Troop recruitment failed: insufficient action turns.';
    }
    if ((int)$unitsObj->untrained < (int)$cost['units']) {
        return 'Troop recruitment failed: insufficient untrained units.';
    }
    $trainedUnits = [
        'attack' => (int)$unitsObj->attack,
        'defense' => (int)$unitsObj->defense,
        'covert' => (int)$unitsObj->covert,
        'anticovert' => (int)$unitsObj->anticovert,
    ];
    if (!ArmyPolicy::canTrain($trainedUnits, (int)$qty)) {
        return 'Troop recruitment failed: base army capacity reached. Remaining trained capacity: ' . fnum(ArmyPolicy::remaining($trainedUnits)) . '.';
    }
    if ((int)$bankObj->onHand < (int)$cost['naq']) {
        return 'Troop recruitment failed: insufficient Naquadah.';
    }
    if ((int)$res->food < (int)$cost['food'] || (int)$res->water < (int)$cost['water'] || (int)$res->deuterium < (int)$cost['deuterium']) {
        return 'Troop recruitment failed: insufficient food/water/deuterium reserves.';
    }

    $unitField = militaryTroopRoleField($troopMeta);
    $xpGain = max(2, (int)ceil($qty / 10));
    $readinessGain = max(1, (int)ceil($qty / 80));

    $s->query("UPDATE bank SET onHand=onHand-" . (int)$cost['naq'] . " WHERE uid=" . $uid . " LIMIT 1");
    $s->query("UPDATE player_resources SET food=food-" . (int)$cost['food'] . ", water=water-" . (int)$cost['water'] . ", deuterium=deuterium-" . (int)$cost['deuterium'] . " WHERE uid=" . $uid . " LIMIT 1");
    $s->query("UPDATE userdata SET actionTurns=GREATEST(0,actionTurns-" . (int)$cost['turns'] . ") WHERE uid=" . $uid . " LIMIT 1");
    $s->query("UPDATE units SET untrained=untrained-" . (int)$cost['units'] . ", " . $unitField . "=" . $unitField . "+" . $qty . " WHERE uid=" . $uid . " LIMIT 1");
    $s->query("UPDATE military_command_state SET drill_xp=drill_xp+" . $xpGain . ", readiness_index=LEAST(100, readiness_index+" . $readinessGain . ") WHERE uid=" . $uid . " LIMIT 1");

    return 'Troop recruitment complete: ' . fnum($qty) . 'x ' . (string)$troopMeta['troop_name'] . ' assigned to ' . strtoupper($unitField) . ' corps.';
}

function militaryQueueProcessReady(Game $s, int $uid, array $troopById, int $limit = 25): array {
    $limit = max(1, min(100, $limit));
    $queueQ = $s->query("SELECT queue_id, troop_id, quantity, eta_seconds, priority_order, UNIX_TIMESTAMP(created_at) AS created_ts
        FROM military_troop_queue
        WHERE uid=" . $uid . " AND status='queued'
        ORDER BY priority_order ASC, queue_id ASC LIMIT " . $limit);
    $processed = 0;
    $failed = 0;
    $waiting = 0;

    if ($queueQ) {
        while ($qItem = $queueQ->fetch_object()) {
            $elapsed = max(0, time() - (int)$qItem->created_ts);
            if ($elapsed < (int)$qItem->eta_seconds) {
                $waiting++;
                continue;
            }
            $troopMeta = $troopById[(int)$qItem->troop_id] ?? null;
            if ($troopMeta === null) {
                $s->query("UPDATE military_troop_queue SET status='failed', completed_at=NOW() WHERE queue_id=" . (int)$qItem->queue_id . " AND uid=" . $uid . " LIMIT 1");
                $failed++;
                continue;
            }
            $applyResult = militaryRecruitApply($s, $uid, $troopMeta, (int)$qItem->quantity);
            if (strpos($applyResult, 'Troop recruitment complete:') === 0) {
                $s->query("UPDATE military_troop_queue SET status='done', completed_at=NOW() WHERE queue_id=" . (int)$qItem->queue_id . " AND uid=" . $uid . " LIMIT 1");
                $processed++;
            } else {
                $s->query("UPDATE military_troop_queue SET status='failed', completed_at=NOW() WHERE queue_id=" . (int)$qItem->queue_id . " AND uid=" . $uid . " LIMIT 1");
                $failed++;
            }
        }
    }

    return ['processed' => $processed, 'waiting' => $waiting, 'failed' => $failed];
}

function militaryQueueNormalizePriorities(Game $s, int $uid): void {
    $q = $s->query("SELECT queue_id FROM military_troop_queue WHERE uid=" . $uid . " AND status='queued' ORDER BY priority_order ASC, queue_id ASC");
    if (!$q) {
        return;
    }
    $prio = 1;
    while ($row = $q->fetch_object()) {
        $s->query("UPDATE military_troop_queue SET priority_order=" . $prio . " WHERE queue_id=" . (int)$row->queue_id . " AND uid=" . $uid . " LIMIT 1");
        $prio++;
    }
}

$main = isset($_GET['id']) ? preg_replace('/[^a-z]/', '', strtolower((string)$_GET['id'])) : 'empire';
$sub = isset($_GET['atype']) ? preg_replace('/[^a-z]/', '', strtolower((string)$_GET['atype'])) : '';

$mainTitles = [
    'empire' => 'Empire Command',
    'military' => 'Military Directorate',
    'operations' => 'Operations Center',
    'economy' => 'Economic Network',
    'diplomacy' => 'Diplomacy Office',
    'intel' => 'Intelligence Bureau',
    'community' => 'Community & Updates',
    'help' => 'Guides & Help Desk',
    'universe' => 'Universe Observatory',
    'research' => 'Research Directorate',
];

$mainBriefs = [
    'empire' => ['role' => 'Command Bridge', 'brief' => 'Coordinate the realm’s identity, readiness, planets, chain of command, logistics, and growth priorities.'],
    'military' => ['role' => 'Conflict and Fleet', 'brief' => 'Convert population, technology, equipment, and fleet capacity into force projection and defense.'],
    'operations' => ['role' => 'Mission Control', 'brief' => 'Plan attacks, raids, covert actions, communications, queues, and post-operation recovery.'],
    'economy' => ['role' => 'Resource Network', 'brief' => 'Manage Naquadah, strategic resources, production structures, markets, infrastructure, and treasury policy.'],
    'diplomacy' => ['role' => 'Political Network', 'brief' => 'Build alliances, commander chains, treaties, councils, relations, and secure communications.'],
    'intel' => ['role' => 'Threat Intelligence', 'brief' => 'Turn rankings, battle reports, signals, dossiers, and sector visibility into informed decisions.'],
    'community' => ['role' => 'Civilian Network', 'brief' => 'Connect commanders through forums, events, updates, contact channels, and academy guidance.'],
    'help' => ['role' => 'Operations Manual', 'brief' => 'Explain the game loop, formulas, protection rules, terminology, commands, and recovery procedures.'],
    'universe' => ['role' => 'Exploration Grid', 'brief' => 'Discover galaxies, planets, moons, stations, lanes, anomalies, expeditions, and seeded universe data.'],
    'research' => ['role' => 'Innovation Directorate', 'brief' => 'Advance technology, talents, stargate systems, laboratories, projects, and blueprint access.'],
];

$subDefaults = [
    'empire' => 'home',
    'military' => 'personnel',
    'operations' => 'attack',
    'economy' => 'banking',
    'diplomacy' => 'alliance',
    'intel' => 'rankings',
    'community' => 'forums',
    'help' => 'newplayer',
    'universe' => 'galaxies',
    'research' => 'tree',
];

$subLabels = [
    'empire' => ['home' => 'Home', 'overview' => 'Overview', 'planets' => 'Planets', 'command' => 'Command', 'progress' => 'Progression', 'logistics' => 'Logistics Hub', 'doctrine' => 'Doctrine Board'],
    'military' => ['personnel' => 'Personnel', 'troops' => 'Troop Catalog', 'armory' => 'Armory', 'training' => 'Training', 'fleet' => 'Fleet', 'navy' => 'Navy Ops', 'defensegrid' => 'Defense Grid'],
    'operations' => ['attack' => 'Attack', 'raid' => 'Raid', 'spy' => 'Spy', 'logs' => 'Combat Logs', 'commandqueue' => 'Command Queue', 'diplomacyops' => 'Diplomatic Ops'],
    'economy' => ['banking' => 'Banking', 'market' => 'Market', 'technology' => 'Technology', 'production' => 'Production', 'resources' => 'Resource Hub', 'buildings' => 'Infrastructure Systems', 'logistics' => 'Supply Logistics', 'treasury' => 'Treasury Policy'],
    'diplomacy' => ['alliance' => 'Alliance', 'relations' => 'Relations', 'messages' => 'Messages', 'commander' => 'Commander Chain', 'governance' => 'Commander Governance', 'treaties' => 'Treaties', 'councils' => 'Councils'],
    'intel' => ['rankings' => 'Rankings', 'reports' => 'Battle Reports', 'threats' => 'Threat Matrix', 'map' => 'Sector Map', 'signals' => 'Signal Watch', 'dossiers' => 'Target Dossiers'],
    'community' => ['forums' => 'Forums', 'updates' => 'Updates', 'contact' => 'Contact', 'faq' => 'FAQ', 'events' => 'Events', 'academy' => 'Academy'],
    'help' => ['newplayer' => 'New Player', 'mechanics' => 'Mechanics', 'glossary' => 'Glossary', 'support' => 'Support', 'troubleshooting' => 'Troubleshooting', 'hotkeys' => 'Quick Commands'],
    'universe' => ['galaxies' => 'Galaxies', 'planets' => 'Planets & Moons', 'objects' => 'Interstellar Objects', 'expedition' => 'Expedition', 'bases' => 'Stations & Bases', 'travel' => 'Jumpgate & Hyperspace', 'lanes' => 'Transit Lanes', 'anomalies' => 'Anomaly Index', 'seeds' => 'Universe Seeds'],
    'research' => ['tree' => 'Research Tree', 'techlib' => 'Technology Tree', 'infrastructure' => 'Tech Library Buildings', 'classes' => 'Class Library', 'talents' => 'Talent Library', 'stargate' => 'Stargate Tech', 'projects' => 'Projects', 'labs' => 'Lab Network', 'blueprints' => 'Blueprint Systems'],
];

// The full registry above keeps every legacy route valid. This smaller map is the visible command rail.
$visibleSubLabels = [
    'empire' => ['home' => 'Home', 'overview' => 'Overview', 'planets' => 'Planets', 'command' => 'Command', 'progress' => 'Progression'],
    'military' => ['personnel' => 'Personnel', 'troops' => 'Troop Catalog', 'training' => 'Training', 'fleet' => 'Fleet', 'defensegrid' => 'Defense Grid'],
    'operations' => ['attack' => 'Attack', 'raid' => 'Raid', 'spy' => 'Spy', 'logs' => 'Combat Logs', 'commandqueue' => 'Command Queue'],
    'economy' => ['banking' => 'Banking', 'market' => 'Market', 'production' => 'Production', 'resources' => 'Resource Hub', 'buildings' => 'Infrastructure'],
    'diplomacy' => ['alliance' => 'Alliance', 'relations' => 'Relations', 'messages' => 'Messages', 'commander' => 'Command Chain', 'governance' => 'Governance'],
    'intel' => ['rankings' => 'Rankings', 'reports' => 'Battle Reports', 'threats' => 'Threat Matrix', 'map' => 'Sector Map'],
    'community' => ['forums' => 'Forums', 'updates' => 'Updates', 'events' => 'Events', 'academy' => 'Academy'],
    'help' => ['newplayer' => 'New Player', 'mechanics' => 'Mechanics', 'glossary' => 'Glossary', 'support' => 'Support', 'troubleshooting' => 'Troubleshooting'],
    'universe' => ['galaxies' => 'Galaxies', 'planets' => 'Planets & Moons', 'expedition' => 'Expedition', 'bases' => 'Stations & Bases', 'travel' => 'Jumpgate'],
    'research' => ['tree' => 'Research Tree', 'techlib' => 'Technology', 'classes' => 'Class Library', 'talents' => 'Talents', 'blueprints' => 'Blueprints'],
];

$systemDetails = [
    'empire' => [
        'home' => [
            'brief' => 'Primary empire home page with strategic status, subsystem links, and planning context.',
            'functions' => ['Review command-level KPIs at a glance', 'Route quickly to core empire systems', 'Track readiness trends before committing turns'],
            'features' => ['Operational status cards', 'Subsystem routing matrix', 'Readiness and reserve indicators'],
            'logic' => ['Values blend bank, resources, personnel, and planetary data', 'Readiness is derived from economy and force balance', 'Home page is tuned for rapid command decisions'],
        ],
        'overview' => [
            'brief' => 'Central empire dashboard showing economy, army growth, and strategic readiness.',
            'functions' => ['View economy and production snapshots', 'Open base, technology, and progress modules', 'Track current military scale'],
            'features' => ['Live stat panel', 'Quick action shortcuts', 'Command feed compatible'],
            'logic' => ['Income and production are turn-based values', 'Army size updates from unit tables', 'Treasury values pull from bank and hand balances'],
        ],
        'planets' => [
            'brief' => 'Planet registry for territory visibility and expansion planning.',
            'functions' => ['List discovered planets', 'Show size and bonus metadata', 'Support growth path planning'],
            'features' => ['Table view for ownership info', 'Safe empty-state messaging', 'Integrated with empire context'],
            'logic' => ['Planet rows are loaded per player id', 'No planets returns an informative fallback', 'Bonuses act as strategic specialization signals'],
        ],
        'command' => [
            'brief' => 'Displays command structure and leadership chain context.',
            'functions' => ['Show commander and rank context', 'Jump to diplomacy relations', 'Open alliance roster tools'],
            'features' => ['Leadership summary card', 'Diplomacy shortcuts', 'Alliance workflow links'],
            'logic' => ['Commander relationship affects coordination flow', 'Race and rank influence strategic role', 'Diplomacy actions are profile-driven'],
        ],
        'progress' => [
            'brief' => 'Progression panel for growth priorities and expansion sequencing.',
            'functions' => ['Open progress dashboard', 'Present upgrade priorities', 'Guide scaling decisions'],
            'features' => ['Priority list', 'Module deep-link', 'Growth strategy guidance'],
            'logic' => ['Higher UP increases military velocity', 'Planet capacity lifts macro growth ceiling', 'Economic stability supports sustained warfare'],
        ],
    ],
    'military' => [
        'personnel' => [
            'brief' => 'Military personnel composition and combat role distribution.',
            'functions' => ['Break down unit classes', 'Expose untrained reserve depth', 'Guide training allocation'],
            'features' => ['Role-by-role unit table', 'Readable totals', 'Linked to training decisions'],
            'logic' => ['Untrained units are conversion input', 'Role balance impacts attack/defense outcomes', 'Covert and anti-covert stats affect intel warfare'],
        ],
        'troops' => [
            'brief' => 'Expanded roster library with 240 troops, ranks, titles, classes, and combat metadata.',
            'functions' => ['Browse 240 troop identities', 'Filter by class and legion doctrine', 'Compare stats, sub-stats, and attributes'],
            'features' => ['Paged 240-row catalog', 'Class filters and quick doctrine links', 'Stats and sub-attribute matrix'],
            'logic' => ['Troop rows are deterministic and balanced by tier', 'Sub-stats scale by role and legion profile', 'Attribute pairings define tactical specialization lanes'],
        ],
        'armory' => [
            'brief' => 'Equipment readiness and force amplification center.',
            'functions' => ['Open armory controls', 'Tune loadout direction', 'Prepare for mission types'],
            'features' => ['Armory quick-link', 'Readiness briefing', 'Battle prep guidance'],
            'logic' => ['Equipment investment alters combat effectiveness', 'Balanced loadouts reduce tactical gaps', 'Repairs preserve long-term force value'],
        ],
        'training' => [
            'brief' => 'Unit conversion operations from reserve to specialized roles.',
            'functions' => ['Open train and untrain modules', 'Shift force composition', 'Adjust to campaign needs'],
            'features' => ['Dual workflow for train/untrain', 'Fast operational links', 'Role conversion guidance'],
            'logic' => ['Training spends reserves into active roles', 'Untraining restores flexibility at a tradeoff', 'Composition changes should follow mission forecasts'],
        ],
        'fleet' => [
            'brief' => 'Fleet mobility and deployment readiness control.',
            'functions' => ['Open fleet dock', 'Coordinate movement posture', 'Stage force projection'],
            'features' => ['Dock entry shortcut', 'Deployment guidance', 'Readiness framing'],
            'logic' => ['Fleet positioning influences reaction speed', 'Readiness windows affect mission timing', 'Sustained operations require economy support'],
        ],
    ],
    'operations' => [
        'attack' => [
            'brief' => 'Direct strike planning and hostile target engagement.',
            'functions' => ['Open target ranking list', 'Select enemy profiles', 'Initiate offensive planning'],
            'features' => ['Targeting jump-link', 'Mission overview', 'Engagement staging cues'],
            'logic' => ['Attacks consume action turns', 'Outcome quality depends on force matchups', 'Intel prior to strike reduces risk'],
        ],
        'raid' => [
            'brief' => 'Fast resource extraction missions against exposed opponents.',
            'functions' => ['Plan high-speed raids', 'Identify weaker logistics targets', 'Cycle opportunistic operations'],
            'features' => ['Raid doctrine guidance', 'Risk-reward framing', 'Quick mission context'],
            'logic' => ['Raids prioritize economy disruption', 'Repeated raids raise retaliation probability', 'Execution cadence must respect turn budget'],
        ],
        'spy' => [
            'brief' => 'Covert intelligence collection and pre-war reconnaissance.',
            'functions' => ['Open spy module', 'Gather enemy indicators', 'Validate strike assumptions'],
            'features' => ['Spy workflow shortcut', 'Recon brief', 'Counter-risk planning cues'],
            'logic' => ['Covert success depends on role strength', 'Anti-covert defenses reduce penetration', 'Intel quality drives mission confidence'],
        ],
        'logs' => [
            'brief' => 'Post-operation analysis and outcome review center.',
            'functions' => ['Open combat logs', 'Review mission outcomes', 'Refine strategic doctrine'],
            'features' => ['Action history access', 'Debrief framing', 'Feedback loop support'],
            'logic' => ['Historical outcomes reveal matchup patterns', 'Loss analysis informs retraining', 'Frequent review improves tactical consistency'],
        ],
    ],
    'economy' => [
        'banking' => [
            'brief' => 'Treasury management for liquidity, safety, and war funding.',
            'functions' => ['Show on-hand and banked resources', 'Open bank module', 'Guide reserve strategy'],
            'features' => ['Dual-balance view', 'Direct bank access', 'Funding policy hints'],
            'logic' => ['On-hand funds support immediate actions', 'Banked funds protect longer-term reserves', 'Liquidity planning stabilizes campaign pacing'],
        ],
        'market' => [
            'brief' => 'Resource trade hub for economic optimization.',
            'functions' => ['Open market', 'Adjust resource mix', 'Capture trade opportunities'],
            'features' => ['Market shortcut', 'Trade operation context', 'Economy tuning support'],
            'logic' => ['Market timing affects purchasing power', 'Overextension can starve military spending', 'Balanced trading smooths growth volatility'],
        ],
        'technology' => [
            'brief' => 'Research and development for systemic empire scaling.',
            'functions' => ['Open technology tree', 'Prioritize upgrades', 'Improve combat and economy efficiency'],
            'features' => ['Tech module link', 'Upgrade planning overview', 'Cross-system growth context'],
            'logic' => ['Technology compounds over time', 'Research priorities should reflect strategy', 'Early economic tech often improves long-term tempo'],
        ],
        'production' => [
            'brief' => 'Production doctrine for army throughput and mining momentum.',
            'functions' => ['Advise UP investments', 'Balance miners and combat roles', 'Protect economic infrastructure'],
            'features' => ['Doctrine checklist', 'Scale-up guidance', 'Force-economy balance prompts'],
            'logic' => ['UP directly affects unit generation', 'Over-militarization can stall growth', 'Defensive coverage preserves production gains'],
        ],
        'resources' => [
            'brief' => 'strategic resource economy command for mining, sustainment, and population growth.',
            'functions' => ['Track 5 strategic resources', 'Upgrade production structures', 'Trade resources for tactical needs'],
            'features' => ['Resource stockpile view', 'Production rates by line', 'Structure level overview and controls'],
            'logic' => ['Resources tick on 30-minute cadence', 'Structure levels amplify resource rates', 'Food and water shortages reduce population'],
        ],
        'buildings' => [
            'brief' => 'Central strategic construction control for economy, facilities, lunar structures, and defense lines.',
            'functions' => ['Upgrade building catalog entries', 'Allocate strategic resources to infrastructure', 'Coordinate economy and military construction timing'],
            'features' => ['Category-based building matrix', 'Live level tracking and next-cost preview', 'Direct integration with Resource HQ, Fleet, and Hyperspace systems'],
            'logic' => ['Each building scales with tiered cost formulas', 'Energy supports advanced construction programs', 'Balanced building progression improves empire efficiency and survivability'],
        ],
    ],
    'diplomacy' => [
        'alliance' => [
            'brief' => 'Alliance coordination and bloc-level strategic organization.',
            'functions' => ['Open alliance roster', 'Coordinate member roles', 'Manage coalition focus'],
            'features' => ['Roster link', 'Coordination framing', 'Team strategy context'],
            'logic' => ['Alliance structure increases strategic reach', 'Role clarity reduces operational friction', 'Collective response deters opportunistic attacks'],
        ],
        'relations' => [
            'brief' => 'Inter-empire stance management for peace and conflict control.',
            'functions' => ['Set relation stance', 'Review profile-based options', 'Signal diplomatic intent'],
            'features' => ['Profile action shortcut', 'Stance guidance', 'Conflict-state awareness'],
            'logic' => ['Relations influence engagement probability', 'Hostile posture raises military pressure', 'Clear stance policy supports alliance coherence'],
        ],
        'messages' => [
            'brief' => 'Secure communication channel for diplomacy and operations.',
            'functions' => ['Open inbox', 'Coordinate operations', 'Exchange strategic updates'],
            'features' => ['Messaging link', 'Diplomatic communication scope', 'Operational syncing support'],
            'logic' => ['Fast communication improves response time', 'Message clarity reduces coordination errors', 'Thread discipline preserves audit context'],
        ],
        'commander' => [
            'brief' => 'Commander assignment and support-chain administration.',
            'functions' => ['Open commander tools', 'Manage parent chain context', 'Support command transfer workflows'],
            'features' => ['Commander shortcut', 'Chain visibility cues', 'Support-flow alignment'],
            'logic' => ['Command chain affects organizational flow', 'Support transfers should match hierarchy goals', 'Leadership stability improves campaign execution'],
        ],
        'governance' => [
            'brief' => 'strategic commander governance systems and policy options center.',
            'functions' => ['Manage 18 governance systems', 'Tune commander options/settings', 'Balance doctrine by campaign phase'],
            'features' => ['Governance module entry point', 'Settings and option profiles', 'Per-system visual icon matrix'],
            'logic' => ['Each governance system scales through level upgrades', 'Enabled/disabled systems alter effective strategic posture', 'Commander settings influence policy response cadence'],
        ],
    ],
    'intel' => [
        'rankings' => [
            'brief' => 'Global standings for threat assessment and target selection.',
            'functions' => ['Open rankings', 'Track rival growth', 'Discover trend changes'],
            'features' => ['Rank console link', 'Comparative visibility', 'Trend awareness'],
            'logic' => ['Rapid rank gain can indicate power spikes', 'Rank brackets can guide target difficulty', 'Ranking deltas inform risk posture'],
        ],
        'reports' => [
            'brief' => 'Mission report intelligence for operational quality control.',
            'functions' => ['Open action reports', 'Review losses and gains', 'Update mission tactics'],
            'features' => ['Report module link', 'Outcome-focused analysis flow', 'Decision feedback loop'],
            'logic' => ['Consistent report review improves efficiency', 'Loss patterns reveal composition issues', 'Action context supports tactical iteration'],
        ],
        'threats' => [
            'brief' => 'Threat matrix for hostile indicators and escalation risk.',
            'functions' => ['Surface key danger signals', 'Highlight hostile patterns', 'Guide defensive posture'],
            'features' => ['Risk checklist', 'Strategic warning panel', 'Escalation awareness'],
            'logic' => ['Repeated raid contact increases conflict probability', 'Hostile command chains can signal coalition pressure', 'Nearby growth spikes can shift regional balance'],
        ],
        'map' => [
            'brief' => 'Sector-level influence estimation using profile intelligence.',
            'functions' => ['Frame territory influence zones', 'Correlate race/rank/alliance data', 'Support expansion route planning'],
            'features' => ['Strategic mapping brief', 'Influence modeling hints', 'Expansion planning context'],
            'logic' => ['Influence follows power and alliance concentration', 'Regional pressure informs defensive placements', 'Map intelligence should be updated from fresh scans'],
        ],
    ],
    'community' => [
        'forums' => [
            'brief' => 'Community collaboration space for strategy and social coordination.',
            'functions' => ['Open forum portal', 'Join public discussions', 'Share strategic insights'],
            'features' => ['External forum link', 'Community visibility', 'Knowledge exchange channel'],
            'logic' => ['Shared intelligence can improve alliance performance', 'Public posts can reveal intent if overexposed', 'Community participation strengthens retention'],
        ],
        'updates' => [
            'brief' => 'Patch and balance awareness panel for meta adaptation.',
            'functions' => ['Open updates/faq', 'Read change notes', 'Adjust strategic priorities'],
            'features' => ['Update feed access', 'Meta-change visibility', 'Balance tracking support'],
            'logic' => ['Patch notes can shift optimal builds', 'Early adaptation yields competitive edge', 'Tracking updates reduces strategic drift'],
        ],
        'contact' => [
            'brief' => 'Staff communication lane for moderation and support routing.',
            'functions' => ['Open messaging channel', 'Report operational issues', 'Coordinate moderator follow-up'],
            'features' => ['Contact pathway shortcut', 'Escalation guidance', 'Support routing context'],
            'logic' => ['Clear issue details reduce resolution time', 'Timestamped reports improve traceability', 'Proper channel use preserves support workflow'],
        ],
        'faq' => [
            'brief' => 'Rules and common answers to reduce onboarding friction.',
            'functions' => ['Open FAQ module', 'Review core policies', 'Understand progression norms'],
            'features' => ['Policy and guidance access', 'Beginner-friendly references', 'Rule clarification hub'],
            'logic' => ['Rule knowledge prevents avoidable penalties', 'Policy alignment improves community health', 'Frequent FAQ review reduces repeated errors'],
        ],
    ],
    'help' => [
        'newplayer' => [
            'brief' => 'Step-by-step early game launch sequence for stable growth.',
            'functions' => ['Outline opening priorities', 'Guide safe expansion rhythm', 'Reduce beginner misplays'],
            'features' => ['Ordered launch checklist', 'Beginner strategy framing', 'Resource safety guidance'],
            'logic' => ['Balanced training lowers early vulnerability', 'Reserve funds protect against shocks', 'Scouting before attack improves odds'],
        ],
        'mechanics' => [
            'brief' => 'Core systems summary explaining turns, combat, and scaling.',
            'functions' => ['Explain action turn economy', 'Highlight combat score impact', 'Describe tech and support rules'],
            'features' => ['Mechanics bullet reference', 'System relationship clarity', 'Practical doctrine hints'],
            'logic' => ['Offensive actions are turn-gated', 'Military score influences ranking pressure', 'Transfer and growth systems reward planning discipline'],
        ],
        'glossary' => [
            'brief' => 'Terminology reference for all key game concepts.',
            'functions' => ['Define core resources', 'Clarify command-chain terms', 'Support quick interpretation'],
            'features' => ['Keyword definitions', 'Fast lookup format', 'New player comprehension support'],
            'logic' => ['Common vocabulary reduces coordination errors', 'Shared terminology improves alliance execution', 'Concept clarity supports faster decision cycles'],
        ],
        'support' => [
            'brief' => 'Issue reporting protocol for account and gameplay incidents.',
            'functions' => ['Provide support reporting guidance', 'Direct users to contact channels', 'Improve issue triage quality'],
            'features' => ['Support workflow brief', 'Evidence checklist cues', 'Escalation context'],
            'logic' => ['Detailed reports are resolved faster', 'Including players and timestamps improves verification', 'Channel discipline prevents lost requests'],
        ],
    ],
    'universe' => [
        'galaxies' => [
            'brief' => 'Strategic map of galaxy clusters, sector lanes, and expansion pressure points.',
            'functions' => ['Survey galaxy clusters', 'Track moon and habitability density', 'Support macro colonization pathing'],
            'features' => ['Cluster summary grid', 'Per-galaxy readiness indicators', 'Expansion lane overview'],
            'logic' => ['Galaxy spread reduces campaign congestion', 'High moon density improves tactical flexibility', 'Balanced lane usage improves long-term resilience'],
        ],
        'planets' => [
            'brief' => 'Planetary registry with moon classes, biomes, and resource signatures.',
            'functions' => ['Inspect worlds and moons', 'Prioritize colonization targets', 'Link biome profile to economy strategy'],
            'features' => ['Planet/moon table', 'Biome visibility', 'Resource profile summaries'],
            'logic' => ['Habitability influences colony slot efficiency', 'Biome composition shapes mining and defense roles', 'Moon count helps expedition staging and surveillance'],
        ],
        'objects' => [
            'brief' => 'Interstellar object scanner for debris, nebula, asteroid, and anomaly logistics.',
            'functions' => ['Review object density', 'Plan recycler and scout loops', 'Estimate anomaly opportunities'],
            'features' => ['Object matrix by galaxy', 'Debris recovery tools', 'Route planning context'],
            'logic' => ['Debris-heavy zones raise recovery value', 'Nebulae increase uncertainty in movement timing', 'Wormhole lanes can alter strike projection windows'],
        ],
        'expedition' => [
            'brief' => 'strategic expedition and colonization planner with mission control actions.',
            'functions' => ['Stage expeditions', 'Run attack/spy/raid target dispatch', 'Balance colony growth versus military readiness'],
            'features' => ['Mission matrix', 'Target dispatch controls', 'Expansion doctrine checklist'],
            'logic' => ['Expedition risk scales with mission cadence', 'Colonization should preserve reserve economy', 'Multi-front dispatch requires covert and combat redundancy'],
        ],
        'bases' => [
            'brief' => 'Orbital infrastructure command for Space Stations, Starbases, and Moon Bases.',
            'functions' => ['Upgrade orbital installations', 'Increase fleet staging capacity', 'Improve expedition safety and scanning'],
            'features' => ['Persistent base levels', 'Resource-based upgrade controls', 'Integration with fleet and expedition modules'],
            'logic' => ['Space Stations unlock deep-space logistics', 'Starbases require station maturity and improve defense projection', 'Moon Bases require Starbases and boost scan/survival multipliers'],
        ],
        'travel' => [
            'brief' => 'Hyperspace command layer for Jump Gates, Stargates, and interstellar lane routing.',
            'functions' => ['Upgrade gate infrastructure', 'Map travel routes by threat and distance', 'Launch transfer, expedition, and colonization transits'],
            'features' => ['Persistent travel routes', 'Transit queue with ETA/return states', 'Fuel and sustainment cost simulation'],
            'logic' => ['Jump Gates bootstrap lane access', 'Stargates improve deep-route safety and throughput', 'Hyperspace Core levels reduce cooldown and improve long-haul efficiency'],
        ],
    ],
    'research' => [
        'tree' => [
            'brief' => 'Master research tree with domain-tier progression, level systems, and core stat scaffolding.',
            'functions' => ['Browse research domains and tiers', 'Track level systems and XP to next tier', 'Review top-level stat and sub-stat baselines'],
            'features' => ['10-domain tree matrix', 'Level progression panel', 'Stats and sub-stats board'],
            'logic' => ['Research level scales with cumulative tech progression', 'Tier costs increase per domain stage', 'Sub-stats influence specialized outcomes'],
        ],
        'techlib' => [
            'brief' => 'Technology tree library focused on implementation branches and throughput disciplines.',
            'functions' => ['Browse technology domain ladders', 'Compare tech tier costs and power', 'Route upgrades to military or economy goals'],
            'features' => ['Per-domain technology nodes', 'Power and cost summaries', 'Cross-link to existing tech module'],
            'logic' => ['Technology throughput compounds with level systems', 'Branch selection changes empire specialization', 'Balanced sequencing reduces bottlenecks'],
        ],
        'classes' => [
            'brief' => 'Expanded doctrine class library with 90 classes and mapped subclasses, types, and sub-types.',
            'functions' => ['Inspect class doctrine models', 'Audit type and subtype coverage', 'Map classes to mission roles'],
            'features' => ['90 class rows', 'Subclass pairings', 'Type and subtype categorization'],
            'logic' => ['Class doctrine defines build intent', 'Subtype detail refines tactical usage', 'Coverage supports flexible campaign design'],
        ],
        'talents' => [
            'brief' => 'Talent library containing 240 unique entries split across research and technology branches.',
            'functions' => ['Browse research talents', 'Browse technology talents', 'Review tier and effect progression'],
            'features' => ['240 talent index', 'Branch and tier filtering table', 'Effect strings for planning'],
            'logic' => ['Talent tiers scale in progression bands', 'Branch choice impacts growth profile', 'Effects stack with tech and level systems'],
        ],
        'stargate' => [
            'brief' => 'Full Stargate technology command for gate science, power systems, fleet integration, and threat-response research.',
            'functions' => ['Upgrade Stargate-specific technologies', 'Spend Naquadah plus strategic resources on research', 'Scale deep-space mobility and defensive doctrine'],
            'features' => ['Multi-domain Stargate tech catalog', 'Per-tech level tracking', 'Integrated economy and hyperspace dependencies'],
            'logic' => ['Each upgrade scales in cost by level', 'Energy and deuterium become core late-tier constraints', 'Technology compounding improves interstellar campaign tempo'],
        ],
    ],
];

if (!isset($mainTitles[$main])) {
    $main = 'empire';
}
if ($sub === '' || !isset($subLabels[$main][$sub])) {
    $sub = $subDefaults[$main];
}

$uid = (int)$_SESSION['userid'];
$requestedPage = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$requestedPerPage = isset($_GET['pp']) ? (int)$_GET['pp'] : 50;
$cmd = isset($_GET['cmd']) ? preg_replace('/[^a-z_]/', '', strtolower((string)$_GET['cmd'])) : '';
$troopClassFilter = isset($_GET['tcclass']) ? preg_replace('/[^a-z]/', '', strtolower((string)$_GET['tcclass'])) : 'all';
$troopLegionFilter = isset($_GET['tclegion']) ? preg_replace('/[^a-z]/', '', strtolower((string)$_GET['tclegion'])) : 'all';
$troopPage = isset($_GET['tp']) ? (int)$_GET['tp'] : 1;
$troopPickId = isset($_GET['tpid']) ? (int)$_GET['tpid'] : 0;
$troopPickQty = isset($_GET['tqty']) ? (int)$_GET['tqty'] : 1;
$troopQueueId = isset($_GET['tqid']) ? (int)$_GET['tqid'] : 0;
$targetWorld = isset($_GET['target']) ? (int)$_GET['target'] : 0;
$bpId = isset($_GET['bp']) ? (int)$_GET['bp'] : 0;
$bpQty = isset($_GET['qty']) ? (int)$_GET['qty'] : 1;
$bpMode = isset($_GET['mode']) ? preg_replace('/[^a-z]/', '', strtolower((string)$_GET['mode'])) : 'me';
$s->updatePower($uid);

$baseData = $s->baseVars();
$personnel = $s->getPersonnel($uid);
$bank = $s->bank();
$userStats = $s->getUserInfo($uid);
$uCfg = universeConfig();
$planets = $s->getUserPlanets($uid);
$universeActionStatus = '';
if ($main === 'universe' && $cmd === 'colonize') {
    $universeActionStatus = universeColonizeWorld($s, $uid, $uCfg, $planets, $targetWorld);
    $planets = $s->getUserPlanets($uid);
}
$universe = buildUniverseSnapshot($uid, $planets);
$worldSlice = universeWorldSlice($uid, $planets, $uCfg, $requestedPage, $requestedPerPage);
$techView = $s->viewTech();
$researchHub = buildResearchDirectorate($uid, $techView, $personnel);
$resourceHub = resourceEnsureAndTick($s, $uid, $baseData, $planets, $techView);

$pageActionStatus = '';
$blueprintCatalog = blueprintCatalog();
$blueprintOwned = [];
$blueprintHangar = [];
$seedSlice = ['rows' => [], 'page' => 1, 'perPage' => 25, 'maxPage' => 1, 'start' => 0, 'end' => 0, 'total' => 0];
$seedBookmarks = [];

if ($main === 'military' || strpos($cmd, 'mil_') === 0) {
    $s->query("CREATE TABLE IF NOT EXISTS military_command_state (
        uid INT NOT NULL PRIMARY KEY,
        readiness_index INT NOT NULL DEFAULT 50,
        drill_xp INT NOT NULL DEFAULT 0,
        navy_focus VARCHAR(24) NOT NULL DEFAULT 'balanced',
        defense_posture VARCHAR(24) NOT NULL DEFAULT 'standard',
        logistics_posture VARCHAR(24) NOT NULL DEFAULT 'steady',
        war_games INT NOT NULL DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    $s->query("INSERT IGNORE INTO military_command_state (uid) VALUES (" . $uid . ")");
    $s->query("CREATE TABLE IF NOT EXISTS military_troop_queue (
        queue_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        uid INT NOT NULL,
        troop_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        priority_order INT NOT NULL DEFAULT 0,
        eta_seconds INT NOT NULL DEFAULT 300,
        status VARCHAR(20) NOT NULL DEFAULT 'queued',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL DEFAULT NULL
    )");
    $prioColQ = $s->query("SHOW COLUMNS FROM military_troop_queue LIKE 'priority_order'");
    $hasPriority = $prioColQ ? (int)$prioColQ->num_rows : 0;
    if ($hasPriority === 0) {
        $s->query("ALTER TABLE military_troop_queue ADD COLUMN priority_order INT NOT NULL DEFAULT 0 AFTER quantity");
    }
    $troopCatalog = militaryTroopCatalog();
    $s->query("CREATE TABLE IF NOT EXISTS military_troop_catalog (
        troop_id INT NOT NULL PRIMARY KEY,
        troop_code VARCHAR(20) NOT NULL,
        troop_name VARCHAR(120) NOT NULL,
        troop_rank VARCHAR(60) NOT NULL,
        troop_title VARCHAR(120) NOT NULL,
        class_name VARCHAR(40) NOT NULL,
        class_subclass VARCHAR(60) NOT NULL,
        troop_type VARCHAR(60) NOT NULL,
        troop_subtype VARCHAR(60) NOT NULL,
        power_stat INT NOT NULL DEFAULT 0,
        attack_stat INT NOT NULL DEFAULT 0,
        defense_stat INT NOT NULL DEFAULT 0,
        covert_stat INT NOT NULL DEFAULT 0,
        anti_covert_stat INT NOT NULL DEFAULT 0,
        mobility_stat INT NOT NULL DEFAULT 0,
        morale_stat INT NOT NULL DEFAULT 0,
        logistics_stat INT NOT NULL DEFAULT 0,
        tactic_substat INT NOT NULL DEFAULT 0,
        resilience_substat INT NOT NULL DEFAULT 0,
        discipline_substat INT NOT NULL DEFAULT 0,
        attribute_primary VARCHAR(60) NOT NULL,
        attribute_secondary VARCHAR(60) NOT NULL,
        sub_attribute_a INT NOT NULL DEFAULT 0,
        sub_attribute_b INT NOT NULL DEFAULT 0,
        legion_name VARCHAR(60) NOT NULL,
        tier INT NOT NULL DEFAULT 1
    )");
    $troopCountQ = $s->query("SELECT COUNT(*) AS c FROM military_troop_catalog");
    $troopCount = $troopCountQ ? (int)($troopCountQ->fetch_object()->c ?? 0) : 0;
    if ($troopCount !== 240) {
        foreach ($troopCatalog as $t) {
            $s->query("REPLACE INTO military_troop_catalog (
                troop_id, troop_code, troop_name, troop_rank, troop_title, class_name, class_subclass,
                troop_type, troop_subtype, power_stat, attack_stat, defense_stat, covert_stat, anti_covert_stat,
                mobility_stat, morale_stat, logistics_stat, tactic_substat, resilience_substat, discipline_substat,
                attribute_primary, attribute_secondary, sub_attribute_a, sub_attribute_b, legion_name, tier
            ) VALUES (
                " . (int)$t['troop_id'] . ",
                '" . pageSafeToken((string)$t['troop_code']) . "',
                '" . pageSafeToken((string)$t['troop_name']) . "',
                '" . pageSafeToken((string)$t['troop_rank']) . "',
                '" . pageSafeToken((string)$t['troop_title']) . "',
                '" . pageSafeToken((string)$t['class_name']) . "',
                '" . pageSafeToken((string)$t['class_subclass']) . "',
                '" . pageSafeToken((string)$t['troop_type']) . "',
                '" . pageSafeToken((string)$t['troop_subtype']) . "',
                " . (int)$t['power_stat'] . ",
                " . (int)$t['attack_stat'] . ",
                " . (int)$t['defense_stat'] . ",
                " . (int)$t['covert_stat'] . ",
                " . (int)$t['anti_covert_stat'] . ",
                " . (int)$t['mobility_stat'] . ",
                " . (int)$t['morale_stat'] . ",
                " . (int)$t['logistics_stat'] . ",
                " . (int)$t['tactic_substat'] . ",
                " . (int)$t['resilience_substat'] . ",
                " . (int)$t['discipline_substat'] . ",
                '" . pageSafeToken((string)$t['attribute_primary']) . "',
                '" . pageSafeToken((string)$t['attribute_secondary']) . "',
                " . (int)$t['sub_attribute_a'] . ",
                " . (int)$t['sub_attribute_b'] . ",
                '" . pageSafeToken((string)$t['legion_name']) . "',
                " . (int)$t['tier'] . "
            )");
        }
    }

    $troopById = [];
    foreach ($troopCatalog as $t) {
        $troopById[(int)$t['troop_id']] = $t;
    }

    if (strpos($cmd, 'mil_') === 0) {
        $turnQ = $s->query("SELECT actionTurns FROM userdata WHERE uid=" . $uid . " LIMIT 1");
        $turns = $turnQ ? (int)($turnQ->fetch_object()->actionTurns ?? 0) : 0;
        $resQ = $s->query("SELECT metal,crystal,deuterium,food,water,population FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
        $res = $resQ ? $resQ->fetch_object() : (object)['metal' => 0, 'crystal' => 0, 'deuterium' => 0, 'food' => 0, 'water' => 0, 'population' => 0];
        $unitQ = $s->query("SELECT untrained,attack,defense,covert,anticovert FROM units WHERE uid=" . $uid . " LIMIT 1");
        $unitsObj = $unitQ ? $unitQ->fetch_object() : (object)['untrained' => 0, 'attack' => 0, 'defense' => 0, 'covert' => 0, 'anticovert' => 0];
        $bankObj = $bank ?: (object)['onHand' => 0];

        if ($cmd === 'mil_personnel_drill') {
            $needTurns = 2;
            $needFood = 2600;
            $needWater = 2400;
            $needUu = 140;
            if ($turns < $needTurns) {
                $pageActionStatus = 'Personnel drill failed: insufficient action turns.';
            } elseif ((int)$res->food < $needFood || (int)$res->water < $needWater) {
                $pageActionStatus = 'Personnel drill failed: insufficient food/water reserves.';
            } elseif ((int)$unitsObj->untrained < $needUu) {
                $pageActionStatus = 'Personnel drill failed: insufficient untrained units.';
            } else {
                $atkGain = 70;
                $defGain = 45;
                $covGain = 25;
                $s->query("UPDATE player_resources SET food=food-" . $needFood . ", water=water-" . $needWater . " WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE userdata SET actionTurns=GREATEST(0,actionTurns-" . $needTurns . ") WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE units SET untrained=untrained-" . $needUu . ", attack=attack+" . $atkGain . ", defense=defense+" . $defGain . ", covert=covert+" . $covGain . " WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE military_command_state SET drill_xp=drill_xp+12, readiness_index=LEAST(100, readiness_index+4) WHERE uid=" . $uid . " LIMIT 1");
                $pageActionStatus = 'Personnel drill complete: +' . fnum($atkGain) . ' ATK, +' . fnum($defGain) . ' DEF, +' . fnum($covGain) . ' COV.';
            }
        }

        if ($cmd === 'mil_armory_refit') {
            $needTurns = 2;
            $needMetal = 18000;
            $needCrystal = 11000;
            $needDeut = 7000;
            if ($turns < $needTurns) {
                $pageActionStatus = 'Armory refit failed: insufficient action turns.';
            } elseif ((int)$res->metal < $needMetal || (int)$res->crystal < $needCrystal || (int)$res->deuterium < $needDeut) {
                $pageActionStatus = 'Armory refit failed: insufficient strategic resources.';
            } else {
                $s->query("UPDATE player_resources SET metal=metal-" . $needMetal . ", crystal=crystal-" . $needCrystal . ", deuterium=deuterium-" . $needDeut . " WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE userdata SET actionTurns=GREATEST(0,actionTurns-" . $needTurns . ") WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE shipyard SET dock_efficiency=LEAST(40, dock_efficiency+1) WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE military_command_state SET readiness_index=LEAST(100, readiness_index+3) WHERE uid=" . $uid . " LIMIT 1");
                $pageActionStatus = 'Armory refit complete: dock efficiency improved and readiness increased.';
            }
        }

        if ($cmd === 'mil_training_surge') {
            $needTurns = 1;
            $needNaq = 120000;
            if ($turns < $needTurns) {
                $pageActionStatus = 'Training surge failed: insufficient action turns.';
            } elseif ((int)$bankObj->onHand < $needNaq) {
                $pageActionStatus = 'Training surge failed: insufficient Naquadah.';
            } else {
                $crewGain = 850;
                $s->query("UPDATE bank SET onHand=onHand-" . $needNaq . " WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE userdata SET actionTurns=GREATEST(0,actionTurns-" . $needTurns . ") WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE units SET untrained=untrained+" . $crewGain . " WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE military_command_state SET logistics_posture='surge', readiness_index=LEAST(100, readiness_index+2) WHERE uid=" . $uid . " LIMIT 1");
                $pageActionStatus = 'Training surge complete: +' . fnum($crewGain) . ' untrained units ready for specialization.';
            }
        }

        if ($cmd === 'mil_fleet_wargame') {
            $needTurns = 3;
            $needDeut = 9500;
            $needFood = 6000;
            if ($turns < $needTurns) {
                $pageActionStatus = 'Fleet war-game failed: insufficient action turns.';
            } elseif ((int)$res->deuterium < $needDeut || (int)$res->food < $needFood) {
                $pageActionStatus = 'Fleet war-game failed: insufficient deuterium/food.';
            } else {
                $s->query("UPDATE player_resources SET deuterium=deuterium-" . $needDeut . ", food=food-" . $needFood . " WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE userdata SET actionTurns=GREATEST(0,actionTurns-" . $needTurns . ") WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE military_command_state SET war_games=war_games+1, readiness_index=LEAST(100, readiness_index+5), navy_focus='aggressive' WHERE uid=" . $uid . " LIMIT 1");
                $pageActionStatus = 'Fleet war-game successful: navy focus shifted to aggressive and readiness increased.';
            }
        }

        if ($cmd === 'mil_setfocus_aggressive' || $cmd === 'mil_setfocus_balanced' || $cmd === 'mil_setfocus_defensive') {
            $focus = 'balanced';
            if ($cmd === 'mil_setfocus_aggressive') {
                $focus = 'aggressive';
            }
            if ($cmd === 'mil_setfocus_defensive') {
                $focus = 'defensive';
            }
            $s->query("UPDATE military_command_state SET navy_focus='" . $focus . "' WHERE uid=" . $uid . " LIMIT 1");
            $pageActionStatus = 'Navy focus updated to ' . ucfirst($focus) . '.';
        }

        if ($cmd === 'mil_recruit_troop') {
            $qty = max(1, min(500, $troopPickQty));
            $troopMeta = $troopById[$troopPickId] ?? null;
            if ($troopMeta === null) {
                $pageActionStatus = 'Troop recruitment failed: troop profile not found.';
            } else {
                $pageActionStatus = militaryRecruitApply($s, $uid, $troopMeta, $qty);
            }
        }

        if ($cmd === 'mil_queue_recruit') {
            $qty = max(1, min(500, $troopPickQty));
            $troopMeta = $troopById[$troopPickId] ?? null;
            if ($troopMeta === null) {
                $pageActionStatus = 'Recruitment queue failed: troop profile not found.';
            } else {
                $queuedCountQ = $s->query("SELECT COUNT(*) AS c FROM military_troop_queue WHERE uid=" . $uid . " AND status='queued'");
                $queuedCount = $queuedCountQ ? (int)($queuedCountQ->fetch_object()->c ?? 0) : 0;
                if ($queuedCount >= 60) {
                    $pageActionStatus = 'Recruitment queue failed: queue is at capacity (60 queued batches).';
                } else {
                $eta = max(120, (int)(120 + ($qty * 8) + ((int)$troopMeta['tier'] * 18)));
                $prioQ = $s->query("SELECT COALESCE(MAX(priority_order), 0) AS p FROM military_troop_queue WHERE uid=" . $uid . "");
                $nextPrio = $prioQ ? ((int)($prioQ->fetch_object()->p ?? 0) + 1) : 1;
                $s->query("INSERT INTO military_troop_queue (uid, troop_id, quantity, priority_order, eta_seconds, status) VALUES (" . $uid . ", " . (int)$troopPickId . ", " . $qty . ", " . $nextPrio . ", " . $eta . ", 'queued')");
                militaryQueueNormalizePriorities($s, $uid);
                $pageActionStatus = 'Recruitment queued: ' . fnum($qty) . 'x ' . (string)$troopMeta['troop_name'] . ' (ETA ' . fnum($eta) . 's).';
                }
            }
        }

        if ($cmd === 'mil_queue_process') {
            $queueQ = $s->query("SELECT queue_id, troop_id, quantity, eta_seconds, priority_order, UNIX_TIMESTAMP(created_at) AS created_ts
                FROM military_troop_queue
                WHERE uid=" . $uid . " AND status='queued'
                ORDER BY priority_order ASC, queue_id ASC LIMIT 1");
            $qItem = $queueQ ? $queueQ->fetch_object() : null;
            if (!$qItem) {
                $pageActionStatus = 'Queue process: no queued troop batches found.';
            } else {
                $elapsed = max(0, time() - (int)$qItem->created_ts);
                if ($elapsed < (int)$qItem->eta_seconds) {
                    $remain = (int)$qItem->eta_seconds - $elapsed;
                    $pageActionStatus = 'Queue process: batch still in training (' . fnum($remain) . 's remaining).';
                } else {
                    $troopMeta = $troopById[(int)$qItem->troop_id] ?? null;
                    if ($troopMeta === null) {
                        $s->query("UPDATE military_troop_queue SET status='failed', completed_at=NOW() WHERE queue_id=" . (int)$qItem->queue_id . " AND uid=" . $uid . " LIMIT 1");
                        $pageActionStatus = 'Queue process failed: troop profile missing.';
                    } else {
                        $applyResult = militaryRecruitApply($s, $uid, $troopMeta, (int)$qItem->quantity);
                        if (strpos($applyResult, 'Troop recruitment complete:') === 0) {
                            $s->query("UPDATE military_troop_queue SET status='done', completed_at=NOW() WHERE queue_id=" . (int)$qItem->queue_id . " AND uid=" . $uid . " LIMIT 1");
                            $pageActionStatus = 'Queue process complete: ' . $applyResult;
                        } else {
                            $s->query("UPDATE military_troop_queue SET status='failed', completed_at=NOW() WHERE queue_id=" . (int)$qItem->queue_id . " AND uid=" . $uid . " LIMIT 1");
                            $pageActionStatus = 'Queue process failed: ' . $applyResult;
                        }
                    }
                }
            }
        }

        if ($cmd === 'mil_queue_process_all') {
            $sync = militaryQueueProcessReady($s, $uid, $troopById, 25);
            militaryQueueNormalizePriorities($s, $uid);
            $pageActionStatus = 'Queue process all: processed ' . fnum((int)$sync['processed']) . ', waiting ' . fnum((int)$sync['waiting']) . ', failed ' . fnum((int)$sync['failed']) . '.';
        }

        if ($cmd === 'mil_queue_cancel') {
            if ($troopQueueId <= 0) {
                $pageActionStatus = 'Queue cancel failed: invalid queue id.';
            } else {
                $rowQ = $s->query("SELECT status FROM military_troop_queue WHERE queue_id=" . $troopQueueId . " AND uid=" . $uid . " LIMIT 1");
                $row = $rowQ ? $rowQ->fetch_object() : null;
                if (!$row) {
                    $pageActionStatus = 'Queue cancel failed: queue batch not found.';
                } elseif ((string)$row->status !== 'queued') {
                    $pageActionStatus = 'Queue cancel skipped: batch is already ' . h((string)$row->status) . '.';
                } else {
                    $s->query("UPDATE military_troop_queue SET status='cancelled', completed_at=NOW() WHERE queue_id=" . $troopQueueId . " AND uid=" . $uid . " LIMIT 1");
                    militaryQueueNormalizePriorities($s, $uid);
                    $pageActionStatus = 'Queue batch #' . fnum($troopQueueId) . ' cancelled.';
                }
            }
        }

        if ($cmd === 'mil_queue_cancel_all') {
            $countQ = $s->query("SELECT COUNT(*) AS c FROM military_troop_queue WHERE uid=" . $uid . " AND status='queued'");
            $cancelCount = $countQ ? (int)($countQ->fetch_object()->c ?? 0) : 0;
            if ($cancelCount <= 0) {
                $pageActionStatus = 'Queue cancel-all skipped: no queued batches found.';
            } else {
                $s->query("UPDATE military_troop_queue SET status='cancelled', completed_at=NOW() WHERE uid=" . $uid . " AND status='queued'");
                militaryQueueNormalizePriorities($s, $uid);
                $pageActionStatus = 'Queue cancel-all complete: ' . fnum($cancelCount) . ' queued batches cancelled.';
            }
        }

        if ($cmd === 'mil_queue_retry') {
            if ($troopQueueId <= 0) {
                $pageActionStatus = 'Queue retry failed: invalid queue id.';
            } else {
                $rowQ = $s->query("SELECT status FROM military_troop_queue WHERE queue_id=" . $troopQueueId . " AND uid=" . $uid . " LIMIT 1");
                $row = $rowQ ? $rowQ->fetch_object() : null;
                if (!$row) {
                    $pageActionStatus = 'Queue retry failed: queue batch not found.';
                } else {
                    $statusNow = (string)$row->status;
                    if ($statusNow !== 'failed' && $statusNow !== 'cancelled') {
                        $pageActionStatus = 'Queue retry skipped: only failed/cancelled batches can be retried.';
                    } else {
                        $prioQ = $s->query("SELECT COALESCE(MAX(priority_order), 0) AS p FROM military_troop_queue WHERE uid=" . $uid . "");
                        $nextPrio = $prioQ ? ((int)($prioQ->fetch_object()->p ?? 0) + 1) : 1;
                        $s->query("UPDATE military_troop_queue SET status='queued', created_at=NOW(), completed_at=NULL, priority_order=" . $nextPrio . " WHERE queue_id=" . $troopQueueId . " AND uid=" . $uid . " LIMIT 1");
                        militaryQueueNormalizePriorities($s, $uid);
                        $pageActionStatus = 'Queue batch #' . fnum($troopQueueId) . ' moved back to queued status.';
                    }
                }
            }
        }

        if ($cmd === 'mil_queue_clear_history') {
            $countQ = $s->query("SELECT COUNT(*) AS c FROM military_troop_queue WHERE uid=" . $uid . " AND status<>'queued'");
            $clearCount = $countQ ? (int)($countQ->fetch_object()->c ?? 0) : 0;
            $s->query("DELETE FROM military_troop_queue WHERE uid=" . $uid . " AND status<>'queued'");
            militaryQueueNormalizePriorities($s, $uid);
            $pageActionStatus = 'Queue history cleared: removed ' . fnum($clearCount) . ' completed/cancelled/failed rows.';
        }

        if ($cmd === 'mil_queue_up' || $cmd === 'mil_queue_down') {
            if ($troopQueueId <= 0) {
                $pageActionStatus = 'Queue priority update failed: invalid queue id.';
            } else {
                $selfQ = $s->query("SELECT queue_id, priority_order, status FROM military_troop_queue WHERE queue_id=" . $troopQueueId . " AND uid=" . $uid . " LIMIT 1");
                $self = $selfQ ? $selfQ->fetch_object() : null;
                if (!$self) {
                    $pageActionStatus = 'Queue priority update failed: queue batch not found.';
                } elseif ((string)$self->status !== 'queued') {
                    $pageActionStatus = 'Queue priority update skipped: batch is already ' . h((string)$self->status) . '.';
                } else {
                    $cmp = ($cmd === 'mil_queue_up') ? '<' : '>';
                    $dir = ($cmd === 'mil_queue_up') ? 'DESC' : 'ASC';
                    $adjQ = $s->query("SELECT queue_id, priority_order FROM military_troop_queue
                        WHERE uid=" . $uid . " AND status='queued' AND priority_order " . $cmp . " " . (int)$self->priority_order . "
                        ORDER BY priority_order " . $dir . ", queue_id " . $dir . " LIMIT 1");
                    $adj = $adjQ ? $adjQ->fetch_object() : null;
                    if (!$adj) {
                        $pageActionStatus = ($cmd === 'mil_queue_up') ? 'Queue batch is already highest priority.' : 'Queue batch is already lowest priority.';
                    } else {
                        $selfPrio = (int)$self->priority_order;
                        $adjPrio = (int)$adj->priority_order;
                        $s->query("UPDATE military_troop_queue SET priority_order=" . $adjPrio . " WHERE queue_id=" . (int)$self->queue_id . " AND uid=" . $uid . " LIMIT 1");
                        $s->query("UPDATE military_troop_queue SET priority_order=" . $selfPrio . " WHERE queue_id=" . (int)$adj->queue_id . " AND uid=" . $uid . " LIMIT 1");
                        militaryQueueNormalizePriorities($s, $uid);
                        $pageActionStatus = 'Queue priority updated for batch #' . fnum((int)$self->queue_id) . '.';
                    }
                }
            }
        }

        if ($sub === 'troops' && $cmd === '') {
            $sync = militaryQueueProcessReady($s, $uid, $troopById, 10);
            militaryQueueNormalizePriorities($s, $uid);
            if ((int)$sync['processed'] > 0 || (int)$sync['failed'] > 0) {
                $pageActionStatus = 'Auto queue sync: processed ' . fnum((int)$sync['processed']) . ', failed ' . fnum((int)$sync['failed']) . ', still waiting ' . fnum((int)$sync['waiting']) . '.';
            }
        }

        if ($cmd === 'mil_defense_harden') {
            $needTurns = 2;
            $needMetal = 16000;
            $needCrystal = 9000;
            if ($turns < $needTurns) {
                $pageActionStatus = 'Defense hardening failed: insufficient action turns.';
            } elseif ((int)$res->metal < $needMetal || (int)$res->crystal < $needCrystal) {
                $pageActionStatus = 'Defense hardening failed: insufficient metal/crystal.';
            } else {
                $s->query("UPDATE player_resources SET metal=metal-" . $needMetal . ", crystal=crystal-" . $needCrystal . " WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE userdata SET actionTurns=GREATEST(0,actionTurns-" . $needTurns . ") WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE units SET defense=defense+120, anticovert=anticovert+45 WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE military_command_state SET defense_posture='fortified', readiness_index=LEAST(100, readiness_index+4) WHERE uid=" . $uid . " LIMIT 1");
                $pageActionStatus = 'Defense hardening complete: +120 defense and +45 anti-covert units integrated.';
            }
        }
    }
}

if (($main === 'research' && $sub === 'blueprints') || ($main === 'universe' && $sub === 'seeds') || strpos($cmd, 'bp_') === 0 || $cmd === 'seed_bookmark') {
    blueprintEnsureTables($s, $blueprintCatalog);
    $s->query("CREATE TABLE IF NOT EXISTS universe_seed_bookmarks (
        uid INT NOT NULL,
        seed_index INT NOT NULL,
        seed_key VARCHAR(64) NOT NULL,
        note VARCHAR(120) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(uid, seed_index)
    )");

    foreach ($blueprintCatalog as $id => $bp) {
        $s->query("INSERT IGNORE INTO player_blueprints (uid, blueprint_id) VALUES (" . $uid . ", " . (int)$id . ")");
    }

    if (strpos($cmd, 'bp_') === 0 && isset($blueprintCatalog[$bpId])) {
        $bp = $blueprintCatalog[$bpId];
        $resQ = $s->query("SELECT metal,crystal,deuterium FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
        $resObj = $resQ ? $resQ->fetch_object() : (object)['metal' => 0, 'crystal' => 0, 'deuterium' => 0];
        $turnQ = $s->query("SELECT actionTurns FROM userdata WHERE uid=" . $uid . " LIMIT 1");
        $turnObj = $turnQ ? $turnQ->fetch_object() : (object)['actionTurns' => 0];
        $bankObj = $bank ?: (object)['onHand' => 0];
        $bpRowQ = $s->query("SELECT owned_copies, me_level, te_level, run_count FROM player_blueprints WHERE uid=" . $uid . " AND blueprint_id=" . $bpId . " LIMIT 1");
        $bpRow = $bpRowQ ? $bpRowQ->fetch_object() : (object)['owned_copies' => 0, 'me_level' => 0, 'te_level' => 0, 'run_count' => 0];

        if ($cmd === 'bp_acquire') {
            $copyCost = (int)$bp['copy_cost'];
            if ((int)$bankObj->onHand < $copyCost) {
                $pageActionStatus = 'Blueprint acquisition failed: insufficient Naquadah.';
            } else {
                $s->query("UPDATE bank SET onHand=onHand-" . $copyCost . " WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE player_blueprints SET owned_copies=owned_copies+1 WHERE uid=" . $uid . " AND blueprint_id=" . $bpId . " LIMIT 1");
                $pageActionStatus = 'Blueprint acquired: ' . (string)$bp['name'] . '.';
            }
        }

        if ($cmd === 'bp_research') {
            $mode = ($bpMode === 'te') ? 'te' : 'me';
            $curLevel = (int)(($mode === 'me') ? $bpRow->me_level : $bpRow->te_level);
            $nextLevel = $curLevel + 1;
            $costM = (int)round(((int)$bp['base_metal'] * 0.60) * pow(1.22, $curLevel));
            $costC = (int)round(((int)$bp['base_crystal'] * 0.65) * pow(1.24, $curLevel));
            $costD = (int)round(((int)$bp['base_deuterium'] * 0.70) * pow(1.20, $curLevel));
            $turnCost = max(2, (int)ceil(((int)$bp['base_turns'] * $nextLevel) / 2));

            if ((int)$bpRow->owned_copies < 1) {
                $pageActionStatus = 'Blueprint research failed: acquire a copy first.';
            } elseif ((int)$turnObj->actionTurns < $turnCost) {
                $pageActionStatus = 'Blueprint research failed: insufficient action turns.';
            } elseif ((int)$resObj->metal < $costM || (int)$resObj->crystal < $costC || (int)$resObj->deuterium < $costD) {
                $pageActionStatus = 'Blueprint research failed: insufficient strategic resources.';
            } else {
                $s->query("UPDATE player_resources SET metal=metal-" . $costM . ", crystal=crystal-" . $costC . ", deuterium=deuterium-" . $costD . " WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE userdata SET actionTurns=GREATEST(0, actionTurns-" . $turnCost . ") WHERE uid=" . $uid . " LIMIT 1");
                if ($mode === 'me') {
                    $s->query("UPDATE player_blueprints SET me_level=me_level+1 WHERE uid=" . $uid . " AND blueprint_id=" . $bpId . " LIMIT 1");
                    $pageActionStatus = 'Material Efficiency upgraded for ' . (string)$bp['name'] . ' to ME ' . $nextLevel . '.';
                } else {
                    $s->query("UPDATE player_blueprints SET te_level=te_level+1 WHERE uid=" . $uid . " AND blueprint_id=" . $bpId . " LIMIT 1");
                    $pageActionStatus = 'Time Efficiency upgraded for ' . (string)$bp['name'] . ' to TE ' . $nextLevel . '.';
                }
            }
        }

        if ($cmd === 'bp_build') {
            $qty = max(1, min(200, $bpQty));
            $costs = blueprintOrderCosts($bp, $qty, (int)$bpRow->me_level, (int)$bpRow->te_level);
            if ((int)$bpRow->owned_copies < 1) {
                $pageActionStatus = 'Manufacturing failed: blueprint copy not owned.';
            } elseif ((int)$turnObj->actionTurns < (int)$costs['turns']) {
                $pageActionStatus = 'Manufacturing failed: insufficient action turns.';
            } elseif ((int)$resObj->metal < (int)$costs['metal'] || (int)$resObj->crystal < (int)$costs['crystal'] || (int)$resObj->deuterium < (int)$costs['deuterium']) {
                $pageActionStatus = 'Manufacturing failed: insufficient resources.';
            } else {
                $hull = preg_replace('/[^A-Za-z0-9 _-]/', '', (string)$bp['hull']);
                $s->query("UPDATE player_resources SET metal=metal-" . (int)$costs['metal'] . ", crystal=crystal-" . (int)$costs['crystal'] . ", deuterium=deuterium-" . (int)$costs['deuterium'] . " WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE userdata SET actionTurns=GREATEST(0, actionTurns-" . (int)$costs['turns'] . ") WHERE uid=" . $uid . " LIMIT 1");
                $s->query("INSERT INTO blueprint_hangar (uid, blueprint_id, hull_class, quantity, total_power)
                    VALUES (" . $uid . ", " . $bpId . ", '" . $hull . "', " . $qty . ", " . (int)$costs['power'] . ")
                    ON DUPLICATE KEY UPDATE quantity=quantity+" . $qty . ", total_power=total_power+" . (int)$costs['power']);
                $s->query("UPDATE player_blueprints SET run_count=run_count+" . $qty . " WHERE uid=" . $uid . " AND blueprint_id=" . $bpId . " LIMIT 1");
                $pageActionStatus = 'Manufacturing complete: ' . fnum($qty) . 'x ' . (string)$bp['name'] . ' added to blueprint hangar.';
            }
        }
    }

    if ($main === 'universe' && $cmd === 'seed_bookmark' && $targetWorld > 0) {
        $seedSys = universeSeedSystem($uid, $targetWorld);
        $note = preg_replace('/[^A-Za-z0-9 _:-]/', '', $seedSys['star'] . ' | ' . $seedSys['biome']);
        $seedKey = preg_replace('/[^A-Za-z0-9-]/', '', (string)$seedSys['seedKey']);
        $s->query("INSERT IGNORE INTO universe_seed_bookmarks (uid, seed_index, seed_key, note)
            VALUES (" . $uid . ", " . $targetWorld . ", '" . $seedKey . "', '" . $note . "')");
        $pageActionStatus = 'Universe seed bookmarked: #' . $targetWorld . ' (' . $seedSys['seedKey'] . ').';
    }

    $ownedQ = $s->query("SELECT blueprint_id, owned_copies, me_level, te_level, run_count FROM player_blueprints WHERE uid=" . $uid);
    if ($ownedQ) {
        while ($r = $ownedQ->fetch_assoc()) {
            $blueprintOwned[(int)$r['blueprint_id']] = [
                'owned_copies' => (int)$r['owned_copies'],
                'me_level' => (int)$r['me_level'],
                'te_level' => (int)$r['te_level'],
                'run_count' => (int)$r['run_count'],
            ];
        }
    }

    $hangarQ = $s->query("SELECT blueprint_id, quantity, total_power FROM blueprint_hangar WHERE uid=" . $uid);
    if ($hangarQ) {
        while ($r = $hangarQ->fetch_assoc()) {
            $blueprintHangar[(int)$r['blueprint_id']] = ['quantity' => (int)$r['quantity'], 'total_power' => (int)$r['total_power']];
        }
    }

    if ($main === 'universe' && $sub === 'seeds') {
        $seedSlice = universeSeedSlice($uid, $requestedPage, 25);
        $bookQ = $s->query("SELECT seed_index, seed_key, note, created_at FROM universe_seed_bookmarks WHERE uid=" . $uid . " ORDER BY seed_index ASC LIMIT 40");
        if ($bookQ) {
            while ($r = $bookQ->fetch_assoc()) {
                $seedBookmarks[] = $r;
            }
        }
    }
}

$title = $mainTitles[$main];
$subTitle = $subLabels[$main][$sub];

echo '<div class="page-hub">';
$domain = $mainBriefs[$main] ?? ['role' => 'Command Module', 'brief' => 'Server-authoritative command interface.'];
$activeDetail = $systemDetails[$main][$sub] ?? ['brief' => $domain['brief'], 'functions' => [], 'features' => [], 'logic' => []];
echo '<div class="page-hub-head redesigned-page-head">';
echo '<div class="page-head-kicker"><span class="rts-kicker">UNIVERSE CIVILIZATION // ' . h(strtoupper($domain['role'])) . '</span><span class="page-route">' . h($main) . ' / ' . h($sub) . '</span></div>';
echo '<h3>' . h($title) . ' <span class="page-title-divider">/</span> ' . h($subTitle) . '</h3>';
echo '<p>' . h($domain['brief']) . '</p>';
echo '<div class="page-head-status"><span><strong>Function:</strong> ' . h($activeDetail['functions'][0] ?? 'Review the active command module') . '</span><span><strong>Logic:</strong> ' . h($activeDetail['logic'][0] ?? 'All actions are validated and resolved on the server') . '</span></div>';
echo '</div>';
if ($universeActionStatus !== '') {
    echo '<div class="card full"><strong>' . h($universeActionStatus) . '</strong></div>';
}
if ($pageActionStatus !== '') {
    echo '<div class="card full"><strong>' . h($pageActionStatus) . '</strong></div>';
}

echo '<div class="page-subnav-title">' . h($domain['role']) . ' // Subsystems and Page Routes</div>';
echo '<div class="page-subnav">';
foreach (($visibleSubLabels[$main] ?? $subLabels[$main]) as $subKey => $subName) {
    $activeClass = ($subKey === $sub) ? ' class="active"' : '';
    echo '<a' . $activeClass . ' href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'' . h($main) . '\',\'' . h($subKey) . '\'); return false">' . h($subName) . '</a>';
}
echo '</div>';

$featureButtons = [
    'empire' => [
        ['label' => 'Base', 'js' => "sendData('base','get','mainDisplay'); return false"],
        ['label' => 'Progress', 'js' => "sendData('progress','get','mainDisplay'); return false"],
        ['label' => 'Bank', 'js' => "sendData('bank','get','mainDisplay'); return false"],
        ['label' => 'Research', 'js' => "sendData('pages','get','research','tree'); return false"],
        ['label' => 'Logistics Hub', 'js' => "sendData('pages','get','empire','logistics'); return false"],
    ],
    'military' => [
        ['label' => 'Armory', 'js' => "sendData('armory','get','mainDisplay'); return false"],
        ['label' => 'Training', 'js' => "sendData('train','get','mainDisplay'); return false"],
        ['label' => 'Troop Catalog', 'js' => "sendData('pages','get','military','troops'); return false"],
        ['label' => 'Fleet Dock', 'js' => "sendData('fleetdock','get','mainDisplay'); return false"],
        ['label' => 'Navy Ops', 'js' => "sendData('pages','get','military','navy'); return false"],
        ['label' => 'Mega Forge', 'js' => "sendData('megaforge','get','mainDisplay'); return false"],
        ['label' => 'Stations', 'js' => "sendData('stations','get','mainDisplay'); return false"],
        ['label' => 'Hyperspace', 'js' => "sendData('hyperspace','get','mainDisplay'); return false"],
    ],
    'operations' => [
        ['label' => 'Targets', 'js' => "sendData('rank','get','mainDisplay'); return false"],
        ['label' => 'Spy', 'js' => "sendData('spy','get','mainDisplay'); return false"],
        ['label' => 'Combat Logs', 'js' => "sendData('logs','get','mainDisplay'); return false"],
        ['label' => 'Action Reports', 'js' => "sendData('actionLogs','get','mainDisplay'); return false"],
        ['label' => 'Command Queue', 'js' => "sendData('pages','get','operations','commandqueue'); return false"],
    ],
    'economy' => [
        ['label' => 'Bank', 'js' => "sendData('bank','get','mainDisplay'); return false"],
        ['label' => 'Market', 'js' => "sendData('market','get','mainDisplay'); return false"],
        ['label' => 'Resource HQ', 'js' => "sendData('resourcehq','get','mainDisplay'); return false"],
        ['label' => 'Infrastructure Systems', 'js' => "sendData('infrastructure','get','mainDisplay'); return false"],
        ['label' => 'Supply Logistics', 'js' => "sendData('pages','get','economy','logistics'); return false"],
        ['label' => 'Technology', 'js' => "sendData('technology','get','mainDisplay'); return false"],
        ['label' => 'Stargate Tech', 'js' => "sendData('stargatetech','get','mainDisplay'); return false"],
    ],
    'diplomacy' => [
        ['label' => 'Messages', 'js' => "sendData('messages','get','mainDisplay'); return false"],
        ['label' => 'Alliance', 'js' => "sendData('ally_mlist','get','mainDisplay'); return false"],
        ['label' => 'Relations', 'js' => "sendData('pages','get','diplomacy','relations'); return false"],
        ['label' => 'Treaties', 'js' => "sendData('pages','get','diplomacy','treaties'); return false"],
        ['label' => 'Commander Systems', 'js' => "sendData('commandergov','get','mainDisplay'); return false"],
    ],
    'intel' => [
        ['label' => 'Rankings', 'js' => "sendData('rank','get','mainDisplay'); return false"],
        ['label' => 'Reports', 'js' => "sendData('actionLogs','get','mainDisplay'); return false"],
        ['label' => 'Signal Watch', 'js' => "sendData('pages','get','intel','signals'); return false"],
        ['label' => 'Spy', 'js' => "sendData('spy','get','mainDisplay'); return false"],
    ],
    'community' => [
        ['label' => 'Forums', 'js' => "window.open('forums/','_blank'); return false"],
        ['label' => 'Updates', 'js' => "sendData('faq','get','mainDisplay'); return false"],
        ['label' => 'Contact', 'js' => "sendData('messages','get','mainDisplay'); return false"],
    ],
    'help' => [
        ['label' => 'Guide', 'js' => "sendData('pages','get','help','newplayer'); return false"],
        ['label' => 'Mechanics', 'js' => "sendData('pages','get','help','mechanics'); return false"],
        ['label' => 'Glossary', 'js' => "sendData('pages','get','help','glossary'); return false"],
        ['label' => 'Troubleshooting', 'js' => "sendData('pages','get','help','troubleshooting'); return false"],
    ],
    'universe' => [
        ['label' => 'Galaxy Map', 'js' => "sendData('pages','get','universe','galaxies'); return false"],
        ['label' => 'Transit Lanes', 'js' => "sendData('pages','get','universe','lanes'); return false"],
        ['label' => 'Stations', 'js' => "sendData('stations','get','mainDisplay'); return false"],
        ['label' => 'Hyperspace', 'js' => "sendData('hyperspace','get','mainDisplay'); return false"],
        ['label' => 'Expedition', 'js' => "sendData('pages','get','universe','expedition'); return false"],
    ],
    'research' => [
        ['label' => 'Research Tree', 'js' => "sendData('pages','get','research','tree'); return false"],
        ['label' => 'Technology Tree', 'js' => "sendData('pages','get','research','techlib'); return false"],
        ['label' => 'Tech Library Buildings', 'js' => "sendData('techlib','get','mainDisplay'); return false"],
        ['label' => 'Projects', 'js' => "sendData('pages','get','research','projects'); return false"],
        ['label' => 'Classes', 'js' => "sendData('pages','get','research','classes'); return false"],
        ['label' => 'Talents', 'js' => "sendData('pages','get','research','talents'); return false"],
        ['label' => 'Stargate Tech', 'js' => "sendData('stargatetech','get','mainDisplay'); return false"],
    ],
];

if (isset($featureButtons[$main]) && count($featureButtons[$main]) > 0) {
    echo '<div class="page-subnav-title">Feature Actions</div>';
    echo '<div class="page-subnav feature-subnav">';
    foreach ($featureButtons[$main] as $btn) {
        echo '<a href="javascript:void(0)" onclick="' . h($btn['js']) . '">' . h($btn['label']) . '</a>';
    }
    echo '</div>';
}

$subPageGroups = [
    'empire' => [
        'Command Home' => [
            ['home', 'Empire Home'],
            ['overview', 'Operations Overview'],
            ['command', 'Command Structure'],
        ],
        'Empire Layers' => [
            ['logistics', 'Logistics Hub'],
            ['doctrine', 'Doctrine Board'],
        ],
    ],
    'military' => [
        'Command Layers' => [
            ['personnel', 'Personnel'],
            ['troops', 'Troop Catalog'],
            ['fleet', 'Fleet'],
            ['navy', 'Navy Ops'],
            ['defensegrid', 'Defense Grid'],
        ],
    ],
    'operations' => [
        'Mission Routing' => [
            ['attack', 'Attack Missions'],
            ['raid', 'Raid Missions'],
            ['spy', 'Spy Network'],
            ['commandqueue', 'Command Queue'],
            ['diplomacyops', 'Diplomatic Ops'],
        ],
    ],
    'economy' => [
        'Economic Layers' => [
            ['banking', 'Banking'],
            ['resources', 'Resource Hub'],
            ['logistics', 'Supply Logistics'],
            ['treasury', 'Treasury Policy'],
        ],
    ],
    'diplomacy' => [
        'Diplomacy Layers' => [
            ['relations', 'Relations'],
            ['governance', 'Commander Governance'],
            ['treaties', 'Treaties'],
            ['councils', 'Councils'],
        ],
    ],
    'intel' => [
        'Intelligence Layers' => [
            ['rankings', 'Rankings'],
            ['reports', 'Battle Reports'],
            ['signals', 'Signal Watch'],
            ['dossiers', 'Target Dossiers'],
        ],
    ],
    'community' => [
        'Community Layers' => [
            ['forums', 'Forums'],
            ['events', 'Events'],
            ['academy', 'Academy'],
        ],
    ],
    'help' => [
        'Help Layers' => [
            ['newplayer', 'New Player'],
            ['mechanics', 'Mechanics'],
            ['troubleshooting', 'Troubleshooting'],
            ['hotkeys', 'Quick Commands'],
        ],
    ],
    'universe' => [
        'Universe Layers' => [
            ['galaxies', 'Galaxies'],
            ['planets', 'Planets & Moons'],
            ['lanes', 'Transit Lanes'],
            ['anomalies', 'Anomaly Index'],
        ],
    ],
    'research' => [
        'Research Layers' => [
            ['tree', 'Research Tree'],
            ['techlib', 'Technology Tree'],
            ['projects', 'Projects'],
            ['labs', 'Lab Network'],
        ],
    ],
];

// The condensed visibleSubLabels rail replaces the older duplicated grouped-submenu card.
renderInfoBlock($activeDetail);
echo '<div class="page-grid">';

if ($main === 'empire' && $sub === 'home') {
    $armySize = (int)($userStats->armySize ?? 0);
    $treasury = (int)($bank->onHand ?? 0);
    $income = (int)($baseData->income ?? 0);
    $up = (int)($baseData->up ?? 0);
    $planetCount = count($planets);
    $turnQ = $s->query("SELECT actionTurns FROM userdata WHERE uid=" . (int)$uid . " LIMIT 1");
    $actionTurns = $turnQ ? (int)($turnQ->fetch_object()->actionTurns ?? 0) : 0;
    $reservePressure = max(0, (int)round(($income > 0) ? ($treasury / $income) : 0));
    $readiness = min(100, max(1, (int)round((($up / 1000) * 25) + (($armySize / 100000) * 35) + (($planetCount / max(1, (int)$uCfg['maxColonies'])) * 20) + (($treasury / 1000000) * 20))));
    $colonyCap = max(1, (int)$uCfg['maxColonies']);
    $colonyUsage = (int)round(($planetCount / $colonyCap) * 100);
    $warPosture = ($readiness >= 62 || ($armySize >= 180000 && $up >= 260));
    $postureLabel = $warPosture ? 'War Posture' : 'Growth Posture';
    $critTurns = $warPosture ? 10 : 6;
    $warnTurns = $warPosture ? 18 : 12;
    $critReserve = $warPosture ? 6 : 4;
    $warnReserve = $warPosture ? 10 : 7;
    $critReadiness = $warPosture ? 48 : 35;
    $warnReadiness = $warPosture ? 66 : 52;
    $warnColonyUsage = $warPosture ? 85 : 93;
    $warnUpFloor = $warPosture ? 260 : 150;
    $foodFloor = $warPosture ? max(22000, (int)$resourceHub['rates']['food'] * 4) : max(14000, (int)$resourceHub['rates']['food'] * 3);
    $waterFloor = $warPosture ? max(22000, (int)$resourceHub['rates']['water'] * 4) : max(14000, (int)$resourceHub['rates']['water'] * 3);
    $energyFloor = $warPosture ? max(22000, (int)$resourceHub['rates']['energy'] * 4) : max(14000, (int)$resourceHub['rates']['energy'] * 3);
    $alerts = [];

    if ($actionTurns <= $critTurns) {
        $alerts[] = ['level' => 'Critical', 'message' => 'Low action turns (' . fnum($actionTurns) . '). Prioritize turn-efficient moves.'];
    } elseif ($actionTurns <= $warnTurns) {
        $alerts[] = ['level' => 'Warning', 'message' => 'Action turns are tightening (' . fnum($actionTurns) . '). Queue only high-value actions.'];
    }

    if ($reservePressure <= $critReserve) {
        $alerts[] = ['level' => 'Critical', 'message' => 'Reserve runway is short (' . fnum($reservePressure) . ' turns). Stabilize treasury before escalation.'];
    } elseif ($reservePressure <= $warnReserve) {
        $alerts[] = ['level' => 'Warning', 'message' => 'Reserve runway is moderate (' . fnum($reservePressure) . ' turns). Limit optional spending.'];
    }

    if ((int)$resourceHub['current']['food'] < $foodFloor || (int)$resourceHub['current']['water'] < $waterFloor || (int)$resourceHub['current']['energy'] < $energyFloor) {
        $alerts[] = ['level' => 'Critical', 'message' => 'Food/Water/Energy buffers are low. Population stability risk is rising.'];
    }

    if ($up < $warnUpFloor) {
        $alerts[] = ['level' => 'Warning', 'message' => 'Unit production is under tactical pace (' . fnum($up) . '/turn). Consider progression and infrastructure upgrades.'];
    }

    if ($colonyUsage >= $warnColonyUsage) {
        $alerts[] = ['level' => 'Warning', 'message' => 'Colony capacity is near cap (' . fnum($planetCount) . '/' . fnum($colonyCap) . '). Plan expansion unlocks.'];
    }

    if ($readiness <= $critReadiness) {
        $alerts[] = ['level' => 'Critical', 'message' => 'Readiness is low (' . fnum($readiness) . '%). Avoid multi-front operations until stabilized.'];
    } elseif ($readiness <= $warnReadiness) {
        $alerts[] = ['level' => 'Warning', 'message' => 'Readiness is moderate (' . fnum($readiness) . '%). Use selective operations and preserve reserves.'];
    }

    if (count($alerts) === 0) {
        $alerts[] = ['level' => 'Stable', 'message' => 'All core command indicators are stable. Safe window for growth or offensive planning.'];
    }

    echo '<div class="card full">';
    echo '<h4>Empire Home Alerts</h4>';
    echo '<table class="mini-table" border="0" width="100%">';
    echo '<tr><th align="left">Operational Posture</th><td>' . h($postureLabel) . '</td></tr>';
    echo '<tr><th align="left">Severity</th><th align="left">Alert</th></tr>';
    foreach ($alerts as $alert) {
        echo '<tr><td><strong>' . h($alert['level']) . '</strong></td><td>' . h($alert['message']) . '</td></tr>';
    }
    echo '</table>';
    echo '</div>';

    echo '<div class="card">';
    echo '<h4>Empire Home Snapshot</h4>';
    echo '<p><strong>Readiness Index:</strong> ' . fnum($readiness) . '%</p>';
    echo '<p><strong>Treasury On Hand:</strong> ' . fnum($treasury) . ' Naquadah</p>';
    echo '<p><strong>Army Size:</strong> ' . fnum($armySize) . '</p>';
    echo '<p><strong>Planets Controlled:</strong> ' . fnum($planetCount) . ' / ' . fnum((int)$uCfg['maxColonies']) . '</p>';
    echo '</div>';

    echo '<div class="card">';
    echo '<h4>Command Signals</h4>';
    echo '<p><strong>Income/Turn:</strong> ' . fnum($income) . '</p>';
    echo '<p><strong>Unit Production/Turn:</strong> ' . fnum($up) . '</p>';
    echo '<p><strong>Reserve Coverage:</strong> ' . fnum($reservePressure) . ' turns</p>';
    echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'empire\',\'overview\'); return false">Open Operations Overview</a></p>';
    echo '</div>';

    echo '<div class="card full">';
    echo '<h4>Empire Systems Home Matrix</h4>';
    echo '<table class="mini-table" border="0" width="100%">';
    echo '<tr><th align="left">System</th><th align="left">Current Detail</th><th align="left">Primary Action</th><th align="left">Information Focus</th></tr>';
    echo '<tr><td>Territory</td><td>' . fnum($planetCount) . ' colonies with moon and bonus metadata</td><td><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'empire\',\'planets\'); return false">Open Planets</a></td><td>Expansion slots, biome potential, moon utility</td></tr>';
    echo '<tr><td>Command</td><td>Commander chain, rank, and alliance posture</td><td><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'empire\',\'command\'); return false">Open Command</a></td><td>Diplomatic leverage and coordination tempo</td></tr>';
    echo '<tr><td>Progression</td><td>Growth pacing and upgrade sequencing</td><td><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'empire\',\'progress\'); return false">Open Progress</a></td><td>UP scaling, planet cap, economy acceleration</td></tr>';
    echo '<tr><td>Logistics</td><td>Resource routing and reserve floor management</td><td><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'empire\',\'logistics\'); return false">Open Logistics</a></td><td>War spend discipline and buffer policy</td></tr>';
    echo '<tr><td>Doctrine</td><td>War, economy, and intel posture alignment</td><td><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'empire\',\'doctrine\'); return false">Open Doctrine</a></td><td>Campaign stance and risk governance</td></tr>';
    echo '</table>';
    echo '</div>';

    echo '<div class="card full">';
    echo '<h4>Home Information Board</h4>';
    echo '<p><strong>Operational Guidance:</strong> Use this home page to validate reserve health, force readiness, and expansion headroom before issuing attack, research, or build commands.</p>';
    echo '<p><strong>Recommended Loop:</strong> Home -> Logistics -> Military/Fleet -> Operations -> Home (re-check readiness).</p>';
    echo '</div>';
}

if ($main === 'empire' && $sub === 'overview') {
    echo '<div class="card"><h4>Empire Snapshot</h4>';
    echo '<p><strong>Army Size:</strong> ' . fnum($userStats->armySize ?? 0) . '</p>';
    echo '<p><strong>Treasury:</strong> ' . fnum($bank->onHand ?? 0) . ' Naquadah</p>';
    echo '<p><strong>Income/Turn:</strong> ' . fnum($baseData->income ?? 0) . '</p>';
    echo '<p><strong>Unit Production:</strong> ' . fnum($baseData->up ?? 0) . '</p>';
    echo '</div>';
    echo '<div class="card"><h4>Quick Actions</h4>';
    echo '<p><a href="javascript:void(0)" onclick="sendData(\'base\',\'get\',\'mainDisplay\'); return false">Open Base Module</a></p>';
    echo '<p><a href="javascript:void(0)" onclick="sendData(\'technology\',\'get\',\'mainDisplay\'); return false">Open Technology</a></p>';
    echo '<p><a href="javascript:void(0)" onclick="sendData(\'progress\',\'get\',\'mainDisplay\'); return false">Open Progress</a></p>';
    echo '</div>';

    echo '<div class="card full"><h4>Seven-Resource Command Stockpile</h4>';
    echo '<table class="mini-table" border="0" width="100%"><tr><th align="left">Resource</th><th align="left">Current</th><th align="left">Production / Turn</th></tr>';
    echo '<tr><td>Metal</td><td>' . fnum($resourceHub['current']['metal']) . '</td><td>' . fnum($resourceHub['rates']['metal']) . '</td></tr>';
    echo '<tr><td>Crystal</td><td>' . fnum($resourceHub['current']['crystal']) . '</td><td>' . fnum($resourceHub['rates']['crystal']) . '</td></tr>';
    echo '<tr><td>Deuterium</td><td>' . fnum($resourceHub['current']['deuterium']) . '</td><td>' . fnum($resourceHub['rates']['deuterium']) . '</td></tr>';
    echo '<tr><td>Food</td><td>' . fnum($resourceHub['current']['food']) . '</td><td>' . fnum($resourceHub['rates']['food']) . '</td></tr>';
    echo '<tr><td>Water</td><td>' . fnum($resourceHub['current']['water']) . '</td><td>' . fnum($resourceHub['rates']['water']) . '</td></tr>';
    echo '<tr><td>Population</td><td>' . fnum($resourceHub['current']['population']) . '</td><td>' . fnum($resourceHub['rates']['population']) . '</td></tr>';
    echo '<tr><td>Energy</td><td>' . fnum($resourceHub['current']['energy']) . '</td><td>' . fnum($resourceHub['rates']['energy']) . '</td></tr>';
    echo '</table></div>';
}

if ($main === 'empire' && $sub === 'planets') {
    echo '<div class="card full"><h4>Planet Registry</h4>';
    echo '<p><strong>Colonization Capacity:</strong> ' . fnum($uCfg['maxColonies']) . ' worlds | <strong>Moon Capacity:</strong> ' . fnum($uCfg['maxMoons']) . '</p>';
    echo '<p><strong>Owned Colonies:</strong> ' . fnum(count($planets)) . ' | <strong>Open Colony Slots:</strong> ' . fnum(max(0, $uCfg['maxColonies'] - count($planets))) . '</p>';
    if (count($planets) === 0) {
        echo '<p>No planets discovered in your registry yet.</p>';
    } else {
        echo '<table width="100%" border="0"><tr><th align="left">Planet</th><th align="left">Size</th><th align="left">Bonus</th><th align="left">Moons</th><th align="left">Moon Class</th><th align="left">World Slot</th></tr>';
        foreach ($planets as $idx => $planet) {
            $moonSeed = (($uid + 31) * 103 + (($idx + 1) * 17)) & 0x7fffffff;
            $moonCount = universeRand($moonSeed, 0, 3);
            $moonClass = $moonCount > 0 ? universePick($moonSeed, ['Rocky', 'Icy', 'Metallic', 'Ruined']) : '-';
            echo '<tr><td>' . h($planet['name']) . '</td><td>' . h($planet['size']) . '</td><td>' . h($planet['bonus']) . '</td><td>' . fnum($moonCount) . '</td><td>' . h($moonClass) . '</td><td>#' . fnum($idx + 1) . '</td></tr>';
        }
        echo '</table>';
    }
    echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'expedition\'); return false">Open Colonization Mission Control</a></p>';
    echo '</div>';
}

if ($main === 'empire' && $sub === 'command') {
    echo '<div class="card"><h4>Command Chain</h4>';
    echo '<p><strong>Commander:</strong> ' . h($userStats->cmdrName ?? 'None') . '</p>';
    echo '<p><strong>Race:</strong> ' . h($userStats->race ?? '') . '</p>';
    echo '<p><strong>Rank:</strong> ' . h($userStats->rank ?? '') . '</p>';
    echo '</div>';
    echo '<div class="card"><h4>Diplomatic Actions</h4>';
    echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'diplomacy\',\'relations\'); return false">Manage Relations</a></p>';
    echo '<p><a href="javascript:void(0)" onclick="sendData(\'ally_mlist\',\'get\',\'mainDisplay\'); return false">Alliance Member List</a></p>';
    echo '</div>';
}

if ($main === 'empire' && $sub === 'progress') {
    echo '<div class="card"><h4>Progress Status</h4>';
    echo '<p>Track your expansion level, unit production growth, and military readiness.</p>';
    echo '<p><a href="javascript:void(0)" onclick="sendData(\'progress\',\'get\',\'mainDisplay\'); return false">Open Progress Dashboard</a></p>';
    echo '</div>';
    echo '<div class="card"><h4>Upgrade Priorities</h4>';
    echo '<ul><li>Increase Unit Production</li><li>Expand Planet Capacity</li><li>Boost Economy/Turn</li></ul>';
    echo '</div>';
}

if ($main === 'empire' && $sub === 'logistics') {
    echo '<div class="card"><h4>Logistics Hub</h4><p>Route resources between economy, war, and expansion programs with a stable reserve floor.</p></div>';
    echo '<div class="card"><h4>Active Supply Links</h4><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'economy\',\'resources\'); return false">Resource Hub</a></p><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'economy\',\'logistics\'); return false">Supply Logistics</a></p></div>';
}

if ($main === 'empire' && $sub === 'doctrine') {
    echo '<div class="card full"><h4>Doctrine Board</h4><p>Set command posture for conflict, growth, and intelligence in one synchronized board.</p><table class="mini-table" border="0" width="100%"><tr><th align="left">Track</th><th align="left">Current Focus</th></tr><tr><td>War</td><td>Fleet pressure with controlled risk</td></tr><tr><td>Economy</td><td>Compounding production and reserve retention</td></tr><tr><td>Intel</td><td>Scouting before commitment</td></tr></table></div>';
}

if ($main === 'military') {
    $mStateQ = $s->query("SELECT readiness_index, drill_xp, navy_focus, defense_posture, logistics_posture, war_games FROM military_command_state WHERE uid=" . $uid . " LIMIT 1");
    $mState = $mStateQ ? $mStateQ->fetch_object() : (object)['readiness_index' => 50, 'drill_xp' => 0, 'navy_focus' => 'balanced', 'defense_posture' => 'standard', 'logistics_posture' => 'steady', 'war_games' => 0];

    echo '<div class="card full"><h4>Military Command Console</h4>';
    echo '<p><strong>Readiness:</strong> ' . fnum((int)$mState->readiness_index) . '% | <strong>Drill XP:</strong> ' . fnum((int)$mState->drill_xp) . ' | <strong>Navy Focus:</strong> ' . h((string)$mState->navy_focus) . ' | <strong>Defense Posture:</strong> ' . h((string)$mState->defense_posture) . ' | <strong>War Games:</strong> ' . fnum((int)$mState->war_games) . '</p>';
    echo '<div class="page-subnav feature-subnav">';
    echo '<a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'' . h($sub) . '&cmd=mil_personnel_drill\'); return false">Personnel Drill</a>';
    echo '<a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'' . h($sub) . '&cmd=mil_armory_refit\'); return false">Armory Refit</a>';
    echo '<a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'' . h($sub) . '&cmd=mil_training_surge\'); return false">Training Surge</a>';
    echo '<a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'' . h($sub) . '&cmd=mil_fleet_wargame\'); return false">Fleet War-Game</a>';
    echo '<a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'' . h($sub) . '&cmd=mil_defense_harden\'); return false">Defense Harden</a>';
    echo '</div>';
    echo '<p style="margin-top:8px;"><strong>Navy Focus Sub Buttons:</strong> '
        . '<a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'' . h($sub) . '&cmd=mil_setfocus_aggressive\'); return false">Aggressive</a> | '
        . '<a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'' . h($sub) . '&cmd=mil_setfocus_balanced\'); return false">Balanced</a> | '
        . '<a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'' . h($sub) . '&cmd=mil_setfocus_defensive\'); return false">Defensive</a></p>';
    echo '<p><label>Command Dropdown '
        . '<select id="milCommandDropdown">'
        . '<option value="mil_personnel_drill">Personnel Drill</option>'
        . '<option value="mil_armory_refit">Armory Refit</option>'
        . '<option value="mil_training_surge">Training Surge</option>'
        . '<option value="mil_fleet_wargame">Fleet War-Game</option>'
        . '<option value="mil_defense_harden">Defense Harden</option>'
        . '<option value="mil_setfocus_aggressive">Set Focus: Aggressive</option>'
        . '<option value="mil_setfocus_balanced">Set Focus: Balanced</option>'
        . '<option value="mil_setfocus_defensive">Set Focus: Defensive</option>'
        . '</select></label> '
        . '<a href="javascript:void(0)" onclick="(function(){var x=document.getElementById(\'milCommandDropdown\'); if(x){sendData(\'pages\',\'get\',\'military\',\'' . h($sub) . '&cmd=\'+x.value);} return false;})(); return false">Execute Command</a></p>';
    echo '</div>';

    if ($sub === 'personnel') {
        echo '<div class="card full"><h4>Personnel Breakdown</h4>';
        echo '<table width="100%" border="0">';
        echo '<tr><td>Untrained Units</td><td>' . fnum($personnel->uuCount ?? 0) . '</td></tr>';
        echo '<tr><td>Attack Units</td><td>' . fnum($personnel->attackCount ?? 0) . '</td></tr>';
        echo '<tr><td>Defense Units</td><td>' . fnum($personnel->defenseCount ?? 0) . '</td></tr>';
        echo '<tr><td>Covert Units</td><td>' . fnum($personnel->covertCount ?? 0) . '</td></tr>';
        echo '<tr><td>Anti-Covert Units</td><td>' . fnum($personnel->anticovertCount ?? 0) . '</td></tr>';
        echo '</table>';
        echo '<div class="page-subnav feature-subnav"><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'personnel&cmd=mil_personnel_drill\'); return false">Run Personnel Drill</a><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'training&cmd=mil_training_surge\'); return false">Run Training Surge</a></div>';
        echo '</div>';
    }
    if ($sub === 'troops') {
        $allTroops = militaryTroopCatalog();
        $classOptions = ['all' => 'All Classes'];
        $legionOptions = ['all' => 'All Legions'];
        foreach ($allTroops as $tt) {
            $ck = strtolower((string)$tt['class_name']);
            $lk = strtolower((string)$tt['legion_name']);
            if (!isset($classOptions[$ck])) {
                $classOptions[$ck] = (string)$tt['class_name'];
            }
            if (!isset($legionOptions[$lk])) {
                $legionOptions[$lk] = (string)$tt['legion_name'];
            }
        }
        if (!isset($classOptions[$troopClassFilter])) {
            $troopClassFilter = 'all';
        }
        if (!isset($legionOptions[$troopLegionFilter])) {
            $troopLegionFilter = 'all';
        }

        $filteredTroops = [];
        foreach ($allTroops as $tt) {
            $classMatch = ($troopClassFilter === 'all') || (strtolower((string)$tt['class_name']) === $troopClassFilter);
            $legionMatch = ($troopLegionFilter === 'all') || (strtolower((string)$tt['legion_name']) === $troopLegionFilter);
            if ($classMatch && $legionMatch) {
                $filteredTroops[] = $tt;
            }
        }

        $perPage = 24;
        $totalTroops = count($filteredTroops);
        $maxTroopPage = max(1, (int)ceil($totalTroops / $perPage));
        $troopPage = max(1, min($maxTroopPage, $troopPage));
        $startIndex = ($troopPage - 1) * $perPage;
        $sliceTroops = array_slice($filteredTroops, $startIndex, $perPage);

        echo '<div class="card full"><h4>240 Troop Rank, Title, and Attribute Library</h4>';
        echo '<p><strong>Total Troops:</strong> 240 | <strong>Classes:</strong> ' . fnum(count($classOptions) - 1) . ' | <strong>Legions:</strong> ' . fnum(count($legionOptions) - 1) . '</p>';
        echo '<p><strong>Filters:</strong> Class=' . h($classOptions[$troopClassFilter]) . ' | Legion=' . h($legionOptions[$troopLegionFilter]) . ' | Showing ' . fnum($startIndex + 1) . '-' . fnum(min($totalTroops, $startIndex + $perPage)) . ' of ' . fnum($totalTroops) . '</p>';
        echo '<div class="page-subnav feature-subnav">';
        foreach ($classOptions as $classKey => $className) {
            echo '<a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'troops&tcclass=' . h($classKey) . '&tclegion=' . h($troopLegionFilter) . '&tp=1\'); return false">' . h($className) . '</a>';
        }
        echo '</div>';
        echo '<div class="page-subnav feature-subnav">';
        foreach ($legionOptions as $legionKey => $legionName) {
            echo '<a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'troops&tcclass=' . h($troopClassFilter) . '&tclegion=' . h($legionKey) . '&tp=1\'); return false">' . h($legionName) . '</a>';
        }
        echo '</div>';
        echo '</div>';

        echo '<div class="card full"><h4>Troop Recruitment Control</h4>';
        echo '<p>Recruit selected troop profiles directly from untrained reserves into military corps using turns and resources.</p>';
        echo '<p><label>Troop Profile '
            . '<select id="troopRecruitSelect">';
        foreach ($sliceTroops as $tt) {
            echo '<option value="' . (int)$tt['troop_id'] . '">' . h((string)$tt['troop_code']) . ' - ' . h((string)$tt['troop_rank']) . ' - ' . h((string)$tt['troop_name']) . '</option>';
        }
        echo '</select></label> '
            . '<label>Quantity <input id="troopRecruitQty" type="number" min="1" max="500" value="25" style="width:80px;" /></label> '
            . '<a href="javascript:void(0)" onclick="(function(){var p=document.getElementById(\'troopRecruitSelect\');var q=document.getElementById(\'troopRecruitQty\');if(p&&q){var qv=parseInt(q.value,10);if(!qv||qv<1){qv=1;}if(qv>500){qv=500;}sendData(\'pages\',\'get\',\'military\',\'troops&tcclass=' . h($troopClassFilter) . '&tclegion=' . h($troopLegionFilter) . '&tp=' . $troopPage . '&cmd=mil_recruit_troop&tpid=\'+p.value+\'&tqty=\'+qv);}return false;})(); return false">Recruit Instantly</a> | '
            . '<a href="javascript:void(0)" onclick="(function(){var p=document.getElementById(\'troopRecruitSelect\');var q=document.getElementById(\'troopRecruitQty\');if(p&&q){var qv=parseInt(q.value,10);if(!qv||qv<1){qv=1;}if(qv>500){qv=500;}sendData(\'pages\',\'get\',\'military\',\'troops&tcclass=' . h($troopClassFilter) . '&tclegion=' . h($troopLegionFilter) . '&tp=' . $troopPage . '&cmd=mil_queue_recruit&tpid=\'+p.value+\'&tqty=\'+qv);}return false;})(); return false">Add To Queue</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'troops&tcclass=' . h($troopClassFilter) . '&tclegion=' . h($troopLegionFilter) . '&tp=' . $troopPage . '&cmd=mil_queue_process\'); return false">Process Next Ready Queue Batch</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'troops&tcclass=' . h($troopClassFilter) . '&tclegion=' . h($troopLegionFilter) . '&tp=' . $troopPage . '&cmd=mil_queue_process_all\'); return false">Process All Ready Queue Batches</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'troops&tcclass=' . h($troopClassFilter) . '&tclegion=' . h($troopLegionFilter) . '&tp=' . $troopPage . '&cmd=mil_queue_cancel_all\'); return false">Cancel All Queued Batches</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'troops&tcclass=' . h($troopClassFilter) . '&tclegion=' . h($troopLegionFilter) . '&tp=' . $troopPage . '&cmd=mil_queue_clear_history\'); return false">Clear Completed/Cancelled/Failed History</a></p>';
        echo '</div>';

        $queueRows = [];
        $queueQ = $s->query("SELECT queue_id, troop_id, quantity, priority_order, eta_seconds, status, UNIX_TIMESTAMP(created_at) AS created_ts
            FROM military_troop_queue
            WHERE uid=" . $uid . "
            ORDER BY status='queued' DESC, priority_order ASC, queue_id ASC LIMIT 12");
        if ($queueQ) {
            while ($qr = $queueQ->fetch_assoc()) {
                $queueRows[] = $qr;
            }
        }

        echo '<div class="card full"><h4>Recruitment Queue</h4>';
        if (count($queueRows) === 0) {
            echo '<p>No queued troop batches yet.</p>';
        } else {
            echo '<table class="mini-table" border="0" width="100%">';
            echo '<tr><th align="left">Queue ID</th><th align="left">Priority</th><th align="left">Troop</th><th align="left">Qty</th><th align="left">ETA</th><th align="left">Status</th><th align="left">Action</th></tr>';
            foreach ($queueRows as $qr) {
                $tId = (int)($qr['troop_id'] ?? 0);
                $tName = isset($troopById[$tId]) ? (string)$troopById[$tId]['troop_name'] : ('Troop #' . $tId);
                $prioNum = (int)($qr['priority_order'] ?? 0);
                $etaSec = (int)($qr['eta_seconds'] ?? 0);
                $createdTs = (int)($qr['created_ts'] ?? time());
                $elapsed = max(0, time() - $createdTs);
                $remaining = max(0, $etaSec - $elapsed);
                $statusName = (string)($qr['status'] ?? 'queued');
                $etaText = ($statusName === 'queued') ? (fnum($remaining) . 's') : '0s';
                echo '<tr>';
                echo '<td>#' . fnum((int)$qr['queue_id']) . '</td>';
                echo '<td>' . fnum($prioNum) . '</td>';
                echo '<td>' . h($tName) . '</td>';
                echo '<td>' . fnum((int)($qr['quantity'] ?? 0)) . '</td>';
                echo '<td>' . h($etaText) . '</td>';
                echo '<td>' . h($statusName) . '</td>';
                if ($statusName === 'queued') {
                    echo '<td><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'troops&tcclass=' . h($troopClassFilter) . '&tclegion=' . h($troopLegionFilter) . '&tp=' . $troopPage . '&cmd=mil_queue_up&tqid=' . (int)$qr['queue_id'] . '\'); return false">Up</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'troops&tcclass=' . h($troopClassFilter) . '&tclegion=' . h($troopLegionFilter) . '&tp=' . $troopPage . '&cmd=mil_queue_down&tqid=' . (int)$qr['queue_id'] . '\'); return false">Down</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'troops&tcclass=' . h($troopClassFilter) . '&tclegion=' . h($troopLegionFilter) . '&tp=' . $troopPage . '&cmd=mil_queue_cancel&tqid=' . (int)$qr['queue_id'] . '\'); return false">Cancel</a></td>';
                } elseif ($statusName === 'failed' || $statusName === 'cancelled') {
                    echo '<td><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'troops&tcclass=' . h($troopClassFilter) . '&tclegion=' . h($troopLegionFilter) . '&tp=' . $troopPage . '&cmd=mil_queue_retry&tqid=' . (int)$qr['queue_id'] . '\'); return false">Retry</a></td>';
                } else {
                    echo '<td>-</td>';
                }
                echo '</tr>';
            }
            echo '</table>';
        }
        echo '</div>';

        echo '<div class="card full"><h4>Troop Matrix</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Code</th><th align="left">Name</th><th align="left">Rank & Title</th><th align="left">Class Tree</th><th align="left">Type Tree</th><th align="left">Stats</th><th align="left">Sub Stats / Attributes</th><th align="left">Action</th></tr>';
        foreach ($sliceTroops as $tt) {
            $statsText = 'PWR ' . fnum((int)$tt['power_stat'])
                . ' | ATK ' . fnum((int)$tt['attack_stat'])
                . ' | DEF ' . fnum((int)$tt['defense_stat'])
                . ' | COV ' . fnum((int)$tt['covert_stat'])
                . ' | A-COV ' . fnum((int)$tt['anti_covert_stat'])
                . ' | MOB ' . fnum((int)$tt['mobility_stat'])
                . ' | MOR ' . fnum((int)$tt['morale_stat'])
                . ' | LOG ' . fnum((int)$tt['logistics_stat']);
            $subText = 'TAC ' . fnum((int)$tt['tactic_substat'])
                . ' | RES ' . fnum((int)$tt['resilience_substat'])
                . ' | DIS ' . fnum((int)$tt['discipline_substat'])
                . ' | ' . h((string)$tt['attribute_primary']) . '/' . h((string)$tt['attribute_secondary'])
                . ' | SUB-A ' . fnum((int)$tt['sub_attribute_a'])
                . ' | SUB-B ' . fnum((int)$tt['sub_attribute_b']);
            echo '<tr>';
            echo '<td>' . h((string)$tt['troop_code']) . '</td>';
            echo '<td>' . h((string)$tt['troop_name']) . ' <br><small>Legion: ' . h((string)$tt['legion_name']) . ' | Tier ' . fnum((int)$tt['tier']) . '</small></td>';
            echo '<td>' . h((string)$tt['troop_rank']) . '<br><small>' . h((string)$tt['troop_title']) . '</small></td>';
            echo '<td>' . h((string)$tt['class_name']) . ' / ' . h((string)$tt['class_subclass']) . '</td>';
            echo '<td>' . h((string)$tt['troop_type']) . ' / ' . h((string)$tt['troop_subtype']) . '</td>';
            echo '<td>' . $statsText . '</td>';
            echo '<td>' . $subText . '</td>';
            echo '<td><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'troops&tcclass=' . h($troopClassFilter) . '&tclegion=' . h($troopLegionFilter) . '&tp=' . $troopPage . '&cmd=mil_recruit_troop&tpid=' . (int)$tt['troop_id'] . '&tqty=10\'); return false">Recruit x10</a></td>';
            echo '</tr>';
        }
        echo '</table>';
        echo '<p><strong>Pages:</strong> ';
        for ($p = 1; $p <= $maxTroopPage; $p++) {
            $label = ($p === $troopPage) ? ('[' . $p . ']') : (string)$p;
            echo '<a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'troops&tcclass=' . h($troopClassFilter) . '&tclegion=' . h($troopLegionFilter) . '&tp=' . $p . '\'); return false">' . h($label) . '</a> ';
        }
        echo '</p>';
        echo '</div>';
    }
    if ($sub === 'armory') {
        echo '<div class="card"><h4>Armory Control</h4><p>Manage attack/defense equipment loadouts and repair weapons.</p><p><a href="javascript:void(0)" onclick="sendData(\'armory\',\'get\',\'mainDisplay\'); return false">Open Armory</a></p><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'armory&cmd=mil_armory_refit\'); return false">Run Armory Refit</a></p></div>';
    }
    if ($sub === 'training') {
        echo '<div class="card"><h4>Training Command</h4><p>Convert untrained units into combat-ready specialists.</p><p><a href="javascript:void(0)" onclick="sendData(\'train\',\'get\',\'mainDisplay\'); return false">Open Training</a></p></div>';
        echo '<div class="card"><h4>Demobilization</h4><p>Reverse assignments when strategy shifts.</p><p><a href="javascript:void(0)" onclick="sendData(\'untrain\',\'get\',\'mainDisplay\'); return false">Open Untrain</a></p><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'training&cmd=mil_training_surge\'); return false">Run Training Surge</a></p></div>';
    }
    if ($sub === 'fleet') {
        echo '<div class="card"><h4>Fleet Operations</h4><p>Deploy, reposition, and monitor fleet readiness.</p><p><a href="javascript:void(0)" onclick="sendData(\'fleetdock\',\'get\',\'mainDisplay\'); return false">Open Fleet Dock</a></p><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'objects\'); return false">Scan Debris Fields</a></p></div>';
        echo '<div class="card"><h4>Shipyard and Mothership Controls</h4><p><a href="javascript:void(0)" onclick="sendData(\'fleetdock\',\'get\',\'upgrade_shipyard\'); return false">Upgrade Shipyard</a></p><p><a href="javascript:void(0)" onclick="sendData(\'fleetdock\',\'get\',\'upgrade_bay\'); return false">Upgrade Mothership Bay</a></p><p><a href="javascript:void(0)" onclick="sendData(\'fleetdock\',\'get\',\'mainDisplay\',\'build\'); return false">Open Starship Factory Build Page</a></p><p><a href="javascript:void(0)" onclick="sendData(\'fleetdock\',\'get\',\'mainDisplay\',\'catalog\'); return false">Open 90 Starship Catalog</a></p><p><a href="javascript:void(0)" onclick="sendData(\'fleetdock\',\'get\',\'mainDisplay\',\'classes\'); return false">Open Class and Subclass Matrix</a></p><p><a href="javascript:void(0)" onclick="sendData(\'fleetdock\',\'get\',\'mainDisplay\',\'types\'); return false">Open Type and Subtype Matrix</a></p><p><a href="javascript:void(0)" onclick="sendData(\'megaforge\',\'get\',\'mainDisplay\'); return false">Open 90-Class Mega Forge</a></p></div>';
        echo '<div class="card"><h4>Orbital Installations</h4><p>Expand stations and bases to improve fleet staging and defensive projection.</p><p><a href="javascript:void(0)" onclick="sendData(\'stations\',\'get\',\'mainDisplay\'); return false">Open Stations Command</a></p><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'bases\'); return false">Open Universe Base Matrix</a></p></div>';
        echo '<div class="card"><h4>Interstellar Travel Network</h4><p>Use Jump Gates, Stargates, and hyperspace lanes for long-range force projection.</p><p><a href="javascript:void(0)" onclick="sendData(\'hyperspace\',\'get\',\'mainDisplay\'); return false">Open Hyperspace Transit Command</a></p><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'travel\'); return false">Open Universe Travel Matrix</a></p></div>';
    }
    if ($sub === 'navy') {
        echo '<div class="card"><h4>Navy Operations</h4><p>Coordinate fleet waves, escort sequencing, and timing windows by operation type.</p><p><a href="javascript:void(0)" onclick="sendData(\'fleetdock\',\'get\',\'mainDisplay\'); return false">Open Fleet Dock</a></p><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'navy&cmd=mil_fleet_wargame\'); return false">Run Fleet War-Game</a></p><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'navy&cmd=mil_setfocus_aggressive\'); return false">Set Aggressive Focus</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'navy&cmd=mil_setfocus_defensive\'); return false">Set Defensive Focus</a></p></div>';
    }
    if ($sub === 'defensegrid') {
        echo '<div class="card full"><h4>Defense Grid</h4><p>Layer defense systems across planets, stations, and fleet routes to reduce raid exposure.</p><p><a href="javascript:void(0)" onclick="sendData(\'stations\',\'get\',\'mainDisplay\'); return false">Open Stations Defense</a></p><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'defensegrid&cmd=mil_defense_harden\'); return false">Run Defense Hardening</a></p></div>';
    }
}

if ($main === 'operations') {
    if ($sub === 'attack') {
        echo '<div class="card"><h4>Attack Missions</h4><p>Launch direct strikes against enemy empires.</p><p><a href="javascript:void(0)" onclick="sendData(\'rank\',\'get\',\'mainDisplay\'); return false">Select Targets</a></p></div>';
    }
    if ($sub === 'raid') {
        echo '<div class="card"><h4>Raid Missions</h4><p>Execute high-speed resource raids for rapid gains.</p><p>Use player profiles to trigger raid actions.</p></div>';
    }
    if ($sub === 'spy') {
        echo '<div class="card"><h4>Spy Network</h4><p>Gather intel before committing forces.</p><p><a href="javascript:void(0)" onclick="sendData(\'spy\',\'get\',\'mainDisplay\'); return false">Open Spy Module</a></p></div>';
    }
    if ($sub === 'logs') {
        echo '<div class="card"><h4>Combat Logs</h4><p>Review outcomes and refine strategy.</p><p><a href="javascript:void(0)" onclick="sendData(\'logs\',\'get\',\'mainDisplay\'); return false">Open Logs</a></p></div>';
    }
    if ($sub === 'commandqueue') {
        echo '<div class="card full"><h4>Command Queue</h4><p>Queue mission phases: recon, strike, raid, and recovery to avoid turn waste.</p><table class="mini-table" border="0" width="100%"><tr><th align="left">Phase</th><th align="left">Recommended Action</th></tr><tr><td>1</td><td>Spy and verify defenses</td></tr><tr><td>2</td><td>Launch primary attack wave</td></tr><tr><td>3</td><td>Execute raid follow-up</td></tr><tr><td>4</td><td>Recover and reposition</td></tr></table></div>';
    }
    if ($sub === 'diplomacyops') {
        echo '<div class="card"><h4>Diplomatic Operations</h4><p>Use messages and relation changes to reduce escalation before or after operations.</p><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'diplomacy\',\'messages\'); return false">Open Messaging</a></p></div>';
    }
}

if ($main === 'economy') {
    if ($sub === 'banking') {
        echo '<div class="card"><h4>Banking Control</h4>';
        echo '<p><strong>On Hand:</strong> ' . fnum($bank->onHand ?? 0) . '</p>';
        echo '<p><strong>In Bank:</strong> ' . fnum($bank->inBank ?? 0) . '</p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'bank\',\'get\',\'mainDisplay\'); return false">Open Bank Module</a></p>';
        echo '</div>';

        echo '<div class="card full"><h4>Resource Vaults</h4>';
        echo '<p><strong>Metal:</strong> ' . fnum($resourceHub['current']['metal']) . ' | <strong>Crystal:</strong> ' . fnum($resourceHub['current']['crystal']) . ' | <strong>Deuterium:</strong> ' . fnum($resourceHub['current']['deuterium']) . '</p>';
        echo '<p><strong>Food:</strong> ' . fnum($resourceHub['current']['food']) . ' | <strong>Water:</strong> ' . fnum($resourceHub['current']['water']) . ' | <strong>Population:</strong> ' . fnum($resourceHub['current']['population']) . ' | <strong>Energy:</strong> ' . fnum($resourceHub['current']['energy']) . '</p>';
        echo '</div>';
    }
    if ($sub === 'market') {
        echo '<div class="card"><h4>Market Trade</h4><p>Buy and sell resources to tune your economy.</p><p><a href="javascript:void(0)" onclick="sendData(\'market\',\'get\',\'mainDisplay\'); return false">Open Market</a></p></div>';
    }
    if ($sub === 'technology') {
        echo '<div class="card"><h4>Technology Tree</h4><p>Advance economy, combat, covert, and Stargate-era systems.</p><p><a href="javascript:void(0)" onclick="sendData(\'technology\',\'get\',\'mainDisplay\'); return false">Open Technology</a></p><p><a href="javascript:void(0)" onclick="sendData(\'stargatetech\',\'get\',\'mainDisplay\'); return false">Open Stargate Technology Command</a></p></div>';
    }
    if ($sub === 'production') {
        echo '<div class="card"><h4>Production Planning</h4><p>Focus on unit production and mining throughput to scale your empire.</p><ul><li>Upgrade UP first for faster growth</li><li>Balance miners vs combat readiness</li><li>Protect income assets with defense</li></ul></div>';
        echo '<div class="card"><h4>Resource Command</h4><p><a href="javascript:void(0)" onclick="sendData(\'resourcehq\',\'get\',\'mainDisplay\'); return false">Open Resource HQ</a></p></div>';
        echo '<div class="card"><h4>Infrastructure Build Grid</h4><p><a href="javascript:void(0)" onclick="sendData(\'infrastructure\',\'get\',\'mainDisplay\'); return false">Open Infrastructure Command</a></p></div>';

        echo '<div class="card full"><h4>Strategic Resource Output Grid</h4>';
        echo '<table class="mini-table" border="0" width="100%"><tr><th align="left">Line</th><th align="left">Per Turn</th><th align="left">Notes</th></tr>';
        echo '<tr><td>Metal Mines</td><td>' . fnum($resourceHub['rates']['metal']) . '</td><td>Primary build material for warships and infrastructure.</td></tr>';
        echo '<tr><td>Crystal Plants</td><td>' . fnum($resourceHub['rates']['crystal']) . '</td><td>Advanced systems and tech fabrication material.</td></tr>';
        echo '<tr><td>Deuterium Synthesizers</td><td>' . fnum($resourceHub['rates']['deuterium']) . '</td><td>Fuel and high-tier fleet operations resource.</td></tr>';
        echo '<tr><td>Hydroponics (Food)</td><td>' . fnum($resourceHub['rates']['food']) . '</td><td>Population upkeep and colony stability.</td></tr>';
        echo '<tr><td>Atmospheric Condensers (Water)</td><td>' . fnum($resourceHub['rates']['water']) . '</td><td>Life support and growth multiplier.</td></tr>';
        echo '<tr><td>Population Growth</td><td>' . fnum($resourceHub['rates']['population']) . '</td><td>Workforce growth with food/water dependence.</td></tr>';
        echo '<tr><td>Energy Reactors</td><td>' . fnum($resourceHub['rates']['energy']) . '</td><td>Power grid output for gates, bases, and industry.</td></tr>';
        echo '</table></div>';
    }

    if ($sub === 'resources') {
        echo '<div class="card"><h4>Resource Headquarters</h4>';
        echo '<p>Manage strategic resource mining, food and water sustainment, and population growth.</p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'resourcehq\',\'get\',\'mainDisplay\'); return false">Open Resource HQ Module</a></p>';
        echo '</div>';

        echo '<div class="card"><h4>Current Structure Levels</h4>';
        echo '<p><strong>Metal Mine:</strong> ' . fnum($resourceHub['structures']['metal_mine']) . '</p>';
        echo '<p><strong>Crystal Lab:</strong> ' . fnum($resourceHub['structures']['crystal_lab']) . '</p>';
        echo '<p><strong>Deuterium Refinery:</strong> ' . fnum($resourceHub['structures']['deuterium_refinery']) . '</p>';
        echo '<p><strong>Hydroponics:</strong> ' . fnum($resourceHub['structures']['hydroponics']) . '</p>';
        echo '<p><strong>Water Plant:</strong> ' . fnum($resourceHub['structures']['water_plant']) . '</p>';
        echo '<p><strong>Habitat Dome:</strong> ' . fnum($resourceHub['structures']['habitat_dome']) . '</p>';
        echo '<p><strong>Energy Reactor:</strong> ' . fnum($resourceHub['structures']['energy_reactor']) . '</p>';
        echo '</div>';

        echo '<div class="card full"><h4>Resource Status</h4>';
        echo '<p><strong>Metal:</strong> ' . fnum($resourceHub['current']['metal']) . ' | <strong>Crystal:</strong> ' . fnum($resourceHub['current']['crystal']) . ' | <strong>Deuterium:</strong> ' . fnum($resourceHub['current']['deuterium']) . '</p>';
        echo '<p><strong>Food:</strong> ' . fnum($resourceHub['current']['food']) . ' | <strong>Water:</strong> ' . fnum($resourceHub['current']['water']) . ' | <strong>Population:</strong> ' . fnum($resourceHub['current']['population']) . ' | <strong>Energy:</strong> ' . fnum($resourceHub['current']['energy']) . '</p>';
        echo '</div>';
    }

    if ($sub === 'buildings') {
        echo '<div class="card"><h4>Infrastructure Matrix</h4>';
        echo '<p>Build and upgrade classic structures across resources, facilities, lunar systems, and defenses.</p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'infrastructure\',\'get\',\'mainDisplay\'); return false">Open Infrastructure Command</a></p>';
        echo '</div>';

        echo '<div class="card"><h4>Build Strategy</h4>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'resourcehq\',\'get\',\'mainDisplay\'); return false">Resource HQ</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'fleetdock\',\'get\',\'mainDisplay\'); return false">Fleet Dock</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'hyperspace\',\'get\',\'mainDisplay\'); return false">Hyperspace Transit</a></p>';
        echo '</div>';

        echo '<div class="card full"><h4>Infrastructure Guidance</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Priority</th><th align="left">Why</th><th align="left">Typical Phase</th></tr>';
        echo '<tr><td>Resource Buildings</td><td>Drive compounding growth and sustain all other build lines.</td><td>Early game foundation</td></tr>';
        echo '<tr><td>Facilities</td><td>Improve construction speed and unlock advanced systems.</td><td>Early-mid transition</td></tr>';
        echo '<tr><td>Lunar Structures</td><td>Enable long-range deployment and strategic mobility.</td><td>Mid game expansion</td></tr>';
        echo '<tr><td>Defense Layers</td><td>Protect economy and fleets from raid pressure.</td><td>Any phase under threat</td></tr>';
        echo '</table></div>';
    }

    if ($sub === 'logistics') {
        echo '<div class="card"><h4>Supply Logistics</h4><p>Balance transport and spending across combat, expansion, and research lanes.</p><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'empire\',\'logistics\'); return false">Open Empire Logistics Hub</a></p></div>';
    }

    if ($sub === 'treasury') {
        echo '<div class="card full"><h4>Treasury Policy</h4><p>Set reserve thresholds to avoid operational lock during spikes in war spending.</p><ul><li>War reserve: 35%</li><li>Research reserve: 20%</li><li>Expansion reserve: 15%</li><li>Flexible capital: 30%</li></ul></div>';
    }
}

if ($main === 'diplomacy') {
    if ($sub === 'alliance') {
        echo '<div class="card"><h4>Alliance Management</h4><p>Coordinate allies, officer chains, and power blocs.</p><p><a href="javascript:void(0)" onclick="sendData(\'ally_mlist\',\'get\',\'mainDisplay\'); return false">Open Alliance Roster</a></p></div>';
    }
    if ($sub === 'relations') {
        echo '<div class="card"><h4>Relations Desk</h4><p>Set war, neutral, and peace stances with other empires through player profile actions.</p><p><a href="javascript:void(0)" onclick="sendData(\'user\',\'get\',\'' . $uid . '\'); return false">Open Profile Actions</a></p></div>';
    }
    if ($sub === 'messages') {
        echo '<div class="card"><h4>Secure Messaging</h4><p>Send diplomatic messages and coordinate attacks.</p><p><a href="javascript:void(0)" onclick="sendData(\'messages\',\'get\',\'mainDisplay\'); return false">Open Inbox</a></p></div>';
    }
    if ($sub === 'commander') {
        echo '<div class="card"><h4>Commander Chain</h4><p>Assign commanders and issue support transfers from player profile pages.</p><p><a href="javascript:void(0)" onclick="sendData(\'user\',\'get\',\'' . $uid . '\'); return false">Open Commander Tools</a></p></div>';
    }
    if ($sub === 'governance') {
        echo '<div class="card"><h4>Commander Governance Systems</h4><p>Activate strategic commander governance with 18 policy systems, option profiles, and strategic settings.</p><p><a href="javascript:void(0)" onclick="sendData(\'commandergov\',\'get\',\'mainDisplay\'); return false">Open Commander Governance Console</a></p></div>';
        echo '<div class="card"><h4>Command Links</h4><p><a href="javascript:void(0)" onclick="sendData(\'user\',\'get\',\'' . $uid . '\'); return false">Commander Chain Actions</a></p><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'operations\',\'logs\'); return false">Operation Logs</a></p><p><a href="javascript:void(0)" onclick="sendData(\'stargatetech\',\'get\',\'mainDisplay\'); return false">Stargate Technology</a></p></div>';
        echo '<div class="card full"><h4>Governance Doctrine Overview</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Track</th><th align="left">Purpose</th><th align="left">Typical Focus</th></tr>';
        echo '<tr><td>Policy Administration</td><td>Keep laws and command directives synchronized.</td><td>Balanced/Technocracy setups</td></tr>';
        echo '<tr><td>War Governance</td><td>Accelerate conflict response and chain-of-command control.</td><td>Militarist/Warlord setups</td></tr>';
        echo '<tr><td>Economic Governance</td><td>Optimize markets, quotas, and logistics governance.</td><td>Mercantile/Architect setups</td></tr>';
        echo '<tr><td>Intelligence Governance</td><td>Harden covert resilience and strategic awareness.</td><td>Shadow/High-alert setups</td></tr>';
        echo '</table></div>';
    }
    if ($sub === 'treaties') {
        echo '<div class="card full"><h4>Treaty Desk</h4><p>Track active pacts and peace windows. Use this before multi-target operations.</p><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'diplomacy\',\'relations\'); return false">Open Relations</a></p></div>';
    }
    if ($sub === 'councils') {
        echo '<div class="card"><h4>Council Chamber</h4><p>Coordinate alliance leadership votes and campaign-wide directives.</p><p><a href="javascript:void(0)" onclick="sendData(\'ally_mlist\',\'get\',\'mainDisplay\'); return false">Open Alliance Roster</a></p></div>';
    }
}

if ($main === 'intel') {
    if ($sub === 'rankings') {
        echo '<div class="card"><h4>Rankings Console</h4><p>Monitor global power standings and rival growth.</p><p><a href="javascript:void(0)" onclick="sendData(\'rank\',\'get\',\'mainDisplay\'); return false">Open Rankings</a></p></div>';
    }
    if ($sub === 'reports') {
        echo '<div class="card"><h4>Battle Reports</h4><p>Digest mission outcomes and casualty analytics.</p><p><a href="javascript:void(0)" onclick="sendData(\'actionLogs\',\'get\',\'mainDisplay\'); return false">Open Action Reports</a></p></div>';
    }
    if ($sub === 'threats') {
        echo '<div class="card"><h4>Threat Matrix</h4><p>High threat indicators:</p><ul><li>Rapid rank ascension nearby</li><li>Hostile commander chains</li><li>Repeated raid contact</li></ul></div>';
    }
    if ($sub === 'map') {
        echo '<div class="card"><h4>Sector Map</h4><p>Use race, rank, and alliance data from profile scans to map influence zones.</p></div>';
    }
    if ($sub === 'signals') {
        echo '<div class="card"><h4>Signal Watch</h4><p>Monitor sudden rank jumps, repeated scout activity, and hostile message bursts.</p></div>';
    }
    if ($sub === 'dossiers') {
        echo '<div class="card full"><h4>Target Dossiers</h4><p>Build dossiers for high-value rivals before campaign waves.</p><p><a href="javascript:void(0)" onclick="sendData(\'rank\',\'get\',\'mainDisplay\'); return false">Open Rankings</a></p></div>';
    }
}

if ($main === 'universe') {
    if ($sub === 'galaxies') {
        echo '<div class="card"><h4>Universe Control Seed</h4>';
        echo '<p><strong>Seed:</strong> U-' . h($universe['seed']) . '</p>';
        echo '<p><strong>Galaxy Clusters:</strong> ' . fnum($uCfg['galaxies']) . '</p>';
        echo '<p><strong>Systems per Galaxy:</strong> ' . fnum((int)($uCfg['systemsPerGalaxy'] ?? $uCfg['sectorsPerGalaxy'])) . ' | <strong>Positions per System:</strong> ' . fnum((int)($uCfg['positionsPerSystem'] ?? $uCfg['orbitsPerSector'])) . '</p>';
        echo '<p><strong>Total Worlds:</strong> ' . fnum($uCfg['maxWorlds']) . '</p>';
        echo '<p><strong>Colonizable Worlds:</strong> ~' . fnum((int)floor($uCfg['maxWorlds'] * 0.52)) . '</p>';
        echo '</div>';

        echo '<div class="card"><h4>Expansion Command</h4>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'planets\'); return false">Open Planet Registry</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'objects\'); return false">Scan Interstellar Objects</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'expedition\'); return false">Open Expedition Control</a></p>';
        echo '</div>';

        echo '<div class="card full"><h4>Galaxy Cluster Matrix</h4>';
        echo '<table class="mini-table" border="0" width="100%"><tr><th align="left">Galaxy</th><th align="left">Systems</th><th align="left">Positions/System</th><th align="left">Worlds</th><th align="left">Avg Habitability</th><th align="left">Moon Count</th></tr>';
        $galSampleMax = min(12, (int)$uCfg['galaxies']);
        for ($g = 1; $g <= $galSampleMax; $g++) {
            $systemsPerGalaxy = (int)($uCfg['systemsPerGalaxy'] ?? $uCfg['sectorsPerGalaxy']);
            $positionsPerSystem = (int)($uCfg['positionsPerSystem'] ?? $uCfg['orbitsPerSector']);
            $worldsPerGalaxy = (int)($systemsPerGalaxy * $positionsPerSystem);
            $gSeed = (($uid + $g) * 31337) & 0x7fffffff;
            $avgHab = universeRand($gSeed, 44, 69);
            $moonCount = universeRand($gSeed, (int)floor($worldsPerGalaxy * 0.6), (int)floor($worldsPerGalaxy * 1.4));
            echo '<tr><td>G' . fnum($g) . '</td><td>' . fnum($systemsPerGalaxy) . '</td><td>' . fnum($positionsPerSystem) . '</td><td>' . fnum($worldsPerGalaxy) . '</td><td>' . fnum($avgHab) . '%</td><td>' . fnum($moonCount) . '</td></tr>';
        }
        echo '</table></div>';

        echo '<div class="card full"><h4>Strategic Coordinate Systems</h4>';
        echo '<table class="mini-table" border="0" width="100%"><tr><th align="left">Layer</th><th align="left">Range</th><th align="left">Role</th></tr>';
        echo '<tr><td>Galaxy</td><td>1-' . fnum((int)$uCfg['galaxies']) . '</td><td>Macro strategic region</td></tr>';
        echo '<tr><td>System</td><td>1-' . fnum((int)($uCfg['systemsPerGalaxy'] ?? $uCfg['sectorsPerGalaxy'])) . '</td><td>Primary travel and targeting lane</td></tr>';
        echo '<tr><td>Position</td><td>1-' . fnum((int)($uCfg['positionsPerSystem'] ?? $uCfg['orbitsPerSector'])) . '</td><td>Planet slot and moon anchor</td></tr>';
        echo '</table>';
        echo '</div>';

        echo '<div class="card full"><h4>Planet and Moon Registry</h4>';
        echo '<p>Showing worlds ' . fnum($worldSlice['start']) . '-' . fnum($worldSlice['end']) . ' of ' . fnum($worldSlice['total']) . ' | Page ' . fnum($worldSlice['page']) . ' / ' . fnum($worldSlice['maxPage']) . ' &mdash; <em>Click any planet or moon to view details</em></p>';

        $prev = max(1, $worldSlice['page'] - 1);
        $next = min($worldSlice['maxPage'], $worldSlice['page'] + 1);
        $qsBase = "modules/pages.php?id=universe&atype=galaxies&pp=" . (int)$worldSlice['perPage'] . "&time=";
        echo '<p>';
        echo '<a href="javascript:void(0)" onclick="httpRequest(\'GET\',\'' . $qsBase . '\'+(new Date().getTime())+\'&p=1\',true); return false" style="margin-right:8px;">First</a>';
        echo '<a href="javascript:void(0)" onclick="httpRequest(\'GET\',\'' . $qsBase . '\'+(new Date().getTime())+\'&p=' . $prev . '\',true); return false" style="margin-right:8px;">Prev</a>';
        echo '<a href="javascript:void(0)" onclick="httpRequest(\'GET\',\'' . $qsBase . '\'+(new Date().getTime())+\'&p=' . $next . '\',true); return false" style="margin-right:8px;">Next</a>';
        echo '<a href="javascript:void(0)" onclick="httpRequest(\'GET\',\'' . $qsBase . '\'+(new Date().getTime())+\'&p=' . $worldSlice['maxPage'] . '\',true); return false">Last</a>';
        echo '</p>';

        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">#</th><th align="left">Coordinate</th><th align="left">World</th><th align="left">Type</th><th align="left">Biome</th><th align="left">Habitability</th><th align="left">Moons</th><th align="left">Moon Class</th><th align="left">Status</th></tr>';
            foreach ($worldSlice['rows'] as $w) {
                $pd = htmlspecialchars(json_encode([
                    'idx'    => $w['idx'],
                    'coord'  => $w['coord'],  'name'  => $w['name'],  'type'  => $w['type'],
                    'biome'  => $w['biome'],  'hab'   => $w['habitability'], 'slots' => $w['slots'],
                    'metal'  => $w['metal'],  'crystal' => $w['crystal'], 'deut' => $w['deut'],
                    'moons'  => $w['moons'],  'moonClass' => $w['moonClass'], 'owner' => $w['owner'],
                ]), ENT_QUOTES);
                $moonOnclick = '';
                if ($w['moons'] > 0) {
                    $md = htmlspecialchars(json_encode([
                        'parent' => $w['name'], 'coord' => $w['coord'],
                        'count'  => $w['moons'], 'class' => $w['moonClass'],
                    ]), ENT_QUOTES);
                    $moonOnclick = ' onclick="showMoonDetail(' . $md . ')" style="cursor:pointer;text-decoration:underline;color:#8cf"';
                }
                echo '<tr>';
                echo '<td>#' . fnum($w['idx']) . '</td>';
                echo '<td>' . h($w['coord']) . '</td>';
                echo '<td><a href="javascript:void(0)" onclick="showPlanetDetail(' . $pd . ')" style="color:#adf">' . h($w['name']) . '</a></td>';
                echo '<td>' . h($w['type']) . '</td>';
                echo '<td>' . h($w['biome']) . '</td>';
                echo '<td>' . fnum($w['habitability']) . '%</td>';
                echo '<td' . $moonOnclick . '>' . fnum($w['moons']) . '</td>';
                echo '<td' . $moonOnclick . '>' . h($w['moonClass']) . '</td>';
                echo '<td>' . h($w['owner']) . '</td>';
                echo '</tr>';
            }
        echo '</table>';
        echo '</div>';
    }

    if ($sub === 'planets') {
        echo '<div class="card"><h4>Colony Totals</h4>';
        echo '<p><strong>Owned Colonies:</strong> ' . fnum(count($planets)) . ' / ' . fnum($uCfg['maxColonies']) . '</p>';
        echo '<p><strong>Available Colony Slots:</strong> ' . fnum(max(0, $uCfg['maxColonies'] - count($planets))) . '</p>';
        echo '<p><strong>Universe Worlds:</strong> ' . fnum($uCfg['maxWorlds']) . ' | <strong>Moon Capacity:</strong> ' . fnum($uCfg['maxMoons']) . '</p>';
        echo '</div>';

        echo '<div class="card"><h4>Planetary Actions</h4>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'empire\',\'planets\'); return false">Open Empire Planet Module</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'fleetdock\',\'get\',\'mainDisplay\'); return false">Open Fleet Dock</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'technology\',\'get\',\'mainDisplay\'); return false">Open Technology Upgrades</a></p>';
        echo '</div>';

        echo '<div class="card"><h4>Colony Sustainment</h4>';
        echo '<p><strong>Food Reserves:</strong> ' . fnum($resourceHub['current']['food']) . '</p>';
        echo '<p><strong>Water Reserves:</strong> ' . fnum($resourceHub['current']['water']) . '</p>';
        echo '<p><strong>Energy Grid:</strong> ' . fnum($resourceHub['current']['energy']) . '</p>';
        echo '<p><strong>Population:</strong> ' . fnum($resourceHub['current']['population']) . '</p>';
        echo '</div>';

        echo '<div class="card full"><h4>Planet, Moon, and Biome Registry</h4>';
        echo '<p>Showing worlds ' . fnum($worldSlice['start']) . '-' . fnum($worldSlice['end']) . ' of ' . fnum($worldSlice['total']) . ' | Page ' . fnum($worldSlice['page']) . ' / ' . fnum($worldSlice['maxPage']) . '</p>';
        $prev = max(1, $worldSlice['page'] - 1);
        $next = min($worldSlice['maxPage'], $worldSlice['page'] + 1);
        $qsBase = "modules/pages.php?id=universe&atype=planets&pp=" . (int)$worldSlice['perPage'] . "&time=";
        echo '<p>';
        echo '<a href="javascript:void(0)" onclick="httpRequest(\'GET\',\'' . $qsBase . '\'+(new Date().getTime())+\'&p=1\',true); return false" style="margin-right:8px;">First</a>';
        echo '<a href="javascript:void(0)" onclick="httpRequest(\'GET\',\'' . $qsBase . '\'+(new Date().getTime())+\'&p=' . $prev . '\',true); return false" style="margin-right:8px;">Prev</a>';
        echo '<a href="javascript:void(0)" onclick="httpRequest(\'GET\',\'' . $qsBase . '\'+(new Date().getTime())+\'&p=' . $next . '\',true); return false" style="margin-right:8px;">Next</a>';
        echo '<a href="javascript:void(0)" onclick="httpRequest(\'GET\',\'' . $qsBase . '\'+(new Date().getTime())+\'&p=' . $worldSlice['maxPage'] . '\',true); return false">Last</a>';
        echo '</p>';
        echo '<p><em>Click any planet or moon count to view details</em></p>';

        $actBase = "modules/pages.php?id=universe&atype=planets&p=" . (int)$worldSlice['page'] . "&pp=" . (int)$worldSlice['perPage'] . "&cmd=colonize&target=";
        $autoTarget = 0;
        foreach ($worldSlice['rows'] as $cand) {
            if ((string)$cand['owner'] === 'Unclaimed' && (int)$cand['habitability'] >= 46) {
                $autoTarget = (int)$cand['idx'];
                break;
            }
        }

        echo '<div class="card"><h4>Colonization Console</h4>';
        echo '<p>Requirements: 46%+ habitability, action turns, Naquadah, deuterium, food, water, and population.</p>';
        if ($autoTarget > 0) {
            echo '<p><a href="javascript:void(0)" onclick="httpRequest(\'GET\',\'' . $actBase . $autoTarget . '&time=\'+(new Date().getTime()),true); return false">Auto-Colonize Best Candidate On This Page</a></p>';
        } else {
            echo '<p>No eligible unclaimed world on this page. Try next page or lower requirements in strategy.</p>';
        }
        echo '</div>';

        echo '<table class="mini-table" border="0" width="100%"><tr><th align="left">#</th><th align="left">Coordinate</th><th align="left">World</th><th align="left">Type</th><th align="left">Biome</th><th align="left">Habitability</th><th align="left">Moons</th><th align="left">Resource Signature</th><th align="left">Status</th><th align="left">Action</th></tr>';
        foreach ($worldSlice['rows'] as $w) {
            $resSig = 'M' . fnum($w['metal']) . ' / C' . fnum($w['crystal']) . ' / D' . fnum($w['deut']);
            $moonSig = ($w['moons'] > 0) ? (fnum($w['moons']) . ' (' . h($w['moonClass']) . ')') : '0';
            $costs = universeColonizeCosts($w);
            $pd = htmlspecialchars(json_encode([
            'idx'    => $w['idx'],
                'coord'  => $w['coord'],  'name'  => $w['name'],  'type'  => $w['type'],
                'biome'  => $w['biome'],  'hab'   => $w['habitability'], 'slots' => $w['slots'],
                'metal'  => $w['metal'],  'crystal' => $w['crystal'], 'deut' => $w['deut'],
                'moons'  => $w['moons'],  'moonClass' => $w['moonClass'], 'owner' => $w['owner'],
            ]), ENT_QUOTES);
            $moonOnclick = '';
            if ($w['moons'] > 0) {
                $md = htmlspecialchars(json_encode([
                    'parent' => $w['name'], 'coord' => $w['coord'],
                    'count'  => $w['moons'], 'class' => $w['moonClass'],
                ]), ENT_QUOTES);
                $moonOnclick = ' onclick="showMoonDetail(' . $md . ')" style="cursor:pointer;text-decoration:underline;color:#8cf"';
            }
            echo '<tr>';
            echo '<td>#' . fnum($w['idx']) . '</td>';
            echo '<td>' . h($w['coord']) . '</td>';
            echo '<td><a href="javascript:void(0)" onclick="showPlanetDetail(' . $pd . ')" style="color:#adf">' . h($w['name']) . '</a></td>';
            echo '<td>' . h($w['type']) . '</td>';
            echo '<td>' . h($w['biome']) . '</td>';
            echo '<td>' . fnum($w['habitability']) . '%</td>';
            echo '<td' . $moonOnclick . '>' . $moonSig . '</td>';
            echo '<td>' . $resSig . '</td>';
            echo '<td>' . h($w['owner']) . '</td>';
            if ((string)$w['owner'] === 'Unclaimed' && (int)$w['habitability'] >= 46) {
                echo '<td><a href="javascript:void(0)" onclick="httpRequest(\'GET\',\'' . $actBase . (int)$w['idx'] . '&time=\'+(new Date().getTime()),true); return false">Colonize</a><br><small>' . fnum($costs['naq']) . ' Naq / ' . fnum($costs['turns']) . 'T</small></td>';
            } elseif ((string)$w['owner'] === 'Unclaimed') {
                echo '<td><small>Needs 46%+ hab</small></td>';
            } else {
                echo '<td><small>Owned</small></td>';
            }
            echo '</tr>';
        }
        echo '</table></div>';
    }

    if ($sub === 'objects') {
        echo '<div class="card"><h4>Interstellar Recovery</h4>';
        echo '<p>Use debris and asteroid routes to power recycler loops and rebuild tempo.</p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'market\',\'get\',\'mainDisplay\'); return false">Open Market Logistics</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'bank\',\'get\',\'mainDisplay\'); return false">Open Treasury Routing</a></p>';
        echo '</div>';

        echo '<div class="card"><h4>Scout Loop</h4>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'spy\',\'get\',\'mainDisplay\'); return false">Open Spy Module</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'rank\',\'get\',\'mainDisplay\'); return false">Open Regional Rankings</a></p>';
        echo '</div>';

        echo '<div class="card full"><h4>Interstellar Object Density Matrix</h4>';
        echo '<table class="mini-table" border="0" width="100%"><tr><th align="left">Galaxy</th><th align="left">Asteroid Belts</th><th align="left">Debris Fields</th><th align="left">Nebulae</th><th align="left">Comet Streams</th><th align="left">Wormholes</th><th align="left">Ancient Ruins</th></tr>';
        foreach ($universe['objects'] as $obj) {
            echo '<tr><td>' . h($obj['galaxy']) . '</td><td>' . fnum($obj['asteroidBelts']) . '</td><td>' . fnum($obj['debrisFields']) . '</td><td>' . fnum($obj['nebulae']) . '</td><td>' . fnum($obj['cometStreams']) . '</td><td>' . fnum($obj['wormholes']) . '</td><td>' . fnum($obj['ancientRuins']) . '</td></tr>';
        }
        echo '</table></div>';
    }

    if ($sub === 'expedition') {
        echo '<div class="card"><h4>Mission Dispatch</h4>';
        echo '<p>Target UID dispatch:</p>';
        echo '<p><input id="uniTargetUid" type="number" min="1" value="1" style="width:110px"> ';
        echo '<a href="javascript:void(0)" onclick="var t=parseInt(document.getElementById(\'uniTargetUid\').value,10)||0;if(t>0){sendData(\'action\',\'get\',t,\'spy\');} return false">Spy</a> | ';
        echo '<a href="javascript:void(0)" onclick="var t=parseInt(document.getElementById(\'uniTargetUid\').value,10)||0;if(t>0){sendData(\'action\',\'get\',t,\'raid\');} return false">Raid</a> | ';
        echo '<a href="javascript:void(0)" onclick="var t=parseInt(document.getElementById(\'uniTargetUid\').value,10)||0;if(t>0){sendData(\'action\',\'get\',t,\'attack\');} return false">Attack</a></p>';
        echo '</div>';

        echo '<div class="card"><h4>Expansion Workflows</h4>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'fleetdock\',\'get\',\'mainDisplay\'); return false">Fleet Dock</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'stations\',\'get\',\'mainDisplay\'); return false">Orbital Stations Command</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'hyperspace\',\'get\',\'mainDisplay\'); return false">Jumpgate and Hyperspace Command</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'fleetdock\',\'get\',\'upgrade_shipyard\'); return false">Upgrade Shipyard</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'fleetdock\',\'get\',\'upgrade_bay\'); return false">Upgrade Mothership Bay</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'planets\'); return false">Candidate Worlds</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'technology\',\'get\',\'mainDisplay\'); return false">Astro/Tech Upgrades</a></p>';
        echo '</div>';

        echo '<div class="card full"><h4>Expedition and Colonization Doctrine</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Mission</th><th align="left">Purpose</th><th align="left">Typical Cost</th><th align="left">Risk Tier</th></tr>';
        echo '<tr><td>Deep Expedition</td><td>Find debris, anomalies, and bonus resources</td><td>Fleet + covert turns</td><td>Medium</td></tr>';
        echo '<tr><td>Colonization Wave</td><td>Claim high-habitability worlds with moon potential</td><td>Fleet + economy reserve</td><td>Medium-High</td></tr>';
        echo '<tr><td>Debris Recovery</td><td>Recycle post-combat fields into growth capital</td><td>Recycler allocation + travel time</td><td>Low</td></tr>';
        echo '<tr><td>Rapid Strike Route</td><td>Use wormhole lanes for pressure projection</td><td>Attack turns + logistics</td><td>High</td></tr>';
        echo '</table></div>';
    }

    if ($sub === 'bases') {
        echo '<div class="card"><h4>Stations and Bases Command</h4>';
        echo '<p>Build Space Stations, Starbases, and Moon Bases to anchor fleet operations and improve expedition consistency.</p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'stations\',\'get\',\'mainDisplay\'); return false">Open Orbital Base Command</a></p>';
        echo '</div>';

        echo '<div class="card"><h4>Integration Paths</h4>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'fleet\'); return false">Military Fleet Directorate</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'fleetdock\',\'get\',\'mainDisplay\'); return false">Fleet Dock and Missions</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'expedition\'); return false">Expedition Planner</a></p>';
        echo '</div>';

        echo '<div class="card full"><h4>Orbital Infrastructure Doctrine</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Installation</th><th align="left">Primary Role</th><th align="left">Synergy</th><th align="left">Suggested Timing</th></tr>';
        echo '<tr><td>Space Station</td><td>Orbital logistics, ship throughput, and fleet support</td><td>Shipyard and Mega Forge output cycles</td><td>Early-mid expansion</td></tr>';
        echo '<tr><td>Starbase</td><td>Defensive projection and warfront staging</td><td>Fleet Dock mission readiness and deterrence</td><td>Mid-game before sustained wars</td></tr>';
        echo '<tr><td>Moon Base</td><td>Surveillance, scan depth, and expedition resilience</td><td>Universe expedition routes and object recovery</td><td>After first stable offensive wing</td></tr>';
        echo '</table></div>';
    }

    if ($sub === 'travel') {
        echo '<div class="card"><h4>Jumpgate and Stargate Transit</h4>';
        echo '<p>Build gate infrastructure and launch hyperspace wings for transfer, expedition, and colonization operations.</p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'hyperspace\',\'get\',\'mainDisplay\'); return false">Open Hyperspace Transit Command</a></p>';
        echo '</div>';

        echo '<div class="card"><h4>Travel Integrations</h4>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'fleet\'); return false">Military Fleet Directorate</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'fleetdock\',\'get\',\'mainDisplay\'); return false">Fleet Dock and Mission Queue</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'expedition\'); return false">Expedition Operations</a></p>';
        echo '</div>';

        echo '<div class="card full"><h4>Hyperspace Operations Doctrine</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">System</th><th align="left">Role</th><th align="left">Primary Resource Pressure</th><th align="left">Best Usage Window</th></tr>';
        echo '<tr><td>Jump Gate</td><td>Local lane initialization and deployment tempo</td><td>Metal + deuterium for lane maintenance</td><td>Early expansion and first war mobilization</td></tr>';
        echo '<tr><td>Stargate</td><td>Deep interstellar routing and fleet projection</td><td>Crystal + deuterium for stable long routes</td><td>Mid-game multi-front campaigns</td></tr>';
        echo '<tr><td>Hyperspace Core</td><td>Cooldown compression and transit efficiency</td><td>Deuterium + sustainment (food/water)</td><td>Late-stage expedition and colonization loops</td></tr>';
        echo '</table></div>';
    }

    if ($sub === 'lanes') {
        echo '<div class="card full"><h4>Transit Lanes</h4><p>Use lane planning to distribute fleets and reduce route bottlenecks.</p><table class="mini-table" border="0" width="100%"><tr><th align="left">Lane Type</th><th align="left">Best Use</th></tr><tr><td>Inner Lanes</td><td>Fast military response</td></tr><tr><td>Outer Lanes</td><td>Colonization and deep expeditions</td></tr></table></div>';
    }

    if ($sub === 'anomalies') {
        echo '<div class="card"><h4>Anomaly Index</h4><p>Catalog wormholes, ruins, and volatile fields for expedition targeting.</p><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'objects\'); return false">Open Object Scanner</a></p></div>';
    }
}

if ($main === 'research') {
    $infraLevels = [
        'research_campus' => 0,
        'data_vault' => 0,
        'simulation_core' => 0,
        'quantum_archive' => 0,
        'ai_directorate' => 0,
    ];
    $infraHas = $s->query("SHOW TABLES LIKE 'research_infrastructure'");
    if ($infraHas && $infraHas->num_rows > 0) {
        $infraQ = $s->query("SELECT research_campus, data_vault, simulation_core, quantum_archive, ai_directorate FROM research_infrastructure WHERE uid=" . (int)$uid . " LIMIT 1");
        if ($infraQ && $infraQ->num_rows > 0) {
            $i = $infraQ->fetch_object();
            $infraLevels['research_campus'] = (int)($i->research_campus ?? 0);
            $infraLevels['data_vault'] = (int)($i->data_vault ?? 0);
            $infraLevels['simulation_core'] = (int)($i->simulation_core ?? 0);
            $infraLevels['quantum_archive'] = (int)($i->quantum_archive ?? 0);
            $infraLevels['ai_directorate'] = (int)($i->ai_directorate ?? 0);
        }
    }
    $infraCostDiscount = min(45.0, ($infraLevels['data_vault'] * 1.5) + ($infraLevels['quantum_archive'] * 1.0) + ($infraLevels['ai_directorate'] * 0.5));
    $infraResearchSpeed = 1 + (($infraLevels['research_campus'] * 0.03) + ($infraLevels['simulation_core'] * 0.015) + ($infraLevels['ai_directorate'] * 0.02));

    if ($sub === 'tree') {
        echo '<div class="card full wows-brief">';
        echo '<h4>Research Fleet Tree</h4>';
        echo '<p>Progress each domain left-to-right through six tiers. Nodes marked <strong>available</strong> are current unlock candidates based on your level state.</p>';
        echo '<div class="wows-pill-row">';
        echo '<span class="wows-pill">Command Lv ' . fnum($researchHub['level']['commandLevel']) . '</span>';
        echo '<span class="wows-pill">Research Lv ' . fnum($researchHub['level']['researchLevel']) . '</span>';
        echo '<span class="wows-pill">Ascension ' . fnum($researchHub['level']['ascension']) . '</span>';
        echo '<span class="wows-pill">XP To Next ' . fnum($researchHub['level']['xpToNext']) . '</span>';
        echo '</div>';
        echo '</div>';

        echo '<div class="card full">';
        echo '<h4>Research Tree Matrix</h4>';
        renderTreeBoard($researchHub['researchTree'], (int)$researchHub['level']['researchLevel'], 'researchTreeBoard', 'R-Tier');
        echo '</div>';

        echo '<div class="card"><h4>Primary Stats</h4><ul>';
        foreach ($researchHub['stats'] as $statName => $statVal) {
            echo '<li>' . h($statName) . ': ' . fnum($statVal) . '</li>';
        }
        echo '</ul></div>';

        echo '<div class="card"><h4>Sub Stats</h4><ul>';
        foreach ($researchHub['subStats'] as $statName => $statVal) {
            echo '<li>' . h($statName) . ': ' . fnum($statVal) . '</li>';
        }
        echo '</ul></div>';

        echo '<div class="card full"><h4>Research Resource Requirements</h4>';
        echo '<p>Advanced research phases consume strategic resources from the expanded economy.</p>';
        echo '<p><strong>Metal:</strong> ' . fnum($resourceHub['current']['metal']) . ' | <strong>Crystal:</strong> ' . fnum($resourceHub['current']['crystal']) . ' | <strong>Deuterium:</strong> ' . fnum($resourceHub['current']['deuterium']) . '</p>';
        echo '<p><strong>Food:</strong> ' . fnum($resourceHub['current']['food']) . ' | <strong>Water:</strong> ' . fnum($resourceHub['current']['water']) . ' | <strong>Population:</strong> ' . fnum($resourceHub['current']['population']) . ' | <strong>Energy:</strong> ' . fnum($resourceHub['current']['energy']) . '</p>';
        echo '<p><strong>Infrastructure Research Speed:</strong> ' . fnum($infraResearchSpeed) . 'x | <strong>Infrastructure Tech Cost Reduction:</strong> ' . fnum($infraCostDiscount) . '%</p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'techlib\',\'get\',\'mainDisplay\'); return false">Open Tech Library Buildings</a></p>';
        echo '</div>';
    }

    if ($sub === 'techlib') {
        echo '<div class="card full wows-brief"><h4>Technology Fleet Tree</h4>';
        echo '<p>Technology progression follows branch lanes similar to naval class lines, where each tier unlocks deeper specialization and output power.</p>';
        echo '<div class="wows-pill-row">';
        echo '<span class="wows-pill">Technology Lv ' . fnum($researchHub['level']['technologyLevel']) . '</span>';
        echo '<span class="wows-pill">Class Entries ' . fnum($researchHub['counts']['classes']) . '</span>';
        echo '<span class="wows-pill">Talent Pool ' . fnum($researchHub['counts']['talents']) . '</span>';
        echo '</div>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'technology\',\'get\',\'mainDisplay\'); return false">Open Legacy Technology Module</a></p>';
        echo '</div>';

        echo '<div class="card full"><h4>Technology Tree Matrix</h4>';
        renderTreeBoard($researchHub['techTree'], (int)$researchHub['level']['technologyLevel'], 'technologyTreeBoard', 'T-Tier');
        echo '</div>';

        echo '<div class="card full"><h4>Type and Sub-Type Index</h4>';
        echo '<p><strong>Types:</strong> ' . h(implode(', ', $researchHub['types'])) . '</p>';
        echo '<p><strong>Sub Types:</strong> ' . h(implode(', ', $researchHub['subTypes'])) . '</p>';
        echo '<p><strong>Tech Library Cost Reduction:</strong> ' . fnum($infraCostDiscount) . '% | <strong>Research Speed:</strong> ' . fnum($infraResearchSpeed) . 'x</p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'techlib\',\'get\',\'mainDisplay\'); return false">Manage Tech Library Buildings</a></p>';
        echo '</div>';
    }

    if ($sub === 'infrastructure') {
        echo '<div class="card full"><h4>Tech Library Buildings</h4>';
        echo '<p>Infrastructure buildings increase research speed and reduce Stargate technology costs.</p>';
        echo '<p><strong>Research Campus:</strong> ' . fnum($infraLevels['research_campus']) . '</p>';
        echo '<p><strong>Data Vault:</strong> ' . fnum($infraLevels['data_vault']) . '</p>';
        echo '<p><strong>Simulation Core:</strong> ' . fnum($infraLevels['simulation_core']) . '</p>';
        echo '<p><strong>Quantum Archive:</strong> ' . fnum($infraLevels['quantum_archive']) . '</p>';
        echo '<p><strong>AI Research Directorate:</strong> ' . fnum($infraLevels['ai_directorate']) . '</p>';
        echo '<p><strong>Research Speed:</strong> ' . fnum($infraResearchSpeed) . 'x</p>';
        echo '<p><strong>Tech Cost Reduction:</strong> ' . fnum($infraCostDiscount) . '%</p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'techlib\',\'get\',\'mainDisplay\'); return false">Open Tech Library Building Console</a></p>';
        echo '</div>';
    }

    if ($sub === 'classes') {
        echo '<div class="card"><h4>Class Doctrine Summary</h4>';
        echo '<p>90 class entries are generated with matching subclasses, types, and sub-types for deep build planning.</p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'megaforge\',\'get\',\'mainDisplay\'); return false">Open Mega Forge Construction</a></p>';
        echo '</div>';

        echo '<div class="card full"><h4>Class and Sub-Class Library (90)</h4>';
        echo '<table class="mini-table" border="0" width="100%"><tr><th align="left">ID</th><th align="left">Class</th><th align="left">Sub Class</th><th align="left">Type</th><th align="left">Sub Type</th></tr>';
        foreach ($researchHub['classes'] as $entry) {
            echo '<tr><td>' . fnum($entry['id']) . '</td><td>' . h($entry['className']) . '</td><td>' . h($entry['subClass']) . '</td><td>' . h($entry['type']) . '</td><td>' . h($entry['subType']) . '</td></tr>';
        }
        echo '</table></div>';
    }

    if ($sub === 'talents') {
        $researchTalents = 0;
        $techTalents = 0;
        foreach ($researchHub['talents'] as $talent) {
            if ($talent['branch'] === 'Research') {
                $researchTalents++;
            } else {
                $techTalents++;
            }
        }

        echo '<div class="card"><h4>Talent Pool Summary</h4>';
        echo '<p><strong>Total Talents:</strong> ' . fnum($researchHub['counts']['talents']) . '</p>';
        echo '<p><strong>Research Talents:</strong> ' . fnum($researchTalents) . '</p>';
        echo '<p><strong>Technology Talents:</strong> ' . fnum($techTalents) . '</p>';
        echo '</div>';

        echo '<div class="card"><h4>Talent Tier Bands</h4>';
        echo '<p>Tier bands are grouped every 30 talents to create 8 progression bands across the full library.</p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'megaforge\',\'get\',\'mainDisplay\'); return false">Use Talents With Mega Forge Builds</a></p>';
        echo '</div>';

        echo '<div class="card full"><h4>Talent Library (240)</h4>';
        echo '<table class="mini-table" border="0" width="100%"><tr><th align="left">ID</th><th align="left">Branch</th><th align="left">Domain</th><th align="left">Talent</th><th align="left">Focus</th><th align="left">Tier</th><th align="left">Effect</th></tr>';
        foreach ($researchHub['talents'] as $talent) {
            echo '<tr><td>' . fnum($talent['id']) . '</td><td>' . h($talent['branch']) . '</td><td>' . h($talent['domain']) . '</td><td>' . h($talent['name']) . '</td><td>' . h($talent['focus']) . '</td><td>' . fnum($talent['tier']) . '</td><td>' . h($talent['effect']) . '</td></tr>';
        }
        echo '</table></div>';
    }

    if ($sub === 'stargate') {
        echo '<div class="card"><h4>Stargate Technology Program</h4>';
        echo '<p>Research complete Stargate-era technologies including gate science, power matrices, fleet integration, and threat response.</p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'stargatetech\',\'get\',\'mainDisplay\'); return false">Open Stargate Technology Command</a></p>';
        echo '</div>';

        echo '<div class="card"><h4>Cross-System Links</h4>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'technology\',\'get\',\'mainDisplay\'); return false">Legacy Technology Module</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'hyperspace\',\'get\',\'mainDisplay\'); return false">Hyperspace Transit Command</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'stations\',\'get\',\'mainDisplay\'); return false">Stations and Bases Command</a></p>';
        echo '</div>';

        echo '<div class="card full"><h4>Stargate Doctrine Priorities</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Phase</th><th align="left">Primary Focus</th><th align="left">Expected Outcome</th></tr>';
        echo '<tr><td>Early</td><td>Naquadah Physics, Gate Dialing Protocols, Capacitor Lattices</td><td>Reliable gate operation and base power continuity</td></tr>';
        echo '<tr><td>Mid</td><td>Fleet Integration and Defense Tech domains</td><td>Safer deep-route deployments and stronger anti-raid posture</td></tr>';
        echo '<tr><td>Late</td><td>Ancient Systems and high-tier threat-response lines</td><td>Maximum interstellar control and campaign endurance</td></tr>';
        echo '</table></div>';
    }

    if ($sub === 'projects') {
        echo '<div class="card full"><h4>Research Projects</h4><p>Track active projects by branch and priority band.</p><ul><li>Military Optimization Project</li><li>Economic Throughput Project</li><li>Gate Stability Project</li></ul></div>';
    }

    if ($sub === 'labs') {
        echo '<div class="card"><h4>Lab Network</h4><p>Coordinate infrastructure levels across research campuses and archives.</p><p><a href="javascript:void(0)" onclick="sendData(\'techlib\',\'get\',\'mainDisplay\'); return false">Open Tech Library Buildings</a></p></div>';
    }
}

if ($main === 'community') {
    if ($sub === 'forums') {
        echo '<div class="card"><h4>Forums</h4><p>Join strategy discussions, diplomacy talks, and event threads.</p><p><a href="forums/" target="_blank">Open Forums</a></p></div>';
    }
    if ($sub === 'updates') {
        echo '<div class="card"><h4>Update Feed</h4><p>Read update announcements and balancing notes.</p><p><a href="javascript:void(0)" onclick="sendData(\'faq\',\'get\',\'mainDisplay\'); return false">Open News/FAQ</a></p></div>';
    }
    if ($sub === 'contact') {
        echo '<div class="card"><h4>Contact Command</h4><p>Reach moderators and administrators through in-game messaging channels.</p><p><a href="javascript:void(0)" onclick="sendData(\'messages\',\'get\',\'mainDisplay\'); return false">Open Messaging</a></p></div>';
    }
    if ($sub === 'faq') {
        echo '<div class="card"><h4>FAQ</h4><p>Core rules, policy, and progression advice are available here.</p><p><a href="javascript:void(0)" onclick="sendData(\'faq\',\'get\',\'mainDisplay\'); return false">Open FAQ</a></p></div>';
    }
    if ($sub === 'events') {
        echo '<div class="card"><h4>Events Calendar</h4><p>Track alliance events, challenge windows, and campaign checkpoints.</p></div>';
    }
    if ($sub === 'academy') {
        echo '<div class="card full"><h4>Academy</h4><p>Structured training path for new and returning commanders.</p><ol><li>Basics: economy and turns</li><li>Intermediate: raids and intel</li><li>Advanced: multi-front doctrine</li></ol></div>';
    }
}

if ($main === 'help') {
    if ($sub === 'newplayer') {
        echo '<div class="card full"><h4>New Player Launch Plan</h4><ol><li>Train a balanced starter army from untrained units.</li><li>Keep a reserve of Naquadah for emergency retraining.</li><li>Upgrade production before expensive wars.</li><li>Scout targets before every major operation.</li></ol></div>';
    }
    if ($sub === 'mechanics') {
        echo '<div class="card"><h4>Core Mechanics</h4><ul><li>Action turns gate all offensive actions.</li><li>Military score influences rank and combat outcomes.</li><li>1% broker fee applies to support transfers.</li><li>Technology upgrades scale growth and resilience.</li></ul></div>';
    }
    if ($sub === 'glossary') {
        echo '<div class="card"><h4>Glossary</h4><p><strong>Naquadah:</strong> Main currency.</p><p><strong>UP:</strong> Unit production per turn.</p><p><strong>Commander:</strong> Parent node in command chain.</p><p><strong>Action Turn:</strong> Strategic action resource.</p></div>';
    }
    if ($sub === 'support') {
        echo '<div class="card"><h4>Support Desk</h4><p>For account issues, use in-game contact and community channels. Include mission timestamps and affected players in reports.</p></div>';
    }
    if ($sub === 'troubleshooting') {
        echo '<div class="card full"><h4>Troubleshooting</h4><ul><li>If module output stalls, reload and retry the menu action.</li><li>If actions fail, verify enough turns and resources.</li><li>If target actions fail, refresh rank/profile intelligence first.</li></ul></div>';
    }
    if ($sub === 'hotkeys') {
        echo '<div class="card"><h4>Quick Commands</h4><p>Use the left command tree and feature action buttons for rapid sub-page switching.</p></div>';
    }
}

renderMechanicsMatrix($main, $sub);
renderInteractiveCalculators($main, $sub, $baseData, $personnel, $bank);
renderFeatureWorkbenches($main, $sub, $baseData, $personnel, $bank, $userStats, $planets);

echo '</div>';
echo '</div>';

// Planet / moon detail modal (shared across galaxy and planets tabs)
?>
<div id="sgw-detail-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.75);z-index:9999;overflow:auto;">
  <div style="background:#1a1a2e;color:#ccc;border:1px solid #555;border-radius:6px;max-width:520px;margin:60px auto;padding:24px;position:relative;">
    <button onclick="document.getElementById('sgw-detail-modal').style.display='none'" style="position:absolute;top:10px;right:14px;background:none;border:none;color:#aaa;font-size:18px;cursor:pointer;">&#x2715;</button>
    <div id="sgw-detail-body"></div>
  </div>
</div>
<script type="text/javascript">
function showPlanetDetail(d){
    var hab = d.hab || 0;
    var habCol = hab >= 70 ? '#6f6' : (hab >= 45 ? '#ff9' : '#f77');
    var moonStr = d.moons > 0
        ? '<span style="cursor:pointer;color:#8cf;text-decoration:underline" onclick="showMoonDetail({parent:\''+esc(d.name)+'\',coord:\''+esc(d.coord)+'\',count:'+d.moons+',\'class\':\''+esc(d.moonClass)+'\'})">'+d.moons+' &times; '+esc(d.moonClass)+'</span>'
        : '<em>None</em>';
    var colonizeBtn = (d.owner === 'Unclaimed' && hab >= 48)
        ? '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'expedition\'); closeSgwModal();" style="color:#6cf">&#x1F680; Plan Colonization Mission</a></p>'
        : '';
    document.getElementById('sgw-detail-body').innerHTML =
        '<h3 style="margin-top:0;color:#adf">&#127760; '+esc(d.name)+'</h3>'+
        '<table style="width:100%;border-collapse:collapse;font-size:.9em">'+
        row('Coordinate', esc(d.coord))+
        row('World Type', esc(d.type))+
        row('Biome', esc(d.biome))+
        row('Habitability', '<span style="color:'+habCol+'">'+hab+'%</span>')+
        row('Build Slots', d.slots)+
        row('Metal Deposit', num(d.metal))+
        row('Crystal Deposit', num(d.crystal))+
        row('Deuterium Deposit', num(d.deut))+
        row('Moons', moonStr)+
        row('Status', esc(d.owner))+
        '</table>'+colonizeBtn;
    document.getElementById('sgw-detail-modal').style.display = 'block';
}
function showMoonDetail(d){
    var moonClasses = {
        Rocky:   {desc:'Dense basalt crust — ideal for sensor arrays and early bunker construction.',  bonus:'Defense +3%, Scanner range +1'},
        Icy:     {desc:'Frozen volatiles — rich in deuterium ice extraction potential.',               bonus:'Deuterium rate +8%'},
        Metallic:{desc:'High-grade ore concentration — excellent mining substrate.',                   bonus:'Metal rate +6%, Crystal rate +4%'},
        Ruined:  {desc:'Ancient wreckage of unknown origin — yields artefact anomalies on excavation.',bonus:'Expedition anomaly chance +12%'},
    };
    var cls = d['class'] || d.class || '?';
    var info = moonClasses[cls] || {desc:'Unknown lunar body.', bonus:'No data'};
    var moons = [];
    for(var i=1;i<=d.count;i++){
        moons.push('<li>Moon '+i+' &mdash; <strong>'+esc(cls)+'</strong></li>');
    }
    document.getElementById('sgw-detail-body').innerHTML =
        '<h3 style="margin-top:0;color:#8cf">&#127761; '+esc(d.parent)+' &mdash; Moon System</h3>'+
        '<p><strong>Parent Coordinate:</strong> '+esc(d.coord)+'</p>'+
        '<ul style="padding-left:18px">'+moons.join('')+'</ul>'+
        '<table style="width:100%;border-collapse:collapse;font-size:.9em">'+
        row('Moon Class', esc(cls))+
        row('Classification', info.desc)+
        row('Strategic Bonus', '<span style="color:#8f8">'+info.bonus+'</span>')+
        '</table>'+
        '<p style="margin-top:12px"><a href="javascript:void(0)" onclick="sendData(\'stations\',\'get\',\'mainDisplay\'); closeSgwModal();" style="color:#6cf">&#x1F6F8; Open Station Command</a></p>';
    document.getElementById('sgw-detail-modal').style.display = 'block';
}
function closeSgwModal(){ document.getElementById('sgw-detail-modal').style.display='none'; }
function row(label, val){ return '<tr><td style="padding:4px 8px;border-bottom:1px solid #333;color:#888;width:45%">'+label+'</td><td style="padding:4px 8px;border-bottom:1px solid #333">'+val+'</td></tr>'; }
function esc(s){ var d=document.createElement('div');d.appendChild(document.createTextNode(String(s)));return d.innerHTML; }
function num(n){ return Number(n).toLocaleString(); }
document.getElementById('sgw-detail-modal').addEventListener('click',function(e){ if(e.target===this){ closeSgwModal(); } });
</script>
<?php

echo "Query Count: " . $s->queryCount . "<br>";
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());
?>