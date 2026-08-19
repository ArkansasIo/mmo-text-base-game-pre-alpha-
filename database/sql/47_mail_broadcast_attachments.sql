CREATE TABLE IF NOT EXISTS game_mail_broadcasts (
  broadcast_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  sender_address VARCHAR(190) NOT NULL,
  subject VARCHAR(190) NOT NULL,
  body TEXT NOT NULL,
  recipient_count INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (broadcast_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS game_email_attachments (
  attachment_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  email_id BIGINT UNSIGNED NOT NULL,
  uid INT NOT NULL,
  attachment_type ENUM('currency','item','equipment') NOT NULL,
  resource_key VARCHAR(64) NULL,
  asset_key VARCHAR(128) NULL,
  quantity BIGINT UNSIGNED NOT NULL DEFAULT 1,
  claimed_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (attachment_id),
  UNIQUE KEY uq_mail_attachment(email_id,attachment_type,resource_key,asset_key),
  KEY idx_mail_attachment_claim(uid,claimed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS player_mail_assets (
  asset_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  uid INT NOT NULL,
  asset_type ENUM('item','equipment') NOT NULL,
  asset_key VARCHAR(128) NOT NULL,
  quantity BIGINT UNSIGNED NOT NULL DEFAULT 0,
  source_email_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (asset_id),
  UNIQUE KEY uq_player_mail_asset(uid,asset_type,asset_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE game_email_messages ADD COLUMN IF NOT EXISTS broadcast_id BIGINT UNSIGNED NULL;
ALTER TABLE game_email_messages ADD COLUMN IF NOT EXISTS has_attachments TINYINT(1) NOT NULL DEFAULT 0;
