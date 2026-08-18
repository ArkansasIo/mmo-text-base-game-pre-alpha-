<?php
include('../config.php');
require_once __DIR__ . '/../base/FleetPolicy.class.php';
require_once __DIR__ . '/../base/ResearchBlueprintPolicy.class.php';
$pagegen = new page_gen();
$pagegen->round_to = 4;
$pagegen->start();
$game = new Game();
if (!$game->loggedIn || !isset($_GET['time'])) {
    header('Location: ../index.php');
    exit;
}
$db = $game->db_link; $uid = (int)$_SESSION['userid']; ResearchBlueprintPolicy::ensureTable($db);
$unlocked = []; $uq = $db->query("SELECT blueprint_key FROM player_blueprint_research WHERE uid=$uid AND status='unlocked'"); if ($uq) while ($ur = $uq->fetch_assoc()) $unlocked[(string)$ur['blueprint_key']] = true;
$filterClass = strtoupper(substr((string)($_GET['class'] ?? ''), 0, 1));
$filterRole = strtolower((string)($_GET['role'] ?? ''));
$filterTier = max(0, (int)($_GET['tier'] ?? 0));
$catalog = array_filter(FleetPolicy::BLUEPRINTS, static function (array $blueprint) use ($filterClass, $filterRole, $filterTier): bool {
    return (!$filterClass || $blueprint['class_code'] === $filterClass)
        && (!$filterRole || $blueprint['role'] === $filterRole)
        && (!$filterTier || (int)$blueprint['tier'] === $filterTier);
});
$escape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$classes = [];
$roles = [];
$tiers = [];
foreach (FleetPolicy::BLUEPRINTS as $blueprint) {
    $classes[$blueprint['class_code']] = $blueprint['class_name'];
    $roles[$blueprint['role']] = ucfirst($blueprint['role']);
    $tiers[(int)$blueprint['tier']] = true;
}
ksort($classes);
ksort($tiers);
echo '<div class="comm-shell blueprint-catalog"><div class="page-hub-head"><span class="rts-kicker">UNIVERSE CIVILIZATION // SHIP DESIGN BUREAU</span><h3>90-Blueprint Fleet Catalog</h3><p>Review hull classes, combat profiles, fitting requirements, and industrial costs before committing a shipyard queue.</p></div>';
echo '<section class="comm-card"><h4>Catalog Filters</h4><form method="get" action="/modules/blueprints.php" class="inline-form"><input type="hidden" name="time" value="'.time().'"/><label>Class <select name="class"><option value="">All A-Z Classes</option>';
foreach ($classes as $code => $name) echo '<option value="'.$escape($code).'"'.($filterClass === $code ? ' selected' : '').'>'.$escape($code).' — '.$escape($name).'</option>';
echo '</select></label><label>Role <select name="role"><option value="">All Roles</option>';
foreach ($roles as $key => $name) echo '<option value="'.$escape($key).'"'.($filterRole === $key ? ' selected' : '').'>'.$escape($name).'</option>';
echo '</select></label><label>Tier <select name="tier"><option value="0">All Tiers</option>';
foreach ($tiers as $tier => $_) echo '<option value="'.(int)$tier.'"'.($filterTier === $tier ? ' selected' : '').'>Tier '.(int)$tier.'</option>';
echo '</select></label><button class="comm-btn">Filter Catalog</button><a class="comm-btn" href="/modules/blueprints.php?time='.time().'">Reset</a></form></section>';
echo '<section class="comm-card"><h4>Blueprint Index <span class="catalog-count">'.count($catalog).' / 90 designs</span></h4><div class="admin-table-wrap"><table><tr><th>Blueprint</th><th>Class</th><th>Role</th><th>Tier</th><th>Research</th><th>Attack</th><th>Defense</th><th>Hull</th><th>Shield</th><th>Speed</th><th>Cargo</th><th>Fitting</th><th>Industrial Cost</th></tr>';
foreach ($catalog as $key => $blueprint) {
    echo '<tr><td><strong>'.$escape($blueprint['name']).'</strong><br><code>'.$escape($key).'</code><br><small>'.$escape($blueprint['description']).'</small></td><td>'.$escape($blueprint['class_code'].' — '.$blueprint['class_name']).'</td><td>'.$escape(ucfirst($blueprint['role'])).'</td><td>T'.(int)$blueprint['tier'].'</td><td>'.(isset($unlocked[$key])?'<span class="research-ok">Unlocked</span>':'Locked · T'.(int)$blueprint['tier']).'</td><td>'.number_format((int)$blueprint['attack']).'</td><td>'.number_format((int)$blueprint['defense']).'</td><td>'.number_format((int)$blueprint['hull']).'</td><td>'.number_format((int)$blueprint['shield']).'</td><td>'.number_format((int)$blueprint['speed']).'</td><td>'.number_format((int)$blueprint['cargo']).'</td><td>H '.(int)$blueprint['high_slots'].' / M '.(int)$blueprint['medium_slots'].' / L '.(int)$blueprint['low_slots'].'<br>PG '.number_format((int)$blueprint['power_grid']).'<br>CPU '.number_format((int)$blueprint['sensor']).'<br>Cap '.number_format((int)$blueprint['capacitor']).'</td><td>M '.number_format((int)$blueprint['metal']).'<br>C '.number_format((int)$blueprint['crystal']).'<br>E '.number_format((int)$blueprint['energy']).'<br>'.number_format((int)$blueprint['build_minutes']).' min</td></tr>';
}
echo '</table></div></section><section class="comm-card"><h4>Module Fitting Reference</h4><p><strong>High</strong> slots accept weapon and siege modules. <strong>Medium</strong> slots accept shield, electronic warfare, and sensor modules. <strong>Low</strong> slots accept armor, cargo, and repair modules. Each hull also limits power grid, CPU, and capacitor usage; fittings that exceed any restriction are rejected server-side.</p></section><section class="comm-card"><h4>Secondary Systems Reference</h4><p><strong>Armor</strong> measures hull mitigation; <strong>capacitor</strong> supports active systems; <strong>signature</strong> affects detection exposure; <strong>warp</strong> controls strategic travel; <strong>evasion</strong> supports interception survival; <strong>drone bandwidth</strong> enables carrier and command auxiliaries; and <strong>salvage</strong> improves recovery after fleet actions.</p></section></div>';
$pagegen->stop();
print('page generation time: '.$pagegen->gen());
?>
