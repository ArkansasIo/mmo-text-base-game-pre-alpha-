<?php
if (PHP_SAPI !== 'cli') { fwrite(STDERR, "CLI only\n"); exit(1); }
if (!in_array(strtolower((string)(getenv('APP_ENV') ?: 'local')), ['local','development'], true) && getenv('ALLOW_EMAIL_TEST') !== '1') { fwrite(STDERR, "Set APP_ENV=local/development or ALLOW_EMAIL_TEST=1.\n"); exit(1); }
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../base/GameEmailPolicy.class.php';
$db = new mysqli($conf['db_server'],$conf['db_username'],$conf['db_password'],$conf['db_name']);
if ($db->connect_error) { fwrite(STDERR, "Database connection failed.\n"); exit(1); }
$uid=(int)(getenv('SGW_EMAIL_TEST_UID')?:0);if($uid<=0){$q=$db->query("SELECT uid FROM users ORDER BY uid ASC LIMIT 1");$uid=$q?(int)($q->fetch_assoc()['uid']??0):0;}
$player=$db->query("SELECT uname,email FROM users WHERE uid=$uid LIMIT 1");$row=$player?$player->fetch_assoc():null;if(!$row){fwrite(STDERR,"Player UID not found. Set SGW_EMAIL_TEST_UID.\n");exit(1);}
$db->query("CREATE TABLE IF NOT EXISTS game_email_messages (email_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,from_uid INT NULL,from_address VARCHAR(190) NOT NULL,to_uid INT NULL,to_address VARCHAR(190) NOT NULL,subject VARCHAR(190) NOT NULL,body TEXT NOT NULL,email_type VARCHAR(16) NOT NULL DEFAULT 'system',is_read TINYINT(1) NOT NULL DEFAULT 0,is_deleted TINYINT(1) NOT NULL DEFAULT 0,delivery_status VARCHAR(16) NOT NULL DEFAULT 'queued',created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,sent_at DATETIME NULL,KEY idx_game_email_recipient(to_uid,is_deleted,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$to=filter_var((string)$row['email'],FILTER_VALIDATE_EMAIL)?$row['email']:'player'.$uid.'@universecivilization.game';$from=GameEmailPolicy::ROOT_ADDRESS;$subject='Root Email Integration Test';$body='This is a local integration test from the root administrator email system.';$stmt=$db->prepare("INSERT INTO game_email_messages(from_uid,from_address,to_uid,to_address,subject,body,email_type,delivery_status) VALUES(NULL,?,?,?,?,?,'system','queued')");$stmt->bind_param('sisss',$from,$uid,$to,$subject,$body);if(!$stmt->execute()){fwrite(STDERR,"Could not queue test message.\n");exit(1);}echo "Queued root email test email_id={$db->insert_id} to UID {$uid} ({$row['uname']}).\n";
?>
