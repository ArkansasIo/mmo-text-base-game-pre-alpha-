-- In-game email subsystem and optional external delivery queue.
CREATE TABLE IF NOT EXISTS game_email_messages (
  email_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  from_uid INT NULL,
  from_address VARCHAR(190) NOT NULL,
  to_uid INT NULL,
  to_address VARCHAR(190) NOT NULL,
  subject VARCHAR(190) NOT NULL,
  body TEXT NOT NULL,
  email_type ENUM('system','admin','player') NOT NULL DEFAULT 'player',
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  is_deleted TINYINT(1) NOT NULL DEFAULT 0,
  delivery_status ENUM('inbox','queued','sent','failed') NOT NULL DEFAULT 'inbox',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  sent_at DATETIME NULL,
  KEY idx_game_email_recipient(to_uid,is_deleted,created_at), KEY idx_game_email_queue(delivery_status,created_at), KEY idx_game_email_sender(from_uid,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS game_email_delivery_log (
  delivery_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  email_id BIGINT UNSIGNED NOT NULL,
  transport VARCHAR(32) NOT NULL,
  status ENUM('sent','failed','skipped') NOT NULL,
  response_text VARCHAR(500) NOT NULL DEFAULT '',
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_game_email_delivery(email_id,attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO app_settings(setting_key,setting_value) VALUES ('game_root_email','root@universecivilization.game'),('game_mail_transport','log') ON DUPLICATE KEY UPDATE setting_key=VALUES(setting_key);
