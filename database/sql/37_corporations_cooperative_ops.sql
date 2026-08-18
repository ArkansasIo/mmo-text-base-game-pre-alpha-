CREATE TABLE IF NOT EXISTS corporations (
  corporation_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  corporation_name VARCHAR(80) NOT NULL,
  corporation_tag VARCHAR(12) NOT NULL,
  description VARCHAR(500) NOT NULL DEFAULT '',
  director_uid INT NOT NULL,
  shared_research_pool BIGINT UNSIGNED NOT NULL DEFAULT 0,
  shared_fleet_pool BIGINT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (corporation_id),
  UNIQUE KEY uq_corp_name (corporation_name),
  UNIQUE KEY uq_corp_tag (corporation_tag),
  KEY idx_corp_director (director_uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS corporation_members (
  corporation_id INT UNSIGNED NOT NULL,
  uid INT NOT NULL,
  role ENUM('member','researcher','operator','officer','director') NOT NULL DEFAULT 'member',
  joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (corporation_id, uid),
  UNIQUE KEY uq_corp_member (uid),
  KEY idx_corp_role (corporation_id, role, joined_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS corporation_research (
  corporation_id INT UNSIGNED NOT NULL,
  research_key VARCHAR(48) NOT NULL,
  level INT UNSIGNED NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (corporation_id, research_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS corporation_contributions (
  contribution_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  corporation_id INT UNSIGNED NOT NULL,
  uid INT NOT NULL,
  resource_type ENUM('naquadah','metal','crystal','deuterium','energy') NOT NULL,
  amount BIGINT UNSIGNED NOT NULL,
  purpose ENUM('research','fleet') NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (contribution_id),
  KEY idx_corp_contrib (corporation_id, purpose, created_at),
  KEY idx_member_contrib (uid, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS corporation_operations (
  operation_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  corporation_id INT UNSIGNED NOT NULL,
  created_by INT NOT NULL,
  mission ENUM('joint_defense','expedition','coordinated_strike','territory_relief') NOT NULL,
  target_planet_id INT NOT NULL,
  fleet_json LONGTEXT NOT NULL,
  combined_attack BIGINT UNSIGNED NOT NULL DEFAULT 0,
  combined_defense BIGINT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('forming','launched','resolved','cancelled') NOT NULL DEFAULT 'forming',
  launched_at DATETIME NULL,
  resolves_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (operation_id),
  KEY idx_corp_ops (corporation_id, status, created_at),
  KEY idx_corp_ops_due (status, resolves_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS corporation_operation_members (
  operation_id BIGINT UNSIGNED NOT NULL,
  uid INT NOT NULL,
  fleet_json LONGTEXT NOT NULL,
  attack_power BIGINT UNSIGNED NOT NULL DEFAULT 0,
  defense_power BIGINT UNSIGNED NOT NULL DEFAULT 0,
  joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (operation_id, uid),
  KEY idx_operation_member (uid, joined_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
