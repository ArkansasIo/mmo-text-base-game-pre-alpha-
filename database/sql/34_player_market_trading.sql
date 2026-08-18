CREATE TABLE IF NOT EXISTS player_blueprints (
  uid INT NOT NULL,
  blueprint_key VARCHAR(64) NOT NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 0,
  acquired_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (uid, blueprint_key),
  KEY idx_blueprint_market (blueprint_key, quantity),
  CONSTRAINT fk_player_blueprint_uid FOREIGN KEY (uid) REFERENCES users(uid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS player_market_listings (
  listing_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  seller_uid INT NOT NULL,
  item_type ENUM('blueprint','module') NOT NULL,
  item_key VARCHAR(64) NOT NULL,
  item_level TINYINT UNSIGNED NOT NULL DEFAULT 0,
  quantity INT UNSIGNED NOT NULL,
  unit_price BIGINT UNSIGNED NOT NULL,
  status ENUM('active','sold','cancelled') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  closed_at DATETIME NULL,
  PRIMARY KEY (listing_id),
  KEY idx_market_active (status, item_type, item_key, unit_price, created_at),
  KEY idx_market_seller (seller_uid, status, created_at),
  CONSTRAINT fk_market_listing_seller FOREIGN KEY (seller_uid) REFERENCES users(uid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS player_trade_offers (
  trade_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  proposer_uid INT NOT NULL,
  recipient_uid INT NOT NULL,
  offered_type ENUM('blueprint','module') NOT NULL,
  offered_key VARCHAR(64) NOT NULL,
  offered_level TINYINT UNSIGNED NOT NULL DEFAULT 0,
  offered_quantity INT UNSIGNED NOT NULL,
  requested_type ENUM('blueprint','module') NOT NULL,
  requested_key VARCHAR(64) NOT NULL,
  requested_level TINYINT UNSIGNED NOT NULL DEFAULT 0,
  requested_quantity INT UNSIGNED NOT NULL,
  offered_credits BIGINT UNSIGNED NOT NULL DEFAULT 0,
  requested_credits BIGINT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('pending','accepted','rejected','cancelled','expired') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  resolved_at DATETIME NULL,
  PRIMARY KEY (trade_id),
  KEY idx_trade_recipient (recipient_uid, status, created_at),
  KEY idx_trade_proposer (proposer_uid, status, created_at),
  CONSTRAINT fk_trade_proposer FOREIGN KEY (proposer_uid) REFERENCES users(uid) ON DELETE CASCADE,
  CONSTRAINT fk_trade_recipient FOREIGN KEY (recipient_uid) REFERENCES users(uid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO player_blueprints(uid, blueprint_key, quantity)
SELECT uid, 'scout', 1 FROM users;
