ALTER TABLE hyperspace_systems
  ADD COLUMN IF NOT EXISTS wormhole_scan_cooldown_at DATETIME NULL;

CREATE TABLE IF NOT EXISTS wormhole_signatures (
  signature_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  uid INT NOT NULL,
  signature_code VARCHAR(24) NOT NULL,
  wormhole_class ENUM('stable','unstable','ancient','null','quantum') NOT NULL,
  scan_difficulty INT UNSIGNED NOT NULL DEFAULT 25,
  stability INT UNSIGNED NOT NULL DEFAULT 50,
  status ENUM('detected','explored','expired') NOT NULL DEFAULT 'detected',
  discovered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NOT NULL,
  PRIMARY KEY (signature_id),
  UNIQUE KEY uq_wormhole_signature(uid,signature_code),
  KEY idx_wormhole_scan_queue(uid,status,expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS wormhole_expeditions (
  expedition_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  signature_id BIGINT UNSIGNED NOT NULL,
  uid INT NOT NULL,
  scan_strength INT UNSIGNED NOT NULL,
  dark_matter_cost BIGINT UNSIGNED NOT NULL,
  status ENUM('enroute','resolved','failed') NOT NULL DEFAULT 'enroute',
  dispatched_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  resolves_at DATETIME NOT NULL,
  outcome VARCHAR(40) NULL,
  reward_dark_matter BIGINT UNSIGNED NOT NULL DEFAULT 0,
  reward_exotic_matter BIGINT UNSIGNED NOT NULL DEFAULT 0,
  reward_tritanium BIGINT UNSIGNED NOT NULL DEFAULT 0,
  resolved_at DATETIME NULL,
  PRIMARY KEY (expedition_id),
  UNIQUE KEY uq_wormhole_active(signature_id,status),
  KEY idx_wormhole_due(status,resolves_at),
  KEY idx_wormhole_player(uid,status,dispatched_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
