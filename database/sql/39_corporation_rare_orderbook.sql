CREATE TABLE IF NOT EXISTS corporation_blueprint_copies (
  corporation_id INT UNSIGNED NOT NULL,
  blueprint_key VARCHAR(64) NOT NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (corporation_id, blueprint_key),
  KEY idx_corp_blueprint_stock (blueprint_key, quantity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS corporation_module_inventory (
  corporation_id INT UNSIGNED NOT NULL,
  equipment_key VARCHAR(64) NOT NULL,
  level INT UNSIGNED NOT NULL DEFAULT 0,
  quantity INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (corporation_id, equipment_key, level),
  KEY idx_corp_module_stock (equipment_key, level, quantity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS corporation_market_orders (
  order_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  corporation_id INT UNSIGNED NOT NULL,
  created_by INT NOT NULL,
  side ENUM('ask','bid') NOT NULL,
  item_type ENUM('module','blueprint') NOT NULL,
  item_key VARCHAR(64) NOT NULL,
  item_level INT UNSIGNED NOT NULL DEFAULT 0,
  quantity INT UNSIGNED NOT NULL,
  remaining_quantity INT UNSIGNED NOT NULL,
  price_dark_matter BIGINT UNSIGNED NOT NULL,
  escrow_dark_matter BIGINT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('open','filled','cancelled') NOT NULL DEFAULT 'open',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  closed_at DATETIME NULL,
  PRIMARY KEY (order_id),
  KEY idx_orderbook_match (item_type,item_key,item_level,side,status,price_dark_matter,created_at),
  KEY idx_corp_orders (corporation_id,status,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS corporation_market_trades (
  trade_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  ask_order_id BIGINT UNSIGNED NOT NULL,
  bid_order_id BIGINT UNSIGNED NOT NULL,
  seller_corporation_id INT UNSIGNED NOT NULL,
  buyer_corporation_id INT UNSIGNED NOT NULL,
  item_type ENUM('module','blueprint') NOT NULL,
  item_key VARCHAR(64) NOT NULL,
  item_level INT UNSIGNED NOT NULL DEFAULT 0,
  quantity INT UNSIGNED NOT NULL,
  price_dark_matter BIGINT UNSIGNED NOT NULL,
  fee_dark_matter BIGINT UNSIGNED NOT NULL DEFAULT 0,
  traded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (trade_id),
  KEY idx_corp_trade_history (seller_corporation_id,traded_at),
  KEY idx_corp_trade_buyer (buyer_corporation_id,traded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
