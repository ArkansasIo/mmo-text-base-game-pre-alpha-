<?php
include_once("../config.php");

$pagegen = new page_gen();
$pagegen->round_to = 4;
$pagegen->start();

$s = new Game();
if (!$s->loggedIn || !$_GET['time']) {
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

function buildUniverseSnapshot(int $uid, array $ownedPlanets): array {
    $seed = (($uid + 11) * 7919) & 0x7fffffff;

    $worldTypes = ['Terran', 'Oceanic', 'Arid', 'Volcanic', 'Ice', 'Gas Dwarf', 'Toxic', 'Crystalline', 'Relic'];
    $biomes = [
        'Terran' => ['Temperate Forest', 'Grassland', 'Rain Basin'],
        'Oceanic' => ['Archipelago', 'Kelp Expanse', 'Storm Sea'],
        'Arid' => ['Dune Basin', 'Canyon Belt', 'Salt Flats'],
        'Volcanic' => ['Magma Rift', 'Ash Plateau', 'Basalt Sea'],
        'Ice' => ['Glacier Shield', 'Frozen Canyons', 'Polar Sink'],
        'Gas Dwarf' => ['Upper Cloudline', 'Ionic Layer', 'Hydrogen Drift'],
        'Toxic' => ['Acid Mire', 'Sulfur Crust', 'Caustic Foglands'],
        'Crystalline' => ['Shard Plains', 'Prism Caves', 'Quartz Highlands'],
        'Relic' => ['Ancient Arcology', 'Derelict Ring', 'Vault Ruins'],
    ];

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
    echo '<div class="card full"><h4>Operational Brief</h4><p>' . h($detail['brief']) . '</p></div>';

    echo '<div class="card"><h4>Functions</h4><ul>';
    foreach (($detail['functions'] ?? []) as $item) {
        echo '<li>' . h($item) . '</li>';
    }
    echo '</ul></div>';

    echo '<div class="card"><h4>Features</h4><ul>';
    foreach (($detail['features'] ?? []) as $item) {
        echo '<li>' . h($item) . '</li>';
    }
    echo '</ul></div>';

    echo '<div class="card full"><h4>Logic & Rules</h4><ol>';
    foreach (($detail['logic'] ?? []) as $item) {
        echo '<li>' . h($item) . '</li>';
    }
    echo '</ol></div>';
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
        'help:mechanics' => [
            'Covert sabotage doctrine often accepts lower losses on success and higher losses on failure.',
            'Mothership progression includes high-cost entry, bay expansion, and weapon specialization.',
            'Effective macro play balances production growth, intel quality, and turn efficiency.',
        ],
        'universe:galaxies' => [
            'Universe is divided into galaxy clusters, sectors, and orbital slots for expansion routing.',
            'Each world has a biome profile, habitability score, and distinct resource distribution.',
            'OGame-style growth favors staggered colonies across multiple galaxy lanes to reduce bottlenecks.',
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

    if (($main === 'economy' && ($sub === 'banking' || $sub === 'market' || $sub === 'production')) || ($main === 'help' && $sub === 'mechanics') || ($main === 'empire' && $sub === 'overview')) {
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

    if ($main === 'empire' && $sub === 'overview') {
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

$subDefaults = [
    'empire' => 'overview',
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
    'empire' => ['overview' => 'Overview', 'planets' => 'Planets', 'command' => 'Command', 'progress' => 'Progression'],
    'military' => ['personnel' => 'Personnel', 'armory' => 'Armory', 'training' => 'Training', 'fleet' => 'Fleet'],
    'operations' => ['attack' => 'Attack', 'raid' => 'Raid', 'spy' => 'Spy', 'logs' => 'Combat Logs'],
    'economy' => ['banking' => 'Banking', 'market' => 'Market', 'technology' => 'Technology', 'production' => 'Production', 'resources' => 'Resource Hub', 'buildings' => 'OGame Buildings'],
    'diplomacy' => ['alliance' => 'Alliance', 'relations' => 'Relations', 'messages' => 'Messages', 'commander' => 'Commander Chain'],
    'intel' => ['rankings' => 'Rankings', 'reports' => 'Battle Reports', 'threats' => 'Threat Matrix', 'map' => 'Sector Map'],
    'community' => ['forums' => 'Forums', 'updates' => 'Updates', 'contact' => 'Contact', 'faq' => 'FAQ'],
    'help' => ['newplayer' => 'New Player', 'mechanics' => 'Mechanics', 'glossary' => 'Glossary', 'support' => 'Support'],
    'universe' => ['galaxies' => 'Galaxies', 'planets' => 'Planets & Moons', 'objects' => 'Interstellar Objects', 'expedition' => 'Expedition', 'bases' => 'Stations & Bases', 'travel' => 'Jumpgate & Hyperspace'],
    'research' => ['tree' => 'Research Tree', 'techlib' => 'Technology Tree', 'classes' => 'Class Library', 'talents' => 'Talent Library', 'stargate' => 'Stargate Tech'],
];

$systemDetails = [
    'empire' => [
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
            'brief' => 'OGame-style resource economy command for mining, sustainment, and population growth.',
            'functions' => ['Track 5 strategic resources', 'Upgrade production structures', 'Trade resources for tactical needs'],
            'features' => ['Resource stockpile view', 'Production rates by line', 'Structure level overview and controls'],
            'logic' => ['Resources tick on 30-minute cadence', 'Structure levels amplify resource rates', 'Food and water shortages reduce population'],
        ],
        'buildings' => [
            'brief' => 'Central OGame-style construction control for economy, facilities, lunar structures, and defense lines.',
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
            'brief' => 'OGame-style expedition and colonization planner with mission control actions.',
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
$s->updatePower($uid);

$baseData = $s->baseVars();
$personnel = $s->getPersonnel($uid);
$bank = $s->bank();
$userStats = $s->getUserInfo($uid);
$planets = $s->getUserPlanets($uid);
$universe = buildUniverseSnapshot($uid, $planets);
$techView = $s->viewTech();
$researchHub = buildResearchDirectorate($uid, $techView, $personnel);
$resourceHub = resourceEnsureAndTick($s, $uid, $baseData, $planets, $techView);

$title = $mainTitles[$main];
$subTitle = $subLabels[$main][$sub];

echo '<div class="page-hub">';
echo '<div class="page-hub-head">';
echo '<h3>' . h($title) . ' - ' . h($subTitle) . '</h3>';
echo '<p>Page: ' . h($main) . ' / ' . h($sub) . ' | Player: ' . h($_SESSION['username']) . '</p>';
echo '</div>';

echo '<div class="page-subnav-title">Sub Pages</div>';
echo '<div class="page-subnav">';
foreach ($subLabels[$main] as $subKey => $subName) {
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
    ],
    'military' => [
        ['label' => 'Armory', 'js' => "sendData('armory','get','mainDisplay'); return false"],
        ['label' => 'Training', 'js' => "sendData('train','get','mainDisplay'); return false"],
        ['label' => 'Fleet Dock', 'js' => "sendData('fleetdock','get','mainDisplay'); return false"],
        ['label' => 'Mega Forge', 'js' => "sendData('megaforge','get','mainDisplay'); return false"],
        ['label' => 'Stations', 'js' => "sendData('stations','get','mainDisplay'); return false"],
        ['label' => 'Hyperspace', 'js' => "sendData('hyperspace','get','mainDisplay'); return false"],
    ],
    'operations' => [
        ['label' => 'Targets', 'js' => "sendData('rank','get','mainDisplay'); return false"],
        ['label' => 'Spy', 'js' => "sendData('spy','get','mainDisplay'); return false"],
        ['label' => 'Combat Logs', 'js' => "sendData('logs','get','mainDisplay'); return false"],
        ['label' => 'Action Reports', 'js' => "sendData('actionLogs','get','mainDisplay'); return false"],
    ],
    'economy' => [
        ['label' => 'Bank', 'js' => "sendData('bank','get','mainDisplay'); return false"],
        ['label' => 'Market', 'js' => "sendData('market','get','mainDisplay'); return false"],
        ['label' => 'Resource HQ', 'js' => "sendData('resourcehq','get','mainDisplay'); return false"],
        ['label' => 'OGame Buildings', 'js' => "sendData('ogamebuildings','get','mainDisplay'); return false"],
        ['label' => 'Technology', 'js' => "sendData('technology','get','mainDisplay'); return false"],
        ['label' => 'Stargate Tech', 'js' => "sendData('stargatetech','get','mainDisplay'); return false"],
    ],
    'diplomacy' => [
        ['label' => 'Messages', 'js' => "sendData('messages','get','mainDisplay'); return false"],
        ['label' => 'Alliance', 'js' => "sendData('ally_mlist','get','mainDisplay'); return false"],
        ['label' => 'Relations', 'js' => "sendData('pages','get','diplomacy','relations'); return false"],
    ],
    'intel' => [
        ['label' => 'Rankings', 'js' => "sendData('rank','get','mainDisplay'); return false"],
        ['label' => 'Reports', 'js' => "sendData('actionLogs','get','mainDisplay'); return false"],
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
    ],
    'universe' => [
        ['label' => 'Galaxy Map', 'js' => "sendData('pages','get','universe','galaxies'); return false"],
        ['label' => 'Stations', 'js' => "sendData('stations','get','mainDisplay'); return false"],
        ['label' => 'Hyperspace', 'js' => "sendData('hyperspace','get','mainDisplay'); return false"],
        ['label' => 'Expedition', 'js' => "sendData('pages','get','universe','expedition'); return false"],
    ],
    'research' => [
        ['label' => 'Research Tree', 'js' => "sendData('pages','get','research','tree'); return false"],
        ['label' => 'Technology Tree', 'js' => "sendData('pages','get','research','techlib'); return false"],
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

echo '<div class="page-grid">';

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
    if (count($planets) === 0) {
        echo '<p>No planets discovered in your registry yet.</p>';
    } else {
        echo '<table width="100%" border="0"><tr><th align="left">Planet</th><th align="left">Size</th><th align="left">Bonus</th></tr>';
        foreach ($planets as $planet) {
            echo '<tr><td>' . h($planet['name']) . '</td><td>' . h($planet['size']) . '</td><td>' . h($planet['bonus']) . '</td></tr>';
        }
        echo '</table>';
    }
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

if ($main === 'military') {
    if ($sub === 'personnel') {
        echo '<div class="card full"><h4>Personnel Breakdown</h4>';
        echo '<table width="100%" border="0">';
        echo '<tr><td>Untrained Units</td><td>' . fnum($personnel->uuCount ?? 0) . '</td></tr>';
        echo '<tr><td>Attack Units</td><td>' . fnum($personnel->attackCount ?? 0) . '</td></tr>';
        echo '<tr><td>Defense Units</td><td>' . fnum($personnel->defenseCount ?? 0) . '</td></tr>';
        echo '<tr><td>Covert Units</td><td>' . fnum($personnel->covertCount ?? 0) . '</td></tr>';
        echo '<tr><td>Anti-Covert Units</td><td>' . fnum($personnel->anticovertCount ?? 0) . '</td></tr>';
        echo '</table>';
        echo '</div>';
    }
    if ($sub === 'armory') {
        echo '<div class="card"><h4>Armory Control</h4><p>Manage attack/defense equipment loadouts and repair weapons.</p><p><a href="javascript:void(0)" onclick="sendData(\'armory\',\'get\',\'mainDisplay\'); return false">Open Armory</a></p></div>';
    }
    if ($sub === 'training') {
        echo '<div class="card"><h4>Training Command</h4><p>Convert untrained units into combat-ready specialists.</p><p><a href="javascript:void(0)" onclick="sendData(\'train\',\'get\',\'mainDisplay\'); return false">Open Training</a></p></div>';
        echo '<div class="card"><h4>Demobilization</h4><p>Reverse assignments when strategy shifts.</p><p><a href="javascript:void(0)" onclick="sendData(\'untrain\',\'get\',\'mainDisplay\'); return false">Open Untrain</a></p></div>';
    }
    if ($sub === 'fleet') {
        echo '<div class="card"><h4>Fleet Operations</h4><p>Deploy, reposition, and monitor fleet readiness.</p><p><a href="javascript:void(0)" onclick="sendData(\'fleetdock\',\'get\',\'mainDisplay\'); return false">Open Fleet Dock</a></p><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'objects\'); return false">Scan Debris Fields</a></p></div>';
        echo '<div class="card"><h4>Shipyard and Mothership Controls</h4><p><a href="javascript:void(0)" onclick="sendData(\'fleetdock\',\'get\',\'upgrade_shipyard\'); return false">Upgrade Shipyard</a></p><p><a href="javascript:void(0)" onclick="sendData(\'fleetdock\',\'get\',\'upgrade_bay\'); return false">Upgrade Mothership Bay</a></p><p><a href="javascript:void(0)" onclick="sendData(\'fleetdock\',\'get\',\'mainDisplay\'); return false">Open Starship Build Console</a></p><p><a href="javascript:void(0)" onclick="sendData(\'megaforge\',\'get\',\'mainDisplay\'); return false">Open 90-Class Mega Forge</a></p></div>';
        echo '<div class="card"><h4>Orbital Installations</h4><p>Expand stations and bases to improve fleet staging and defensive projection.</p><p><a href="javascript:void(0)" onclick="sendData(\'stations\',\'get\',\'mainDisplay\'); return false">Open Stations Command</a></p><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'bases\'); return false">Open Universe Base Matrix</a></p></div>';
        echo '<div class="card"><h4>Interstellar Travel Network</h4><p>Use Jump Gates, Stargates, and hyperspace lanes for long-range force projection.</p><p><a href="javascript:void(0)" onclick="sendData(\'hyperspace\',\'get\',\'mainDisplay\'); return false">Open Hyperspace Transit Command</a></p><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'travel\'); return false">Open Universe Travel Matrix</a></p></div>';
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
        echo '<div class="card"><h4>Infrastructure Build Grid</h4><p><a href="javascript:void(0)" onclick="sendData(\'ogamebuildings\',\'get\',\'mainDisplay\'); return false">Open OGame Buildings Command</a></p></div>';

        echo '<div class="card full"><h4>OGame-Style Resource Output Grid</h4>';
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
        echo '<p>Manage OGame-style resource mining, food and water sustainment, and population growth.</p>';
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
        echo '<div class="card"><h4>OGame Building Matrix</h4>';
        echo '<p>Build and upgrade classic structures across resources, facilities, lunar systems, and defenses.</p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'ogamebuildings\',\'get\',\'mainDisplay\'); return false">Open OGame Buildings Command</a></p>';
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
}

if ($main === 'universe') {
    if ($sub === 'galaxies') {
        echo '<div class="card"><h4>Universe Control Seed</h4>';
        echo '<p><strong>Seed:</strong> U-' . h($universe['seed']) . '</p>';
        echo '<p><strong>Galaxy Clusters:</strong> ' . fnum($universe['summary']['totalGalaxies']) . '</p>';
        echo '<p><strong>Total Worlds:</strong> ' . fnum($universe['summary']['totalWorlds']) . '</p>';
        echo '<p><strong>Colonizable Worlds:</strong> ' . fnum($universe['summary']['colonizableWorlds']) . '</p>';
        echo '</div>';

        echo '<div class="card"><h4>Expansion Command</h4>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'planets\'); return false">Open Planet Registry</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'objects\'); return false">Scan Interstellar Objects</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'expedition\'); return false">Open Expedition Control</a></p>';
        echo '</div>';

        echo '<div class="card full"><h4>Galaxy Cluster Matrix</h4>';
        echo '<table class="mini-table" border="0" width="100%"><tr><th align="left">Galaxy</th><th align="left">Sectors</th><th align="left">Worlds</th><th align="left">Avg Habitability</th><th align="left">Moon Count</th></tr>';
        foreach ($universe['galaxies'] as $gal) {
            echo '<tr><td>' . h($gal['name']) . '</td><td>' . fnum($gal['sectors']) . '</td><td>' . fnum($gal['worlds']) . '</td><td>' . fnum($gal['avgHab']) . '%</td><td>' . fnum($gal['moons']) . '</td></tr>';
        }
        echo '</table></div>';

        $worldsPerPage = 50;
        $totalWorlds = count($universe['worlds']);
        $totalPages = max(1, (int)ceil($totalWorlds / $worldsPerPage));

        echo '<div class="card full"><h4>Planet and Moon Registry (50 Per Page)</h4>';
        echo '<p>Total Worlds: ' . fnum($totalWorlds) . ' | Pages: ' . fnum($totalPages) . ' &mdash; <em>Click any planet or moon to view details</em></p>';
        echo '<p>';
        for ($p = 1; $p <= $totalPages; $p++) {
            echo '<a href="javascript:void(0)" onclick="showGalaxyPage(' . $p . ',' . $totalPages . '); return false" style="margin-right:8px;">Page ' . $p . '</a>';
        }
        echo '</p>';

        for ($p = 1; $p <= $totalPages; $p++) {
            $slice = array_slice($universe['worlds'], ($p - 1) * $worldsPerPage, $worldsPerPage);
            $display = ($p === 1) ? 'block' : 'none';
            echo '<div id="galaxyPage' . $p . '" style="display:' . $display . ';">';
            echo '<table class="mini-table" border="0" width="100%">';
            echo '<tr><th align="left">Coordinate</th><th align="left">World</th><th align="left">Type</th><th align="left">Biome</th><th align="left">Habitability</th><th align="left">Moons</th><th align="left">Moon Class</th><th align="left">Status</th></tr>';
            foreach ($slice as $w) {
                $pd = htmlspecialchars(json_encode([
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

        echo '<script type="text/javascript">';
        echo 'function showGalaxyPage(page,total){for(var i=1;i<=total;i++){var el=document.getElementById("galaxyPage"+i);if(el){el.style.display=(i===page)?"block":"none";}}}';
        echo '</script>';
        echo '</div>';
    }

    if ($sub === 'planets') {
        echo '<div class="card"><h4>Colony Totals</h4>';
        echo '<p><strong>Owned Colonies:</strong> ' . fnum($universe['summary']['ownedColonies']) . '</p>';
        echo '<p><strong>Total Moons:</strong> ' . fnum($universe['summary']['totalMoons']) . '</p>';
        echo '<p><strong>Available Colonization Targets:</strong> ' . fnum($universe['summary']['colonizableWorlds']) . '</p>';
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
        echo '<p><em>Click any planet or moon count to view details</em></p>';
        echo '<table class="mini-table" border="0" width="100%"><tr><th align="left">Coordinate</th><th align="left">World</th><th align="left">Type</th><th align="left">Biome</th><th align="left">Habitability</th><th align="left">Moons</th><th align="left">Resource Signature</th><th align="left">Status</th></tr>';
        foreach (array_slice($universe['worlds'], 0, 48) as $w) {
            $resSig = 'M' . fnum($w['metal']) . ' / C' . fnum($w['crystal']) . ' / D' . fnum($w['deut']);
            $moonSig = ($w['moons'] > 0) ? (fnum($w['moons']) . ' (' . h($w['moonClass']) . ')') : '0';
            $pd = htmlspecialchars(json_encode([
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
            echo '<td>' . h($w['coord']) . '</td>';
            echo '<td><a href="javascript:void(0)" onclick="showPlanetDetail(' . $pd . ')" style="color:#adf">' . h($w['name']) . '</a></td>';
            echo '<td>' . h($w['type']) . '</td>';
            echo '<td>' . h($w['biome']) . '</td>';
            echo '<td>' . fnum($w['habitability']) . '%</td>';
            echo '<td' . $moonOnclick . '>' . $moonSig . '</td>';
            echo '<td>' . $resSig . '</td>';
            echo '<td>' . h($w['owner']) . '</td>';
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
}

if ($main === 'research') {
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
}

if (isset($systemDetails[$main][$sub])) {
    renderInfoBlock($systemDetails[$main][$sub]);
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