-- Guild territory and strategic influence layer
CREATE TABLE IF NOT EXISTS guild_territories (
  territory_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  guild_id INT NOT NULL,
  sector_code VARCHAR(32) NOT NULL,
  control_points INT UNSIGNED NOT NULL DEFAULT 100,
  status ENUM('claimed','contested','lost') NOT NULL DEFAULT 'claimed',
  claimed_by INT NOT NULL,
  claimed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (territory_id),
  UNIQUE KEY uq_territory_sector (sector_code),
  KEY idx_territory_guild (guild_id, status),
  CONSTRAINT fk_territory_guild FOREIGN KEY (guild_id) REFERENCES guilds(guild_id) ON DELETE CASCADE,
  CONSTRAINT fk_territory_user FOREIGN KEY (claimed_by) REFERENCES users(uid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
