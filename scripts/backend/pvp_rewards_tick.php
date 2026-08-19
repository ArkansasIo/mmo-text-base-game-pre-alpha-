<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../base/PvPExpansionPolicy.class.php';
require_once __DIR__ . '/../../base/PvPSeasonService.class.php';
$db = $GLOBALS['db_link'] ?? null;
if (!$db instanceof mysqli) { fwrite(STDERR, "pvp_rewards_tick: database unavailable\n"); exit(1); }
$distributed = PvPSeasonService::distributeEnded($db);
echo "pvp_rewards_tick: distributed $distributed reward placements\n";
?>
