CREATE TABLE IF NOT EXISTS player_notifications (
  notification_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  uid INT NOT NULL,
  category VARCHAR(40) NOT NULL,
  title VARCHAR(160) NOT NULL,
  body VARCHAR(500) NOT NULL,
  data_json LONGTEXT NULL,
  dedupe_key VARCHAR(160) NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (notification_id),
  UNIQUE KEY uq_notification_dedupe (uid, dedupe_key),
  KEY idx_notification_feed (uid, is_read, created_at),
  CONSTRAINT fk_player_notification_uid FOREIGN KEY (uid) REFERENCES users(uid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
