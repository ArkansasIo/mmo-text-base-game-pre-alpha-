-- Guild market and trade route layer
ALTER TABLE guild_territories
  ADD COLUMN IF NOT EXISTS stock_metal BIGINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS stock_crystal BIGINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS stock_energy BIGINT UNSIGNED NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS guild_market_orders (
  order_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  guild_id INT NOT NULL,
  territory_id BIGINT UNSIGNED NOT NULL,
  created_by INT NOT NULL,
  resource_type ENUM('metal','crystal','energy') NOT NULL,
  quantity BIGINT UNSIGNED NOT NULL,
  remaining_quantity BIGINT UNSIGNED NOT NULL,
  unit_price BIGINT UNSIGNED NOT NULL,
  status ENUM('open','filled','cancelled','expired') NOT NULL DEFAULT 'open',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NOT NULL,
  PRIMARY KEY (order_id),
  KEY idx_market_guild_status (guild_id,status,created_at),
  KEY idx_market_territory (territory_id,status),
  CONSTRAINT fk_market_guild FOREIGN KEY (guild_id) REFERENCES guilds(guild_id) ON DELETE CASCADE,
  CONSTRAINT fk_market_territory FOREIGN KEY (territory_id) REFERENCES guild_territories(territory_id) ON DELETE CASCADE,
  CONSTRAINT fk_market_user FOREIGN KEY (created_by) REFERENCES users(uid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS guild_trade_routes (
  route_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  guild_id INT NOT NULL,
  origin_territory_id BIGINT UNSIGNED NOT NULL,
  destination_territory_id BIGINT UNSIGNED NOT NULL,
  created_by INT NOT NULL,
  resource_type ENUM('metal','crystal','energy') NOT NULL,
  quantity BIGINT UNSIGNED NOT NULL,
  status ENUM('enroute','delivered','cancelled') NOT NULL DEFAULT 'enroute',
  depart_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  arrive_at DATETIME NOT NULL,
  delivered_at DATETIME NULL,
  PRIMARY KEY (route_id),
  KEY idx_trade_arrival (status,arrive_at),
  KEY idx_trade_guild (guild_id,status),
  CONSTRAINT fk_trade_guild FOREIGN KEY (guild_id) REFERENCES guilds(guild_id) ON DELETE CASCADE,
  CONSTRAINT fk_trade_origin FOREIGN KEY (origin_territory_id) REFERENCES guild_territories(territory_id) ON DELETE CASCADE,
  CONSTRAINT fk_trade_destination FOREIGN KEY (destination_territory_id) REFERENCES guild_territories(territory_id) ON DELETE CASCADE,
  CONSTRAINT fk_trade_user FOREIGN KEY (created_by) REFERENCES users(uid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
