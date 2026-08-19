-- Ranked PvP seasons and rating history.
CREATE TABLE IF NOT EXISTS pvp_seasons (
  season_code VARCHAR(32) NOT NULL PRIMARY KEY,
  title VARCHAR(120) NOT NULL,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NOT NULL,
  status ENUM('scheduled','active','ended') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO pvp_seasons(season_code,title,starts_at,ends_at,status) VALUES ('S1-2026','Season 1: Frontier Ascension','2026-01-01 00:00:00','2026-12-31 23:59:59','active');
CREATE TABLE IF NOT EXISTS pvp_rankings (
  season_code VARCHAR(32) NOT NULL,
  uid INT NOT NULL,
  rating INT NOT NULL DEFAULT 1000,
  wins INT UNSIGNED NOT NULL DEFAULT 0,
  losses INT UNSIGNED NOT NULL DEFAULT 0,
  draws INT UNSIGNED NOT NULL DEFAULT 0,
  points_for BIGINT UNSIGNED NOT NULL DEFAULT 0,
  points_against BIGINT UNSIGNED NOT NULL DEFAULT 0,
  last_battle_at DATETIME NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY(season_code,uid), KEY idx_pvp_rankings_board(season_code,rating,wins,uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS pvp_rating_history (
  history_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  season_code VARCHAR(32) NOT NULL,
  battle_id BIGINT UNSIGNED NOT NULL,
  uid INT NOT NULL,
  result ENUM('win','loss','draw') NOT NULL,
  rating_before INT NOT NULL,
  rating_delta INT NOT NULL,
  rating_after INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pvp_rating_battle_player(season_code,battle_id,uid), KEY idx_pvp_rating_uid(season_code,uid,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE pvp_battles ADD COLUMN IF NOT EXISTS ranking_settled TINYINT(1) NOT NULL DEFAULT 0;
