-- Player crafting, equipment upgrades, and guild event notifications
CREATE TABLE IF NOT EXISTS player_equipment (
  uid INT NOT NULL,
  equipment_key VARCHAR(32) NOT NULL,
  level TINYINT UNSIGNED NOT NULL DEFAULT 0,
  quantity INT UNSIGNED NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY(uid,equipment_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS unit_equipment_loadouts (
  loadout_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  uid INT NOT NULL,
  unit_id BIGINT UNSIGNED NOT NULL,
  equipment_json TEXT NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY(loadout_id), UNIQUE KEY uq_unit_loadout(uid,unit_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS crafting_transactions (
  transaction_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  uid INT NOT NULL,
  equipment_key VARCHAR(32) NOT NULL,
  level TINYINT UNSIGNED NOT NULL,
  metal_cost BIGINT UNSIGNED NOT NULL,
  crystal_cost BIGINT UNSIGNED NOT NULL,
  energy_cost BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(transaction_id), KEY idx_craft_uid(uid,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS guild_notifications (
  notification_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  guild_id INT NOT NULL,
  territory_id BIGINT UNSIGNED NULL,
  event_id BIGINT UNSIGNED NULL,
  notification_type VARCHAR(32) NOT NULL,
  title VARCHAR(140) NOT NULL,
  body VARCHAR(500) NOT NULL,
  status ENUM('unread','read') NOT NULL DEFAULT 'unread',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(notification_id), KEY idx_notify_guild(guild_id,status,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS guild_webhooks (
  webhook_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  guild_id INT NOT NULL,
  endpoint_url VARCHAR(500) NOT NULL,
  secret_hash CHAR(64) NULL,
  enabled TINYINT(1) NOT NULL DEFAULT 1,
  failure_count INT UNSIGNED NOT NULL DEFAULT 0,
  last_attempt_at DATETIME NULL,
  last_success_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(webhook_id), KEY idx_webhook_guild(guild_id,enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS guild_webhook_deliveries (
  delivery_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  webhook_id BIGINT UNSIGNED NOT NULL,
  notification_id BIGINT UNSIGNED NOT NULL,
  status ENUM('pending','delivered','failed') NOT NULL DEFAULT 'pending',
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  next_attempt_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  delivered_at DATETIME NULL,
  last_error VARCHAR(255) NULL,
  PRIMARY KEY(delivery_id), UNIQUE KEY uq_webhook_notification(webhook_id,notification_id), KEY idx_delivery_retry(status,next_attempt_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
