<?php
include("../config.php");

$pagegen = new page_gen();
$pagegen->round_to = 4;
$pagegen->start();

$s = new Game();
if (!$s->loggedIn || !isset($_GET['time'])) {
    header("Location: ../index.php");
    exit;
}

$uid = (int)$_SESSION['userid'];
$status = '';

function cg_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function cg_num($value): string {
    return number_format((float)$value);
}

function cg_catalog(): array {
    return [
        ['key' => 'imperial_administration', 'track' => 'Administration', 'name' => 'Imperial Administration', 'effect' => 'Raises command administration and policy execution.', 'image' => 'images/ui/gov/imperial-administration.svg', 'baseNq' => 120000, 'baseM' => 4500, 'baseC' => 3200, 'baseD' => 1800, 'scale' => 1.54],
        ['key' => 'stellar_bureaucracy', 'track' => 'Administration', 'name' => 'Stellar Bureaucracy', 'effect' => 'Improves colony governance throughput and compliance.', 'image' => 'images/ui/gov/stellar-bureaucracy.svg', 'baseNq' => 128000, 'baseM' => 4700, 'baseC' => 3400, 'baseD' => 1900, 'scale' => 1.55],
        ['key' => 'judicial_reform_court', 'track' => 'Administration', 'name' => 'Judicial Reform Court', 'effect' => 'Strengthens governance consistency and enforcement trust.', 'image' => 'images/ui/gov/judicial-reform-court.svg', 'baseNq' => 276000, 'baseM' => 8300, 'baseC' => 7200, 'baseD' => 4800, 'scale' => 1.61],

        ['key' => 'technocracy_council', 'track' => 'Research and Policy', 'name' => 'Technocracy Council', 'effect' => 'Accelerates technical policy approval pipelines.', 'image' => 'images/ui/gov/technocracy-council.svg', 'baseNq' => 136000, 'baseM' => 5100, 'baseC' => 3900, 'baseD' => 2100, 'scale' => 1.56],
        ['key' => 'ai_governance_grid', 'track' => 'Research and Policy', 'name' => 'AI Governance Grid', 'effect' => 'Unlocks advanced automated policy synthesis and tuning.', 'image' => 'images/ui/gov/ai-governance-grid.svg', 'baseNq' => 360000, 'baseM' => 10000, 'baseC' => 9000, 'baseD' => 6200, 'scale' => 1.64],
        ['key' => 'resource_quota_authority', 'track' => 'Research and Policy', 'name' => 'Resource Quota Authority', 'effect' => 'Improves strategic stockpile balancing across systems.', 'image' => 'images/ui/gov/resource-quota-authority.svg', 'baseNq' => 186000, 'baseM' => 6400, 'baseC' => 5200, 'baseD' => 3100, 'scale' => 1.58],

        ['key' => 'economic_senate', 'track' => 'Economy and Trade', 'name' => 'Economic Senate', 'effect' => 'Optimizes taxation and market-wide resource policy.', 'image' => 'images/ui/gov/economic-senate.svg', 'baseNq' => 164000, 'baseM' => 5900, 'baseC' => 4700, 'baseD' => 2700, 'scale' => 1.57],
        ['key' => 'trade_guild_chamber', 'track' => 'Economy and Trade', 'name' => 'Trade Guild Chamber', 'effect' => 'Boosts logistics doctrine and interstellar commerce cadence.', 'image' => 'images/ui/gov/trade-guild-chamber.svg', 'baseNq' => 174000, 'baseM' => 6100, 'baseC' => 5000, 'baseD' => 2900, 'scale' => 1.58],
        ['key' => 'colonial_governorate', 'track' => 'Economy and Trade', 'name' => 'Colonial Governorate', 'effect' => 'Improves colony rule stability and remote administration.', 'image' => 'images/ui/gov/colonial-governorate.svg', 'baseNq' => 242000, 'baseM' => 7700, 'baseC' => 6500, 'baseD' => 4200, 'scale' => 1.60],

        ['key' => 'defense_directorate', 'track' => 'War Command', 'name' => 'Defense Directorate', 'effect' => 'Strengthens wartime doctrine and mobilization readiness.', 'image' => 'images/ui/gov/defense-directorate.svg', 'baseNq' => 145000, 'baseM' => 5300, 'baseC' => 4300, 'baseD' => 2300, 'scale' => 1.56],
        ['key' => 'fleet_command_doctrine', 'track' => 'War Command', 'name' => 'Fleet Command Doctrine', 'effect' => 'Improves commander chain efficiency for fleet operations.', 'image' => 'images/ui/gov/fleet-command-doctrine.svg', 'baseNq' => 198000, 'baseM' => 6700, 'baseC' => 5500, 'baseD' => 3400, 'scale' => 1.59],
        ['key' => 'war_cabinet', 'track' => 'War Command', 'name' => 'War Cabinet', 'effect' => 'Boosts military response cadence and chain-of-command tempo.', 'image' => 'images/ui/gov/war-cabinet.svg', 'baseNq' => 336000, 'baseM' => 9500, 'baseC' => 8400, 'baseD' => 5800, 'scale' => 1.63],

        ['key' => 'security_bureau', 'track' => 'Security and Intelligence', 'name' => 'Security Bureau', 'effect' => 'Raises anti-sabotage protocol quality and resilience.', 'image' => 'images/ui/gov/security-bureau.svg', 'baseNq' => 212000, 'baseM' => 6900, 'baseC' => 5800, 'baseD' => 3600, 'scale' => 1.59],
        ['key' => 'intelligence_board', 'track' => 'Security and Intelligence', 'name' => 'Intelligence Board', 'effect' => 'Expands threat analysis and strategic awareness depth.', 'image' => 'images/ui/gov/intelligence-board.svg', 'baseNq' => 226000, 'baseM' => 7300, 'baseC' => 6200, 'baseD' => 3900, 'scale' => 1.60],
        ['key' => 'diplomatic_cabinet', 'track' => 'Security and Intelligence', 'name' => 'Diplomatic Cabinet', 'effect' => 'Enhances treaty posture and alliance coordination speed.', 'image' => 'images/ui/gov/diplomatic-cabinet.svg', 'baseNq' => 258000, 'baseM' => 7900, 'baseC' => 6900, 'baseD' => 4500, 'scale' => 1.61],

        ['key' => 'expansion_ministry', 'track' => 'Expansion and Space Control', 'name' => 'Expansion Ministry', 'effect' => 'Increases expansion planning efficiency and lane control.', 'image' => 'images/ui/gov/expansion-ministry.svg', 'baseNq' => 154000, 'baseM' => 5600, 'baseC' => 4400, 'baseD' => 2500, 'scale' => 1.57],
        ['key' => 'orbital_planning_commission', 'track' => 'Expansion and Space Control', 'name' => 'Orbital Planning Commission', 'effect' => 'Optimizes station and orbital governance strategies.', 'image' => 'images/ui/gov/orbital-planning-commission.svg', 'baseNq' => 294000, 'baseM' => 8600, 'baseC' => 7600, 'baseD' => 5100, 'scale' => 1.62],
        ['key' => 'expedition_authority', 'track' => 'Expansion and Space Control', 'name' => 'Expedition Authority', 'effect' => 'Raises deep-space expedition directive quality.', 'image' => 'images/ui/gov/expedition-authority.svg', 'baseNq' => 314000, 'baseM' => 9000, 'baseC' => 7900, 'baseD' => 5400, 'scale' => 1.62],
    ];
}

