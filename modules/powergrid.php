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
if (isset($_GET['atype']) && str_starts_with((string)$_GET['atype'], 'node=')) {
    $_GET['node'] = (int)substr((string)$_GET['atype'], 5);
}
$status = '';

function pg_h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function pg_num($value): string { return number_format((float)$value); }
function pg_type_label(string $type): string { return ucwords(str_replace('_', ' ', $type)); }
function pg_cost(int $level): array {
    $scale = max(1, $level);
    return ['metal' => (int)(1200 * pow(1.55, $scale - 1)), 'crystal' => (int)(700 * pow(1.52, $scale - 1)), 'deuterium' => (int)(240 * pow(1.50, $scale - 1))];
}
function pg_tick(Game $s, int $uid): void {
    $q = $s->query("SELECT * FROM power_nodes WHERE uid=" . $uid);
    if (!$q) return;
    while ($node = $q->fetch_assoc()) {
        $last = strtotime((string)$node['last_tick_at']);
        $hours = $last ? min(168, max(0, (int)floor((time() - $last) / 3600))) : 0;
        if ($hours < 1) continue;
        $generation = (int)$node['production_rate'] * $hours;
        $consumption = (int)$node['consumption_rate'] * $hours;
        $stored = (int)$node['power_stored'];
        $capacity = max(1, (int)$node['power_capacity']);
        $net = $generation - $consumption;
        $status = 'online';
        if ($net >= 0) {
            $stored = min($capacity, $stored + $net);
        } else {
            $stored += $net;
            if ($stored < 0) { $stored = 0; $status = 'offline'; }
            else { $status = 'brownout'; }
        }
        if ($stored >= $capacity) $status = 'overloaded';
        $s->query("UPDATE power_nodes SET power_stored=" . $stored . ", grid_status='" . $s->db_link->real_escape_string($status) . "', last_tick_at=NOW() WHERE node_id=" . (int)$node['node_id'] . " AND uid=" . $uid);
    }
}
function pg_refresh_rates(Game $s, int $uid): void {
    $q = $s->query("SELECT node_id, reactor_level, storage_level, grid_level, efficiency_level FROM power_nodes WHERE uid=" . $uid);
    if (!$q) return;
    while ($n = $q->fetch_assoc()) {
        $assets = $s->query("SELECT COALESCE(SUM(base_generation * asset_level),0) AS gen, COALESCE(SUM(base_consumption * asset_level),0) AS use_rate FROM power_assets WHERE node_id=" . (int)$n['node_id'] . " AND enabled=1");
        $a = $assets ? $assets->fetch_assoc() : ['gen' => 0, 'use_rate' => 0];
        $generation = (int)$a['gen'] + ((int)$n['reactor_level'] * 420) + ((int)$n['grid_level'] * 80);
        $consumption = max(1, (int)$a['use_rate'] - ((int)$n['efficiency_level'] * 30) + ((int)$n['grid_level'] * 25));
        $capacity = 10000 + ((int)$n['storage_level'] * 12000) + ((int)$n['grid_level'] * 3500);
        $s->query("UPDATE power_nodes SET production_rate=" . $generation . ", consumption_rate=" . $consumption . ", power_capacity=" . $capacity . " WHERE node_id=" . (int)$n['node_id'] . " AND uid=" . $uid);
    }
}
function pg_update_resources(Game $s, int $uid, int $metal, int $crystal, int $deuterium): bool {
    $q = $s->query("SELECT metal,crystal,deuterium FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
    if (!$q || !$q->num_rows) return false;
    $r = $q->fetch_assoc();
    if ((int)$r['metal'] < $metal || (int)$r['crystal'] < $crystal || (int)$r['deuterium'] < $deuterium) return false;
    return (bool)$s->query("UPDATE player_resources SET metal=metal-" . $metal . ", crystal=crystal-" . $crystal . ", deuterium=deuterium-" . $deuterium . " WHERE uid=" . $uid);
}

pg_tick($s, $uid);
pg_refresh_rates($s, $uid);

if (isset($_GET['id']) && $_GET['id'] === 'upgrade') {
    $nodeId = (int)($_GET['atype'] ?? 0);
    $kind = (string)($_GET['subject'] ?? 'reactor');
    $allowed = ['reactor' => 'reactor_level', 'storage' => 'storage_level', 'grid' => 'grid_level', 'efficiency' => 'efficiency_level'];
    if ($nodeId > 0 && isset($allowed[$kind])) {
        $field = $allowed[$kind];
        $nodeQ = $s->query("SELECT * FROM power_nodes WHERE node_id=" . $nodeId . " AND uid=" . $uid . " LIMIT 1");
        $node = $nodeQ ? $nodeQ->fetch_assoc() : null;
        if ($node) {
            $level = (int)$node[$field] + 1;
            $cost = pg_cost($level);
            if (pg_update_resources($s, $uid, $cost['metal'], $cost['crystal'], $cost['deuterium'])) {
                $s->query("UPDATE power_nodes SET " . $field . "=" . $level . " WHERE node_id=" . $nodeId . " AND uid=" . $uid);
                pg_refresh_rates($s, $uid);
                $status = '<div class="pg-alert success">' . pg_h($kind) . ' upgraded to level ' . $level . '.</div>';
            } else {
                $status = '<div class="pg-alert danger">Insufficient Metal, Crystal, or Deuterium for this upgrade.</div>';
            }
        }
    }
}

if (isset($_GET['id']) && $_GET['id'] === 'transfer') {
    $source = (int)($_GET['atype'] ?? 0);
    $target = (int)($_GET['subject'] ?? 0);
    $amount = max(0, (int)($_GET['message'] ?? 0));
    if ($source > 0 && $target > 0 && $source !== $target && $amount > 0) {
        $sourceQ = $s->query("SELECT * FROM power_nodes WHERE node_id=" . $source . " AND uid=" . $uid . " LIMIT 1");
        $targetQ = $s->query("SELECT * FROM power_nodes WHERE node_id=" . $target . " AND uid=" . $uid . " LIMIT 1");
        $src = $sourceQ ? $sourceQ->fetch_assoc() : null;
        $dst = $targetQ ? $targetQ->fetch_assoc() : null;
        if ($src && $dst && (int)$src['power_stored'] >= $amount) {
            $available = max(0, (int)$dst['power_capacity'] - (int)$dst['power_stored']);
            $moved = min($amount, $available);
            if ($moved > 0) {
                $s->query("UPDATE power_nodes SET power_stored=power_stored-" . $moved . " WHERE node_id=" . $source . " AND uid=" . $uid);
                $s->query("UPDATE power_nodes SET power_stored=power_stored+" . $moved . " WHERE node_id=" . $target . " AND uid=" . $uid);
                $s->query("INSERT INTO power_transfers (uid,source_node_id,target_node_id,amount,status) VALUES (" . $uid . "," . $source . "," . $target . "," . $moved . ",'complete')");
                $status = '<div class="pg-alert success">Transferred ' . pg_num($moved) . ' power units.</div>';
            } else { $status = '<div class="pg-alert danger">Target storage is full.</div>'; }
        } else { $status = '<div class="pg-alert danger">Transfer failed: check source charge and node ownership.</div>'; }
    }
}

pg_tick($s, $uid);
$nodesQ = $s->query("SELECT * FROM power_nodes WHERE uid=" . $uid . " ORDER BY FIELD(node_type,'homeworld','planet','moon','starbase','moonbase','spacestation'), node_id");
$nodes = [];
if ($nodesQ) while ($n = $nodesQ->fetch_assoc()) $nodes[] = $n;
$selected = isset($_GET['node']) ? (int)$_GET['node'] : (int)($nodes[0]['node_id'] ?? 0);
$active = null;
foreach ($nodes as $n) if ((int)$n['node_id'] === $selected) { $active = $n; break; }
if (!$active && $nodes) $active = $nodes[0];
$assets = [];
if ($active) {
    $assetQ = $s->query("SELECT * FROM power_assets WHERE node_id=" . (int)$active['node_id'] . " ORDER BY asset_id");
    if ($assetQ) while ($a = $assetQ->fetch_assoc()) $assets[] = $a;
}
?>
<div class="power-grid-module">
  <div class="pg-header"><div><span class="pg-kicker">GRID CONTROL // POWER NETWORK</span><h2>World Power Systems</h2><p>Route energy across homeworlds, moons, starbases, moon bases, and orbital stations.</p></div><div class="pg-badge">NEXUS-STYLE INDUSTRIAL GRID<br><strong>ONLINE CONTROL</strong></div></div>
  <?= $status ?>
  <div class="pg-summary"><div><span>Nodes</span><strong><?= pg_num(count($nodes)); ?></strong></div><div><span>Online</span><strong><?= pg_num(count(array_filter($nodes, fn($n) => $n['grid_status'] === 'online'))); ?></strong></div><div><span>Brownout</span><strong><?= pg_num(count(array_filter($nodes, fn($n) => $n['grid_status'] === 'brownout'))); ?></strong></div><div><span>Stored Power</span><strong><?= pg_num(array_sum(array_map(fn($n) => (int)$n['power_stored'], $nodes))); ?></strong></div></div>
  <div class="pg-layout">
    <aside class="pg-nodes"><h3>Power Nodes</h3><?php foreach ($nodes as $n): ?><a class="pg-node <?= (int)$n['node_id'] === (int)($active['node_id'] ?? 0) ? 'active' : ''; ?>" href="javascript:void(0)" onclick="sendData('powergrid','get','mainDisplay','node=<?= (int)$n['node_id']; ?>');return false"><span class="pg-node-icon"><?= strtoupper(substr($n['node_type'],0,2)); ?></span><span><b><?= pg_h($n['node_name']); ?></b><small><?= pg_type_label($n['node_type']); ?> · <?= pg_h($n['grid_status']); ?></small></span><em><?= pg_num($n['power_stored']); ?>/<?= pg_num($n['power_capacity']); ?></em></a><?php endforeach; ?></aside>
    <section class="pg-main">
      <?php if ($active): ?>
      <div class="pg-active-head"><div><span class="pg-kicker"><?= pg_type_label($active['node_type']); ?></span><h3><?= pg_h($active['node_name']); ?></h3></div><div class="pg-status <?= pg_h($active['grid_status']); ?>"><?= strtoupper(pg_h($active['grid_status'])); ?></div></div>
      <div class="pg-meter"><div class="pg-meter-label"><span>Power Storage</span><strong><?= pg_num($active['power_stored']); ?> / <?= pg_num($active['power_capacity']); ?></strong></div><div class="pg-meter-track"><i style="width:<?= min(100, (int)round(((int)$active['power_stored'] / max(1,(int)$active['power_capacity'])) * 100)); ?>%"></i></div><small>Generation <?= pg_num($active['production_rate']); ?>/hr · Consumption <?= pg_num($active['consumption_rate']); ?>/hr</small></div>
      <div class="pg-upgrades"><h4>Grid Infrastructure</h4><div class="pg-upgrade-grid"><?php foreach (['reactor' => 'Reactor', 'storage' => 'Storage', 'grid' => 'Distribution Grid', 'efficiency' => 'Efficiency'] as $key => $label): $field=$key.'_level'; $level=(int)$active[$field]; $cost=pg_cost($level+1); ?><div class="pg-upgrade"><span><?= pg_h($label); ?></span><strong>Level <?= $level; ?></strong><small><?= pg_num($cost['metal']); ?> M · <?= pg_num($cost['crystal']); ?> C · <?= pg_num($cost['deuterium']); ?> D</small><a href="javascript:void(0)" onclick="sendData('powergrid','get','upgrade','<?= (int)$active['node_id']; ?>','<?= $key; ?>');return false">Upgrade</a></div><?php endforeach; ?></div></div>
      <div class="pg-assets"><h4>Installed Systems</h4><div class="pg-asset-grid"><?php foreach ($assets as $a): ?><div class="pg-asset"><b><?= pg_h($a['asset_name']); ?></b><span>Level <?= (int)$a['asset_level']; ?></span><small>+<?= pg_num($a['base_generation'] * $a['asset_level']); ?> generation · <?= pg_num($a['base_consumption'] * $a['asset_level']); ?> draw</small></div><?php endforeach; ?></div></div>
      <?php else: ?><div class="pg-empty">No power nodes are provisioned for this empire yet.</div><?php endif; ?>
    </section>
  </div>
  <?php if ($active && count($nodes) > 1): ?>
  <div class="pg-transfer"><h4>Energy Transfer</h4><p>Move stored power between owned nodes.</p><select id="pgTarget">
  <?php foreach ($nodes as $n): if ((int)$n['node_id'] === (int)$active['node_id']) continue; ?>
    <option value="<?= (int)$n['node_id']; ?>"><?= pg_h($n['node_name']); ?></option>
  <?php endforeach; ?></select><input id="pgAmount" type="number" min="1" value="1000"><a href="javascript:void(0)" onclick="sendData('powergrid','get','transfer','<?= (int)$active['node_id']; ?>',document.getElementById('pgTarget').value,document.getElementById('pgAmount').value);return false">Transfer Power</a></div>
  <?php endif; ?>
</div>
