-- PvP replay history, matchmaking queue, and season rewards.
CREATE TABLE IF NOT EXISTS pvp_replay_events (
  replay_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  battle_id BIGINT UNSIGNED NOT NULL,
  sequence_no INT UNSIGNED NOT NULL,
  phase VARCHAR(32) NOT NULL,
  event_at_seconds INT UNSIGNED NOT NULL DEFAULT 0,
  label VARCHAR(160) NOT NULL,
  event_json LONGTEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pvp_replay_event(battle_id,sequence_no), KEY idx_pvp_replay_battle(battle_id,sequence_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS pvp_matchmaking_queue (
  queue_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  season_code VARCHAR(32) NOT NULL,
  uid INT NOT NULL,
  rating INT NOT NULL DEFAULT 1000,
  division VARCHAR(32) NOT NULL DEFAULT 'Commander',
  status ENUM('queued','matched','cancelled') NOT NULL DEFAULT 'queued',
  queued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  matched_battle_id BIGINT UNSIGNED NULL,
  origin_planet_id INT NOT NULL DEFAULT 0,
  target_planet_id INT NOT NULL DEFAULT 0,
  fleet_json LONGTEXT NOT NULL,
  fitting_json LONGTEXT NOT NULL,
  UNIQUE KEY uq_pvp_queue_player(season_code,uid,status), KEY idx_pvp_queue_search(season_code,status,rating,queued_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS pvp_season_rewards (
  reward_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  season_code VARCHAR(32) NOT NULL,
  uid INT NOT NULL,
  place_position INT NOT NULL,
  title VARCHAR(80) NOT NULL,
  dark_matter BIGINT UNSIGNED NOT NULL DEFAULT 0,
  metal BIGINT UNSIGNED NOT NULL DEFAULT 0,
  crystal BIGINT UNSIGNED NOT NULL DEFAULT 0,
  deuterium BIGINT UNSIGNED NOT NULL DEFAULT 0,
  claimed_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pvp_reward_player(season_code,uid), KEY idx_pvp_reward_season(season_code,place_position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
