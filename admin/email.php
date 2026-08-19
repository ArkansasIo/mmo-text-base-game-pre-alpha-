<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../base/AdminAuth.class.php';
require_once __DIR__ . '/../base/GameEmailPolicy.class.php';
$s = new Game();
$auth = new AdminAuth($s->db_link);
if (!$auth->isAuthenticated()) { header('Location: /'); exit; }
$db = $s->db_link;
$csrf = $_SESSION['admin_email_csrf'] ?? bin2hex(random_bytes(24));
$_SESSION['admin_email_csrf'] = $csrf;
$message = ''; $error = '';
$db->query("CREATE TABLE IF NOT EXISTS game_email_messages (email_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,from_uid INT NULL,from_address VARCHAR(190) NOT NULL,to_uid INT NULL,to_address VARCHAR(190) NOT NULL,subject VARCHAR(190) NOT NULL,body TEXT NOT NULL,email_type VARCHAR(16) NOT NULL DEFAULT 'system',is_read TINYINT(1) NOT NULL DEFAULT 0,is_deleted TINYINT(1) NOT NULL DEFAULT 0,delivery_status VARCHAR(16) NOT NULL DEFAULT 'queued',created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,sent_at DATETIME NULL,KEY idx_game_email_recipient(to_uid,is_deleted,created_at),KEY idx_game_email_queue(delivery_status,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) $error = 'Security validation failed.';
    elseif (!$auth->isAtLeast('operator')) $error = 'Operator privileges are required.';
    else {
        $uid = (int)($_POST['to_uid'] ?? 0);
        $subject = GameEmailPolicy::cleanSubject((string)($_POST['subject'] ?? ''));
        $body = GameEmailPolicy::cleanBody((string)($_POST['body'] ?? ''));
        $user = $db->query("SELECT uname,email FROM users WHERE uid=$uid LIMIT 1");
        $recipient = $user ? $user->fetch_assoc() : null;
        $root = (string)($_POST['from_address'] ?? GameEmailPolicy::ROOT_ADDRESS);
        if (!$recipient || !GameEmailPolicy::validAddress($root) || $subject === '' || $body === '') {
            $error = 'Provide a valid player UID, root sender address, subject, and body.';
        } else {
            $to = GameEmailPolicy::validAddress((string)$recipient['email']) ? $recipient['email'] : ('player' . $uid . '@universecivilization.game');
            $stmt = $db->prepare("INSERT INTO game_email_messages(from_uid,from_address,to_uid,to_address,subject,body,email_type,delivery_status) VALUES(NULL,?,?,?,?,?,'system','queued')");
            if ($stmt) {
                $stmt->bind_param('sisss', $root, $uid, $to, $subject, $body);
                if ($stmt->execute()) { $auth->audit('send_root_game_email', 'email_network', ['to_uid' => $uid, 'subject' => $subject]); $message = 'Root email queued for in-game delivery.'; }
                else $error = 'Could not queue root email.';
            } else $error = 'Email service unavailable.';
        }
    }
}
$rows = $db->query("SELECT e.email_id,e.to_uid,e.to_address,e.subject,e.delivery_status,e.created_at,u.uname FROM game_email_messages e LEFT JOIN users u ON u.uid=e.to_uid WHERE e.email_type='system' ORDER BY e.email_id DESC LIMIT 50");
function email_h(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
?><!doctype html><html><head><meta charset="utf-8"><title>Root Email Network</title><link rel="stylesheet" href="/main.css"></head><body><main class="admin-shell"><h1>Root Email Network</h1><p>Authenticated administrator: <?=email_h((string)$auth->admin['username'])?> · Sender: <?=email_h(GameEmailPolicy::ROOT_ADDRESS)?></p><?php if($message):?><div class="comm-alert"><?=email_h($message)?></div><?php endif;?><?php if($error):?><div class="comm-alert comm-error"><?=email_h($error)?></div><?php endif;?><section class="admin-card"><h2>Send system email</h2><form method="post" class="admin-form-grid"><input type="hidden" name="csrf" value="<?=email_h($csrf)?>"><label>Recipient UID<input type="number" name="to_uid" min="1" required></label><label>From address<input type="email" name="from_address" value="<?=email_h(GameEmailPolicy::ROOT_ADDRESS)?>" required></label><label>Subject<input name="subject" maxlength="190" required></label><label>Message<textarea name="body" rows="10" maxlength="20000" required></textarea></label><button>Queue Root Email</button></form></section><section class="admin-card"><h2>System email queue</h2><div class="admin-table-wrap"><table><tr><th>ID</th><th>Recipient</th><th>Subject</th><th>Status</th><th>Created</th></tr><?php if($rows) while($r=$rows->fetch_assoc()):?><tr><td><?= (int)$r['email_id']?></td><td><?=email_h((string)($r['uname'] ?: $r['to_address']))?></td><td><?=email_h($r['subject'])?></td><td><?=email_h($r['delivery_status'])?></td><td><?=email_h($r['created_at'])?></td></tr><?php endwhile;?></table></div></section><p><a href="/admin/">Return to Admin Control Plane</a></p></main></body></html>
