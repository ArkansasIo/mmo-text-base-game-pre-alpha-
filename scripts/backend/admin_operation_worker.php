<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config.php';

mysqli_report(MYSQLI_REPORT_OFF);
$db = new mysqli($conf['db_server'], $conf['db_username'], $conf['db_password'], $conf['db_name']);
if ($db->connect_errno) { fwrite(STDERR, "database connection failed\n"); exit(1); }
$db->set_charset('utf8mb4');

function setSetting(mysqli $db, string $key, string $value): void {
    $stmt = $db->prepare('INSERT INTO app_settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
    if (!$stmt) throw new RuntimeException('setting prepare failed');
    $stmt->bind_param('ss', $key, $value); if (!$stmt->execute()) throw new RuntimeException('setting update failed');
}
function writeAudit(mysqli $db, int $actor, string $action, string $module, array $details): void {
    $json = json_encode($details, JSON_UNESCAPED_SLASHES);
    $ip = '127.0.0.1';
    $stmt = $db->prepare('INSERT INTO app_audit_log (uid,action_type,module_name,details_json,ip_address) VALUES (?,?,?,?,?)');
    if (!$stmt) throw new RuntimeException('audit prepare failed');
    $stmt->bind_param('issss', $actor, $action, $module, $json, $ip); if (!$stmt->execute()) throw new RuntimeException('audit insert failed');
}
function targetUids(mysqli $db, int $target): array {
    if ($target > 0) return [$target];
    $result = $db->query('SELECT uid FROM users ORDER BY uid'); $uids=[];
    while ($result && ($row=$result->fetch_assoc())) $uids[]=(int)$row['uid'];
    return $uids;
}
function recalculatePower(mysqli $db, int $target): int {
    $count=0;
    foreach (targetUids($db,$target) as $uid) {
        $stmt=$db->prepare('SELECT attack,superAttack,attackMercs,defense,superDefense,defenseMercs,covert,superCovert,anticovert,superAnticovert FROM units WHERE uid=? LIMIT 1');
        if(!$stmt) throw new RuntimeException('units prepare failed'); $stmt->bind_param('i',$uid);$stmt->execute();$u=$stmt->get_result()->fetch_assoc(); if(!$u) continue;
        $atk=(int)$u['attack']+(int)$u['superAttack']+(int)$u['attackMercs']; $def=(int)$u['defense']+(int)$u['superDefense']+(int)$u['defenseMercs']; $cov=(int)$u['covert']+(int)$u['superCovert']; $anti=(int)$u['anticovert']+(int)$u['superAnticovert']; $total=$atk+$def;
        $up=$db->prepare('INSERT INTO power (uid,overall,mil_atk,mil_def,mil_cov,mil_anti,mil_total) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE overall=VALUES(overall),mil_atk=VALUES(mil_atk),mil_def=VALUES(mil_def),mil_cov=VALUES(mil_cov),mil_anti=VALUES(mil_anti),mil_total=VALUES(mil_total)');
        if(!$up) throw new RuntimeException('power prepare failed'); $up->bind_param('iiiiiii',$uid,$total,$atk,$def,$cov,$anti,$total); if(!$up->execute()) throw new RuntimeException('power update failed'); $count++;
    }
    return $count;
}
function refreshEconomy(mysqli $db, int $target): int {
    $uids=targetUids($db,$target); $count=0;
    foreach($uids as $uid){$stmt=$db->prepare('UPDATE player_resources SET last_tick_at=NOW(),updated_at=CURRENT_TIMESTAMP WHERE uid=?');if(!$stmt)throw new RuntimeException('economy prepare failed');$stmt->bind_param('i',$uid);$stmt->execute();$count+=(int)$stmt->affected_rows;}
    setSetting($db,'economy_metrics_refreshed_at',gmdate('c')); return $count;
}
function rebuildUniverseIndex(mysqli $db): int {
    $result=$db->query('SELECT COUNT(*) AS c FROM planets');$count=$result?(int)$result->fetch_assoc()['c']:0; setSetting($db,'universe_index_version',gmdate('YmdHis'));setSetting($db,'universe_index_planet_count',(string)$count);return $count;
}
function repairCombat(mysqli $db, int $target): int { return recalculatePower($db,$target); }

$lock = $db->query("SELECT GET_LOCK('universe_admin_operation_worker',5) AS acquired");
if (!$lock || (int)$lock->fetch_assoc()['acquired'] !== 1) { fwrite(STDERR,"worker lock unavailable\n"); exit(2); }
$processed=0;
try {
    while (true) {
        $db->begin_transaction();
        $row=$db->query("SELECT job_id,admin_id,job_type,target_uid FROM admin_operation_jobs WHERE status='queued' ORDER BY job_id LIMIT 1 FOR UPDATE");
        $job=$row?$row->fetch_assoc():null;
        if(!$job){$db->commit();break;}
        $jobId=(int)$job['job_id'];$actor=(int)$job['admin_id'];$type=(string)$job['job_type'];$target=(int)($job['target_uid']??0);
        $mark=$db->prepare("UPDATE admin_operation_jobs SET status='running',started_at=NOW() WHERE job_id=?");
        if($mark){$mark->bind_param('i',$jobId);$mark->execute();}
        $db->commit();
        try {
            $db->begin_transaction();
            $affected=match($type){'recalculate_power'=>recalculatePower($db,$target),'refresh_economy_metrics'=>refreshEconomy($db,$target),'rebuild_universe_index'=>rebuildUniverseIndex($db),'repair_combat_integrity'=>repairCombat($db,$target),default=>throw new RuntimeException('unsupported operation')};
            $result=json_encode(['affected'=>$affected,'completed_by'=>'admin_operation_worker'],JSON_UNESCAPED_SLASHES);
            $done=$db->prepare("UPDATE admin_operation_jobs SET status='completed',result_json=?,completed_at=NOW() WHERE job_id=?");$done->bind_param('si',$result,$jobId);$done->execute();writeAudit($db,$actor,'complete_admin_job','operations',['job_id'=>$jobId,'job_type'=>$type,'affected'=>$affected]);$db->commit();$processed++;echo "completed job={$jobId} type={$type} affected={$affected}\n";
        } catch(Throwable $e) {
            $db->rollback();$error=$e->getMessage();$fail=$db->prepare("UPDATE admin_operation_jobs SET status='failed',result_json=?,completed_at=NOW() WHERE job_id=?");$failResult=json_encode(['error'=>$error],JSON_UNESCAPED_SLASHES);if($fail){$fail->bind_param('si',$failResult,$jobId);$fail->execute();}writeAudit($db,$actor,'fail_admin_job','operations',['job_id'=>$jobId,'job_type'=>$type,'error'=>$error]);echo "failed job={$jobId} type={$type} error={$error}\n";
        }
    }
} finally { $db->query("DO RELEASE_LOCK('universe_admin_operation_worker')"); }
echo "processed={$processed}\n";