$catalog = cg_catalog();
$catalogByKey = [];
foreach ($catalog as $item) {
    $catalogByKey[$item['key']] = $item;
}

$allowedSettings = [
    'commander_mode' => ['strategist', 'warlord', 'architect', 'shadow'],
    'governance_style' => ['balanced', 'technocracy', 'militarist', 'mercantile'],
    'policy_cycle' => ['adaptive', 'fixed', 'rapid', 'conservative'],
    'visual_pack' => ['classic_command', 'stargate_naval', 'strategic_grid'],
    'alert_level' => ['standard', 'high', 'war_only'],
    'auto_delegate' => ['0', '1'],
];

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

$s->query("CREATE TABLE IF NOT EXISTS governance_system_levels (
    uid INT NOT NULL,
    gov_key VARCHAR(64) NOT NULL,
    level INT NOT NULL DEFAULT 0,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY(uid, gov_key)
)");

$s->query("CREATE TABLE IF NOT EXISTS commander_settings (
    uid INT NOT NULL PRIMARY KEY,
    commander_mode VARCHAR(24) NOT NULL DEFAULT 'strategist',
    governance_style VARCHAR(24) NOT NULL DEFAULT 'balanced',
    policy_cycle VARCHAR(24) NOT NULL DEFAULT 'adaptive',
    visual_pack VARCHAR(24) NOT NULL DEFAULT 'classic_command',
    alert_level VARCHAR(24) NOT NULL DEFAULT 'standard',
    auto_delegate TINYINT(1) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");
$s->query("INSERT IGNORE INTO commander_settings (uid) VALUES (" . $uid . ")");

foreach ($catalog as $gov) {
    $key = $gov['key'];
    $s->query("INSERT IGNORE INTO governance_system_levels (uid, gov_key, level, enabled) VALUES (" . $uid . ", '" . $key . "', 0, 1)");
}

if (isset($_GET['id']) && $_GET['id'] === 'upgrade') {
    $key = isset($_GET['atype']) ? strtolower((string)$_GET['atype']) : '';
    if (!isset($catalogByKey[$key])) {
        $status = 'Unknown governance system.';
    } else {
        $def = $catalogByKey[$key];
        $qLvl = $s->query("SELECT level FROM governance_system_levels WHERE uid=" . $uid . " AND gov_key='" . $key . "' LIMIT 1");
        $cur = ($qLvl && $qLvl->num_rows > 0) ? (int)($qLvl->fetch_object()->level ?? 0) : 0;

        $needNq = (int)round($def['baseNq'] * pow($def['scale'], $cur));
        $needM = (int)round($def['baseM'] * pow($def['scale'], $cur));
        $needC = (int)round($def['baseC'] * pow($def['scale'], $cur));
        $needD = (int)round($def['baseD'] * pow($def['scale'], $cur));

        $qBank = $s->query("SELECT onHand FROM bank WHERE uid=" . $uid . " LIMIT 1");
        $bank = $qBank ? $qBank->fetch_object() : (object)['onHand' => 0];
        $qRes = $s->query("SELECT metal, crystal, deuterium FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
        $res = $qRes ? $qRes->fetch_object() : (object)['metal' => 0, 'crystal' => 0, 'deuterium' => 0];

        if ((int)$bank->onHand < $needNq || (int)$res->metal < $needM || (int)$res->crystal < $needC || (int)$res->deuterium < $needD) {
            $status = 'Insufficient resources for ' . $def['name'] . ' upgrade.';
        } else {
            $s->query("UPDATE bank SET onHand=onHand-" . $needNq . " WHERE uid=" . $uid . " LIMIT 1");
            $s->query("UPDATE player_resources SET metal=metal-" . $needM . ", crystal=crystal-" . $needC . ", deuterium=deuterium-" . $needD . " WHERE uid=" . $uid . " LIMIT 1");
            $s->query("UPDATE governance_system_levels SET level=level+1 WHERE uid=" . $uid . " AND gov_key='" . $key . "' LIMIT 1");
            $status = $def['name'] . ' upgraded to level ' . ($cur + 1) . '.';
        }
    }
}

if (isset($_GET['id']) && $_GET['id'] === 'toggle') {
    $key = isset($_GET['atype']) ? strtolower((string)$_GET['atype']) : '';
    if (!isset($catalogByKey[$key])) {
        $status = 'Unknown governance system toggle target.';
    } else {
        $s->query("UPDATE governance_system_levels SET enabled = CASE WHEN enabled = 1 THEN 0 ELSE 1 END WHERE uid=" . $uid . " AND gov_key='" . $key . "' LIMIT 1");
        $status = $catalogByKey[$key]['name'] . ' state updated.';
    }
}

if (isset($_GET['id']) && $_GET['id'] === 'set') {
    $raw = isset($_GET['atype']) ? strtolower((string)$_GET['atype']) : '';
    $parts = explode(':', $raw, 2);
    $settingKey = $parts[0] ?? '';
    $settingValue = $parts[1] ?? '';

    if (!isset($allowedSettings[$settingKey])) {
        $status = 'Unknown commander setting.';
    } elseif (!in_array($settingValue, $allowedSettings[$settingKey], true)) {
        $status = 'Invalid value for setting ' . $settingKey . '.';
    } else {
        $s->query("UPDATE commander_settings SET " . $settingKey . "='" . $settingValue . "' WHERE uid=" . $uid . " LIMIT 1");
        $status = 'Commander setting updated: ' . str_replace('_', ' ', $settingKey) . ' -> ' . str_replace('_', ' ', $settingValue);
    }
}

if (isset($_GET['id']) && $_GET['id'] === 'preset') {
    $preset = isset($_GET['atype']) ? strtolower((string)$_GET['atype']) : '';
    if ($preset === 'war') {
        $s->query("UPDATE commander_settings SET commander_mode='warlord', governance_style='militarist', policy_cycle='rapid', visual_pack='stargate_naval', alert_level='war_only', auto_delegate=1 WHERE uid=" . $uid . " LIMIT 1");
        $status = 'Preset applied: War Command Doctrine.';
    } elseif ($preset === 'economy') {
        $s->query("UPDATE commander_settings SET commander_mode='architect', governance_style='mercantile', policy_cycle='fixed', visual_pack='classic_command', alert_level='standard', auto_delegate=0 WHERE uid=" . $uid . " LIMIT 1");
        $status = 'Preset applied: Economic Directorate Doctrine.';
    } elseif ($preset === 'shadow') {
        $s->query("UPDATE commander_settings SET commander_mode='shadow', governance_style='technocracy', policy_cycle='adaptive', visual_pack='strategic_grid', alert_level='high', auto_delegate=1 WHERE uid=" . $uid . " LIMIT 1");
        $status = 'Preset applied: Shadow Intelligence Doctrine.';
    } else {
        $status = 'Unknown preset.';
    }
}

$qLevels = $s->query("SELECT gov_key, level, enabled FROM governance_system_levels WHERE uid=" . $uid);
$levels = [];
if ($qLevels) {
    while ($r = $qLevels->fetch_object()) {
        $levels[(string)$r->gov_key] = [
            'level' => (int)($r->level ?? 0),
            'enabled' => (int)($r->enabled ?? 1),
        ];
    }
}

$qSettings = $s->query("SELECT commander_mode, governance_style, policy_cycle, visual_pack, alert_level, auto_delegate FROM commander_settings WHERE uid=" . $uid . " LIMIT 1");
$settings = $qSettings ? $qSettings->fetch_object() : (object)[
    'commander_mode' => 'strategist',
    'governance_style' => 'balanced',
    'policy_cycle' => 'adaptive',
    'visual_pack' => 'classic_command',
    'alert_level' => 'standard',
    'auto_delegate' => 0,
];

$qBank = $s->query("SELECT onHand FROM bank WHERE uid=" . $uid . " LIMIT 1");
$bank = $qBank ? $qBank->fetch_object() : (object)['onHand' => 0];
$qRes = $s->query("SELECT metal, crystal, deuterium FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
$res = $qRes ? $qRes->fetch_object() : (object)['metal' => 0, 'crystal' => 0, 'deuterium' => 0];

$totalLevel = 0;
$enabledCount = 0;
foreach ($catalog as $gov) {
    $k = $gov['key'];
    $lv = (int)($levels[$k]['level'] ?? 0);
    $en = (int)($levels[$k]['enabled'] ?? 1);
    $totalLevel += $lv;
    if ($en === 1) {
        $enabledCount++;
    }
}

$commandBonus = 1 + ($totalLevel * 0.009);
$policyEfficiency = 1 + ($enabledCount * 0.015);
$warControl = 1 + (((int)($levels['war_cabinet']['level'] ?? 0) + (int)($levels['fleet_command_doctrine']['level'] ?? 0)) * 0.02);

$grouped = [];
foreach ($catalog as $gov) {
    $track = (string)($gov['track'] ?? 'General');
    if (!isset($grouped[$track])) {
        $grouped[$track] = [];
    }
    $grouped[$track][] = $gov;
}
?>
<div class="page-hub">
    <div class="page-hub-head gov-head">
        <h3>Commander Governance Directorate</h3>
        <p>Redesigned strategic governance interface with 18 systems, doctrine presets, and command-grade setting controls.</p>
        <div class="gov-preset-row">
            <a href="javascript:void(0)" class="gov-preset" onclick="sendData('commandergov','get','preset','war'); return false">War Preset</a>
            <a href="javascript:void(0)" class="gov-preset" onclick="sendData('commandergov','get','preset','economy'); return false">Economy Preset</a>
            <a href="javascript:void(0)" class="gov-preset" onclick="sendData('commandergov','get','preset','shadow'); return false">Shadow Preset</a>
        </div>
    </div>

    <?php if ($status !== '') { ?>
    <div class="card full"><strong><?= cg_h($status); ?></strong></div>
    <?php } ?>

    <div class="page-grid gov-kpi-grid">
        <div class="card gov-kpi">
            <h4>Commander Treasury</h4>
            <p><strong>Naquadah:</strong> <?= cg_num((int)$bank->onHand); ?></p>
            <p><strong>Metal:</strong> <?= cg_num((int)$res->metal); ?></p>
            <p><strong>Crystal:</strong> <?= cg_num((int)$res->crystal); ?></p>
            <p><strong>Deuterium:</strong> <?= cg_num((int)$res->deuterium); ?></p>
        </div>

        <div class="card gov-kpi">
            <h4>Governance Effects</h4>
            <p><strong>Total Governance Levels:</strong> <?= cg_num($totalLevel); ?></p>
            <p><strong>Enabled Systems:</strong> <?= cg_num($enabledCount); ?>/18</p>
            <p><strong>Command Bonus:</strong> <?= cg_num($commandBonus); ?>x</p>
            <p><strong>Policy Efficiency:</strong> <?= cg_num($policyEfficiency); ?>x</p>
            <p><strong>War Control:</strong> <?= cg_num($warControl); ?>x</p>
            <p><a href="javascript:void(0)" onclick="sendData('pages','get','diplomacy','governance'); return false">Open Governance Overview</a></p>
        </div>
    </div>

    <div class="card full gov-settings-card">
            <h4>Commander Options and Settings</h4>
            <table class="mini-table gov-settings-table" border="0" width="100%">
                <tr><th align="left">Setting</th><th align="left">Current</th><th align="left">Options</th></tr>
                <tr>
                    <td>Commander Mode</td>
                    <td><?= cg_h((string)$settings->commander_mode); ?></td>
                    <td>
                        <a href="javascript:void(0)" class="gov-option" onclick="sendData('commandergov','get','set','commander_mode:strategist'); return false">Strategist</a>
                        <a href="javascript:void(0)" class="gov-option" onclick="sendData('commandergov','get','set','commander_mode:warlord'); return false">Warlord</a>
                        <a href="javascript:void(0)" class="gov-option" onclick="sendData('commandergov','get','set','commander_mode:architect'); return false">Architect</a>
                        <a href="javascript:void(0)" class="gov-option" onclick="sendData('commandergov','get','set','commander_mode:shadow'); return false">Shadow</a>
                    </td>
                </tr>
                <tr>
                    <td>Governance Style</td>
                    <td><?= cg_h((string)$settings->governance_style); ?></td>
                    <td>
                        <a href="javascript:void(0)" class="gov-option" onclick="sendData('commandergov','get','set','governance_style:balanced'); return false">Balanced</a>
                        <a href="javascript:void(0)" class="gov-option" onclick="sendData('commandergov','get','set','governance_style:technocracy'); return false">Technocracy</a>
                        <a href="javascript:void(0)" class="gov-option" onclick="sendData('commandergov','get','set','governance_style:militarist'); return false">Militarist</a>
                        <a href="javascript:void(0)" class="gov-option" onclick="sendData('commandergov','get','set','governance_style:mercantile'); return false">Mercantile</a>
                    </td>
                </tr>
                <tr>
                    <td>Policy Cycle</td>
                    <td><?= cg_h((string)$settings->policy_cycle); ?></td>
                    <td>
                        <a href="javascript:void(0)" class="gov-option" onclick="sendData('commandergov','get','set','policy_cycle:adaptive'); return false">Adaptive</a>
                        <a href="javascript:void(0)" class="gov-option" onclick="sendData('commandergov','get','set','policy_cycle:fixed'); return false">Fixed</a>
                        <a href="javascript:void(0)" class="gov-option" onclick="sendData('commandergov','get','set','policy_cycle:rapid'); return false">Rapid</a>
                        <a href="javascript:void(0)" class="gov-option" onclick="sendData('commandergov','get','set','policy_cycle:conservative'); return false">Conservative</a>
                    </td>
                </tr>
                <tr>
                    <td>Visual Pack</td>
                    <td><?= cg_h((string)$settings->visual_pack); ?></td>
                    <td>
                        <a href="javascript:void(0)" class="gov-option" onclick="sendData('commandergov','get','set','visual_pack:classic_command'); return false">Classic Command</a>
                        <a href="javascript:void(0)" class="gov-option" onclick="sendData('commandergov','get','set','visual_pack:stargate_naval'); return false">Stargate Naval</a>
                        <a href="javascript:void(0)" class="gov-option" onclick="sendData('commandergov','get','set','visual_pack:strategic_grid'); return false">Strategic Grid</a>
                    </td>
                </tr>
                <tr>
                    <td>Alert Level</td>
                    <td><?= cg_h((string)$settings->alert_level); ?></td>
                    <td>
                        <a href="javascript:void(0)" class="gov-option" onclick="sendData('commandergov','get','set','alert_level:standard'); return false">Standard</a>
                        <a href="javascript:void(0)" class="gov-option" onclick="sendData('commandergov','get','set','alert_level:high'); return false">High</a>
                        <a href="javascript:void(0)" class="gov-option" onclick="sendData('commandergov','get','set','alert_level:war_only'); return false">War Only</a>
                    </td>
                </tr>
                <tr>
                    <td>Auto Delegate</td>
                    <td><?= ((int)$settings->auto_delegate === 1) ? 'Enabled' : 'Disabled'; ?></td>
                    <td>
                        <a href="javascript:void(0)" class="gov-option" onclick="sendData('commandergov','get','set','auto_delegate:1'); return false">Enable</a>
                        <a href="javascript:void(0)" class="gov-option" onclick="sendData('commandergov','get','set','auto_delegate:0'); return false">Disable</a>
                    </td>
                </tr>
            </table>
    </div>

    <div class="card full gov-matrix-card">
        <h4>18 Governance Systems</h4>
        <p>Systems are grouped by doctrine track for faster policy tuning and build sequencing.</p>

        <?php foreach ($grouped as $track => $systems) { ?>
            <div class="gov-track-head"><?= cg_h($track); ?></div>
            <div class="gov-system-grid">
                <?php foreach ($systems as $gov) {
                    $key = $gov['key'];
                    $lv = (int)($levels[$key]['level'] ?? 0);
                    $enabled = (int)($levels[$key]['enabled'] ?? 1);
                    $needNq = (int)round($gov['baseNq'] * pow($gov['scale'], $lv));
                    $needM = (int)round($gov['baseM'] * pow($gov['scale'], $lv));
                    $needC = (int)round($gov['baseC'] * pow($gov['scale'], $lv));
                    $needD = (int)round($gov['baseD'] * pow($gov['scale'], $lv));
                ?>
                <div class="gov-system-card <?= $enabled === 1 ? 'enabled' : 'disabled'; ?>">
                    <div class="gov-system-top">
                        <img src="<?= cg_h($gov['image']); ?>" alt="<?= cg_h($gov['name']); ?>" width="30" height="30" />
                        <div>
                            <div class="gov-system-name"><?= cg_h($gov['name']); ?></div>
                            <div class="gov-system-key"><?= cg_h($key); ?></div>
                        </div>
                    </div>
                    <div class="gov-system-stats">
                        <span>Level <?= cg_num($lv); ?></span>
                        <span><?= $enabled === 1 ? 'Active' : 'Dormant'; ?></span>
                    </div>
                    <p class="gov-system-effect"><?= cg_h($gov['effect']); ?></p>
                    <div class="gov-cost-line">Cost: <?= cg_num($needNq); ?> NQ / <?= cg_num($needM); ?> M / <?= cg_num($needC); ?> C / <?= cg_num($needD); ?> D</div>
                    <div class="gov-system-actions">
                        <a href="javascript:void(0)" onclick="sendData('commandergov','get','upgrade','<?= cg_h($key); ?>'); return false">Upgrade</a>
                        <a href="javascript:void(0)" onclick="sendData('commandergov','get','toggle','<?= cg_h($key); ?>'); return false"><?= $enabled === 1 ? 'Disable' : 'Enable'; ?></a>
                    </div>
                </div>
                <?php } ?>
            </div>
        <?php } ?>
    </div>
</div>
<?php

echo "Query Count: " . $s->queryCount . "<br>";
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());
?>
