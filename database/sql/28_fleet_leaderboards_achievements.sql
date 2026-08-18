-- Shipyard, fleet deployment, leaderboard, and achievement systems
CREATE TABLE IF NOT EXISTS player_resource_wallet (
  uid INT NOT NULL,
  metal BIGINT UNSIGNED NOT NULL DEFAULT 80000,
  crystal BIGINT UNSIGNED NOT NULL DEFAULT 60000,
  energy BIGINT UNSIGNED NOT NULL DEFAULT 30000,
  PRIMARY KEY(uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS shipyard_queue (
  queue_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  uid INT NOT NULL,
  planet_id INT NOT NULL,
  ship_type VARCHAR(24) NOT NULL,
  quantity INT UNSIGNED NOT NULL,
  metal_cost BIGINT UNSIGNED NOT NULL DEFAULT 0,
  crystal_cost BIGINT UNSIGNED NOT NULL DEFAULT 0,
  energy_cost BIGINT UNSIGNED NOT NULL DEFAULT 0,
  completes_at DATETIME NOT NULL,
  status ENUM('building','completed','cancelled') NOT NULL DEFAULT 'building',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(queue_id), KEY idx_shipyard(uid,status,completes_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS player_fleet_inventory (
  uid INT NOT NULL,
  planet_id INT NOT NULL,
  ship_type VARCHAR(24) NOT NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY(uid,planet_id,ship_type), KEY idx_fleet_uid(uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS fleet_deployments (
  deployment_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  uid INT NOT NULL,
  origin_planet_id INT NOT NULL,
  destination_planet_id INT NOT NULL,
  fleet_json TEXT NOT NULL,
  attack_power BIGINT UNSIGNED NOT NULL DEFAULT 0,
  defense_power BIGINT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('enroute','arrived','recalled','lost') NOT NULL DEFAULT 'enroute',
  arrive_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(deployment_id), KEY idx_deploy(uid,status,arrive_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS leaderboard_snapshots (
  snapshot_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  board_key VARCHAR(32) NOT NULL,
  subject_type ENUM('guild','member','territory') NOT NULL,
  subject_id BIGINT UNSIGNED NOT NULL,
  score BIGINT NOT NULL DEFAULT 0,
  rank_position INT UNSIGNED NOT NULL DEFAULT 0,
  captured_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(snapshot_id), KEY idx_board(board_key,captured_at,rank_position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS achievement_definitions (
  achievement_key VARCHAR(48) NOT NULL,
  name VARCHAR(100) NOT NULL,
  description VARCHAR(255) NOT NULL,
  target_value BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY(achievement_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS achievement_progress (
  uid INT NOT NULL,
  achievement_key VARCHAR(48) NOT NULL,
  progress_value BIGINT UNSIGNED NOT NULL DEFAULT 0,
  unlocked_at DATETIME NULL,
  PRIMARY KEY(uid,achievement_key), KEY idx_achievement(achievement_key,unlocked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO achievement_definitions VALUES
 ('territory_pioneer','Territory Pioneer','Control a guild territory.',1),
 ('industrial_magnate','Industrial Magnate','Accumulate 1000000 guild contribution points.',1000000),
 ('fleet_commander','Fleet Commander','Build 100 ships.',100),
 ('war_hero','War Hero','Win 10 guild raids.',10);
