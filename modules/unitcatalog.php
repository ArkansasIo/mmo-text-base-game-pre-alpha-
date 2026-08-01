<?php
include_once("../config.php");

$pagegen = new page_gen();
$pagegen->round_to = 4;
$pagegen->start();

$s = new Game();
if (!$s->loggedIn) { header("Location: ../index.php"); exit; }

$db    = $s->db_link;
$atype = $_GET['atype'] ?? 'military';
if (!in_array($atype, ['military','civilian','government'])) { $atype = 'military'; }

$detailId = (int)($_GET['id'] ?? 0);

// Tier color map
function tierColor(int $tier): string {
    $colors = [1=>'#888',2=>'#7a9',3=>'#6af',4=>'#4cf',5=>'#fc0',
               6=>'#f90',7=>'#f44',8=>'#c4f',9=>'#f4f',10=>'#fff'];
    return $colors[$tier] ?? '#aaa';
}

function catIcon(string $cat): string {
    return ['military'=>'&#9876;','civilian'=>'&#9955;','government'=>'&#9817;'][$cat] ?? '&#9679;';
}
?>
<style>
.uc-shell { font-family: inherit; }
.uc-tabs  { display:flex; gap:4px; margin-bottom:14px; border-bottom:2px solid #444; padding-bottom:6px; }
.uc-tab   { padding:6px 20px; background:#222; color:#aaa; border-radius:4px 4px 0 0; text-decoration:none; font-size:.9em; cursor:pointer; }
.uc-tab.active, .uc-tab:hover { background:#336; color:#adf; }
.uc-grid  { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:10px; }
.uc-card  { background:#111; border:1px solid #333; border-radius:4px; padding:12px; cursor:pointer; transition:border-color .15s; }
.uc-card:hover { border-color:#55a; background:#16162a; }
.uc-code  { font-size:.75em; color:#666; font-family:monospace; }
.uc-name  { font-size:1.05em; font-weight:bold; margin:4px 0 2px; }
.uc-rank  { font-size:.82em; color:#aaa; }
.uc-class { font-size:.78em; color:#888; margin-top:4px; }
.uc-tier-badge { display:inline-block; padding:1px 7px; border-radius:10px; font-size:.75em; font-weight:bold; margin-left:6px; color:#000; }
.uc-stats { display:flex; gap:8px; margin-top:8px; font-size:.8em; flex-wrap:wrap; }
.uc-stat  { background:#1a1a2e; border:1px solid #334; border-radius:3px; padding:2px 7px; }
/* Detail modal */
#uc-modal { display:none; position:fixed; top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.8);z-index:9999;overflow:auto; }
.uc-modal-box { background:#0d0d1a; color:#ccc; border:1px solid #446; border-radius:6px; max-width:680px; margin:40px auto; padding:28px; position:relative; }
.uc-modal-box h2 { margin-top:0; color:#adf; }
.uc-modal-close { position:absolute;top:12px;right:16px;background:none;border:none;color:#888;font-size:20px;cursor:pointer; }
.uc-detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin:14px 0; font-size:.88em; }
.uc-detail-row  { background:#111; border:1px solid #2a2a3e; border-radius:3px; padding:6px 10px; }
.uc-detail-row label { color:#668; display:block; font-size:.8em; margin-bottom:2px; }
.uc-ability { background:#1a1a2e; border:1px solid #44c; border-radius:4px; padding:10px 14px; margin:12px 0; font-size:.88em; }
.uc-ability strong { color:#8cf; }
.uc-lore { background:#0a0a14; border-left:3px solid #446; padding:12px 16px; font-size:.85em; line-height:1.6; color:#aaa; font-style:italic; margin-top:12px; }
.uc-nav { margin-bottom:12px; display:flex; align-items:center; justify-content:space-between; }
</style>

<div class="uc-shell">

<div class="uc-nav">
  <strong>&#9741; Unit Catalog</strong>
  <span style="font-size:.85em;color:#668">90 units across 3 categories &mdash; Click any card to view full details</span>
</div>

<div class="uc-tabs">
  <?php foreach(['military'=>'&#9876; Military','civilian'=>'&#9955; Civilian','government'=>'&#9817; Government'] as $cat=>$label): ?>
  <a class="uc-tab<?= $atype===$cat?' active':'' ?>" href="javascript:void(0)"
     onclick="sendData('unitcatalog','get','0','<?= $cat ?>')"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<?php
// Fetch all units in category
$stmt = $db->prepare("SELECT * FROM unit_catalog WHERE category=? ORDER BY tier ASC, rank_level ASC");
$stmt->bind_param("s", $atype);
$stmt->execute();
$units = $stmt->get_result();

echo '<div class="uc-grid">';
$currentTier = 0;
$rows = [];
while ($u = $units->fetch_object()) { $rows[] = $u; }

foreach ($rows as $u):
    if ($u->tier !== $currentTier) {
        if ($currentTier > 0) { echo '</div><div class="uc-grid">'; }
        $tc = tierColor($u->tier);
        echo '<div style="grid-column:1/-1;margin-top:10px;border-bottom:1px solid #333;padding-bottom:4px;">';
        echo '<span style="color:'.$tc.';font-weight:bold;">&#9670; Tier '.$u->tier.'</span> ';
        echo '<span style="color:#555;font-size:.82em;">'.$u->unit_subtype.'</span>';
        echo '</div>';
        $currentTier = $u->tier;
    }
    $tc = tierColor($u->tier);
?>
<div class="uc-card" onclick="showUnit(<?= $u->unit_id ?>)">
  <div class="uc-code"><?= htmlspecialchars($u->unit_code) ?>
    <span class="uc-tier-badge" style="background:<?= $tc ?>">T<?= $u->tier ?></span>
  </div>
  <div class="uc-name"><?= htmlspecialchars($u->unit_name) ?></div>
  <div class="uc-rank"><?= htmlspecialchars($u->rank_title) ?> (<?= htmlspecialchars($u->rank_abbrev) ?>)</div>
  <div class="uc-class">Class <?= htmlspecialchars($u->class_letter) ?>-<?= htmlspecialchars($u->class_subclass) ?> &bull; <?= htmlspecialchars($u->unit_type) ?></div>
  <div class="uc-stats">
    <?php if ($u->attack_power  > 0): ?><span class="uc-stat" title="Attack">&#9876; <?= number_format($u->attack_power) ?></span><?php endif; ?>
    <?php if ($u->defense_power > 0): ?><span class="uc-stat" title="Defense">&#128737; <?= number_format($u->defense_power) ?></span><?php endif; ?>
    <?php if ($u->covert_power  > 0): ?><span class="uc-stat" title="Covert">&#128373; <?= number_format($u->covert_power) ?></span><?php endif; ?>
    <?php if ($u->income_gen    > 0): ?><span class="uc-stat" title="Income">&#9670; <?= number_format($u->income_gen) ?></span><?php endif; ?>
  </div>
</div>
<?php endforeach; ?>
</div>

</div>

<!-- Detail modal -->
<div id="uc-modal">
  <div class="uc-modal-box">
    <button class="uc-modal-close" onclick="document.getElementById('uc-modal').style.display='none'">&#x2715;</button>
    <div id="uc-modal-body"></div>
  </div>
</div>

<script>
var ucUnits = <?php
$allQ = $db->prepare("SELECT * FROM unit_catalog ORDER BY unit_id ASC");
$allQ->execute();
$allRows = $allQ->get_result()->fetch_all(MYSQLI_ASSOC);
echo json_encode(array_column($allRows, null, 'unit_id'));
?>;

function showUnit(id) {
    var u = ucUnits[id];
    if (!u) return;
    var tc = tierColors[u.tier] || '#aaa';
    var html = '<h2>' + esc(u.unit_name) + '</h2>';
    html += '<div style="font-size:.85em;color:#668;margin-bottom:12px">' + esc(u.full_title) + '</div>';
    html += '<div class="uc-detail-grid">';
    html += drow('Unit Code',      u.unit_code);
    html += drow('Category',       u.category.charAt(0).toUpperCase()+u.category.slice(1));
    html += drow('Tier',           '<span style="color:'+tc+';font-weight:bold">Tier ' + u.tier + '</span>');
    html += drow('Rank Level',     u.rank_level + ' of 30');
    html += drow('Rank Title',     u.rank_title + ' (' + u.rank_abbrev + ')');
    html += drow('Size Class',     'Class ' + u.class_letter + '-' + u.class_subclass);
    html += drow('Unit Type',      u.unit_type);
    html += drow('Subtype',        u.unit_subtype);
    html += drow('Training Cost',  num(u.training_cost) + ' Naq');
    html += drow('Upkeep/Turn',    num(u.upkeep_per_turn) + ' Naq');
    html += drow('Attack Power',   u.attack_power > 0  ? num(u.attack_power)  : '—');
    html += drow('Defense Power',  u.defense_power > 0 ? num(u.defense_power) : '—');
    html += drow('Covert Power',   u.covert_power > 0  ? num(u.covert_power)  : '—');
    html += drow('Income/Turn',    u.income_gen > 0    ? num(u.income_gen) + ' Naq' : '—');
    html += '</div>';
    html += '<div class="uc-ability"><strong>&#9670; Special Ability:</strong> ' + esc(u.special_ability) + '</div>';
    html += '<div class="uc-lore">' + esc(u.description) + '</div>';
    document.getElementById('uc-modal-body').innerHTML = html;
    document.getElementById('uc-modal').style.display = 'block';
}
var tierColors={1:'#888',2:'#7a9',3:'#6af',4:'#4cf',5:'#fc0',6:'#f90',7:'#f44',8:'#c4f',9:'#f4f',10:'#fff'};
function drow(l,v){return '<div class="uc-detail-row"><label>'+l+'</label>'+v+'</div>';}
function esc(s){var d=document.createElement('div');d.appendChild(document.createTextNode(String(s||'')));return d.innerHTML;}
function num(n){return Number(n).toLocaleString();}
document.getElementById('uc-modal').addEventListener('click',function(e){if(e.target===this)this.style.display='none';});
</script>
<?php
echo "Query Count: " . $s->queryCount . "<br>";
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());
?>
