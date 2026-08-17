-- Power Grid expansion: worlds, moons, starbases, moon bases, and stations
CREATE TABLE IF NOT EXISTS power_nodes (
  node_id INT NOT NULL AUTO_INCREMENT,
  uid INT NOT NULL,
  node_type ENUM('homeworld','planet','moon','starbase','moonbase','spacestation') NOT NULL,
  node_name VARCHAR(120) NOT NULL,
  parent_node_id INT NULL,
  orbital_slot VARCHAR(32) NULL,
  reactor_level INT NOT NULL DEFAULT 1,
  storage_level INT NOT NULL DEFAULT 1,
  grid_level INT NOT NULL DEFAULT 1,
  efficiency_level INT NOT NULL DEFAULT 1,
  power_stored BIGINT NOT NULL DEFAULT 0,
  power_capacity BIGINT NOT NULL DEFAULT 10000,
  production_rate BIGINT NOT NULL DEFAULT 100,
  consumption_rate BIGINT NOT NULL DEFAULT 50,
  last_tick_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  grid_status ENUM('online','brownout','offline','overloaded') NOT NULL DEFAULT 'online',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (node_id),
  UNIQUE KEY uq_power_node_owner_name (uid,node_name),
  KEY idx_power_node_uid (uid),
  KEY idx_power_node_parent (parent_node_id),
  CONSTRAINT fk_power_node_user FOREIGN KEY (uid) REFERENCES users(uid) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS power_assets (
  asset_id INT NOT NULL AUTO_INCREMENT,
  node_id INT NOT NULL,
  asset_type ENUM('fusion_reactor','solar_array','zero_point_core','battery_bank','orbital_tether','life_support','industrial_ring','shield_grid','shipyard','research_grid','defense_grid','habitat_ring') NOT NULL,
  asset_name VARCHAR(120) NOT NULL,
  asset_level INT NOT NULL DEFAULT 1,
  base_generation INT NOT NULL DEFAULT 0,
  base_consumption INT NOT NULL DEFAULT 0,
  enabled TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (asset_id),
  UNIQUE KEY uq_power_asset_node_name (node_id,asset_name),
  KEY idx_power_asset_node (node_id),
  CONSTRAINT fk_power_asset_node FOREIGN KEY (node_id) REFERENCES power_nodes(node_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS power_transfers (
  transfer_id BIGINT NOT NULL AUTO_INCREMENT,
  uid INT NOT NULL,
  source_node_id INT NOT NULL,
  target_node_id INT NOT NULL,
  amount BIGINT NOT NULL,
  status ENUM('queued','complete','cancelled') NOT NULL DEFAULT 'complete',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (transfer_id),
  KEY idx_power_transfer_user (uid),
  CONSTRAINT fk_power_transfer_user FOREIGN KEY (uid) REFERENCES users(uid) ON DELETE CASCADE,
  CONSTRAINT fk_power_transfer_source FOREIGN KEY (source_node_id) REFERENCES power_nodes(node_id) ON DELETE CASCADE,
  CONSTRAINT fk_power_transfer_target FOREIGN KEY (target_node_id) REFERENCES power_nodes(node_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO power_nodes (uid,node_type,node_name,orbital_slot,reactor_level,storage_level,grid_level,efficiency_level,power_stored,power_capacity,production_rate,consumption_rate)
SELECT u.uid,'homeworld',COALESCE(NULLIF(p.plnt_name,''),CONCAT(u.uname,' Homeworld')),'HOME',3,3,2,2,85000,120000,1500,760
FROM users u LEFT JOIN planets p ON p.uid=u.uid AND p.isHome=1
WHERE NOT EXISTS (SELECT 1 FROM power_nodes n WHERE n.uid=u.uid AND n.node_type='homeworld');

INSERT INTO power_nodes (uid,node_type,node_name,orbital_slot,reactor_level,storage_level,grid_level,efficiency_level,power_stored,power_capacity,production_rate,consumption_rate)
SELECT u.uid,'planet',COALESCE(NULLIF(p.plnt_name,''),CONCAT(u.uname,' Colony ',p.pid)),CONCAT('P-',p.pid),1,1,1,1,18000,30000,360,240
FROM users u INNER JOIN planets p ON p.uid=u.uid AND p.isHome=0
WHERE NOT EXISTS (SELECT 1 FROM power_nodes n WHERE n.uid=u.uid AND n.node_type='planet' AND n.orbital_slot=CONCAT('P-',p.pid));

INSERT INTO power_nodes (uid,node_type,node_name,orbital_slot,reactor_level,storage_level,grid_level,efficiency_level,power_stored,power_capacity,production_rate,consumption_rate)
SELECT n.uid,'moon',CONCAT(n.node_name,' Moon'),'M-',1,1,1,1,6000,12000,120,130
FROM power_nodes n WHERE n.node_type='planet'
AND NOT EXISTS (SELECT 1 FROM power_nodes m WHERE m.uid=n.uid AND m.node_type='moon' AND m.parent_node_id=n.node_id);

INSERT INTO power_nodes (uid,node_type,node_name,parent_node_id,orbital_slot,reactor_level,storage_level,grid_level,efficiency_level,power_stored,power_capacity,production_rate,consumption_rate)
SELECT n.uid,'starbase',CONCAT(n.node_name,' Starbase'),n.node_id,'SB-',2,2,2,1,28000,50000,700,560
FROM power_nodes n WHERE n.node_type='homeworld'
AND NOT EXISTS (SELECT 1 FROM power_nodes b WHERE b.uid=n.uid AND b.node_type='starbase');

INSERT INTO power_nodes (uid,node_type,node_name,parent_node_id,orbital_slot,reactor_level,storage_level,grid_level,efficiency_level,power_stored,power_capacity,production_rate,consumption_rate)
SELECT n.uid,'moonbase',CONCAT(n.node_name,' Moon Base'),n.node_id,'MB-',1,1,1,1,5000,10000,90,110
FROM power_nodes n WHERE n.node_type='moon'
AND NOT EXISTS (SELECT 1 FROM power_nodes b WHERE b.uid=n.uid AND b.node_type='moonbase');

INSERT INTO power_nodes (uid,node_type,node_name,parent_node_id,orbital_slot,reactor_level,storage_level,grid_level,efficiency_level,power_stored,power_capacity,production_rate,consumption_rate)
SELECT n.uid,'spacestation',CONCAT(n.node_name,' Orbital Station'),n.node_id,'ST-',2,2,1,1,12000,24000,260,300
FROM power_nodes n WHERE n.node_type='planet'
AND NOT EXISTS (SELECT 1 FROM power_nodes b WHERE b.uid=n.uid AND b.node_type='spacestation' AND b.parent_node_id=n.node_id);

INSERT INTO power_assets (node_id,asset_type,asset_name,base_generation,base_consumption)
SELECT n.node_id,'fusion_reactor','Fusion Reactor',900,120 FROM power_nodes n
WHERE NOT EXISTS (SELECT 1 FROM power_assets a WHERE a.node_id=n.node_id AND a.asset_name='Fusion Reactor');
INSERT INTO power_assets (node_id,asset_type,asset_name,base_generation,base_consumption)
SELECT n.node_id,'solar_array','Solar Array',220,20 FROM power_nodes n
WHERE NOT EXISTS (SELECT 1 FROM power_assets a WHERE a.node_id=n.node_id AND a.asset_name='Solar Array');
INSERT INTO power_assets (node_id,asset_type,asset_name,base_generation,base_consumption)
SELECT n.node_id,'battery_bank','Battery Bank',0,12 FROM power_nodes n
WHERE NOT EXISTS (SELECT 1 FROM power_assets a WHERE a.node_id=n.node_id AND a.asset_name='Battery Bank');
INSERT INTO power_assets (node_id,asset_type,asset_name,base_generation,base_consumption)
SELECT n.node_id,'life_support','Life Support',0,180 FROM power_nodes n
WHERE NOT EXISTS (SELECT 1 FROM power_assets a WHERE a.node_id=n.node_id AND a.asset_name='Life Support');
INSERT INTO power_assets (node_id,asset_type,asset_name,base_generation,base_consumption)
SELECT n.node_id,'industrial_ring','Industrial Ring',120,260 FROM power_nodes n
WHERE n.node_type IN ('homeworld','planet','starbase','spacestation')
AND NOT EXISTS (SELECT 1 FROM power_assets a WHERE a.node_id=n.node_id AND a.asset_name='Industrial Ring');
INSERT INTO power_assets (node_id,asset_type,asset_name,base_generation,base_consumption)
SELECT n.node_id,'shield_grid','Shield Grid',0,220 FROM power_nodes n
WHERE n.node_type IN ('homeworld','starbase','moonbase','spacestation')
AND NOT EXISTS (SELECT 1 FROM power_assets a WHERE a.node_id=n.node_id AND a.asset_name='Shield Grid');

-- Guarantee the core homeworld orbital complex exists even for empires with no secondary planets.
INSERT INTO power_nodes (uid,node_type,node_name,parent_node_id,orbital_slot,reactor_level,storage_level,grid_level,efficiency_level,power_stored,power_capacity,production_rate,consumption_rate)
SELECT n.uid,'moon',CONCAT(n.node_name,' Moon'),n.node_id,'M-HOME',1,1,1,1,6000,12000,120,130
FROM power_nodes n WHERE n.node_type='homeworld'
AND NOT EXISTS (SELECT 1 FROM power_nodes m WHERE m.uid=n.uid AND m.node_type='moon' AND m.parent_node_id=n.node_id);

INSERT INTO power_nodes (uid,node_type,node_name,parent_node_id,orbital_slot,reactor_level,storage_level,grid_level,efficiency_level,power_stored,power_capacity,production_rate,consumption_rate)
SELECT n.uid,'moonbase',CONCAT(n.node_name,' Moon Base'),n.node_id,'MB-HOME',1,1,1,1,5000,10000,90,110
FROM power_nodes n WHERE n.node_type='moon'
AND NOT EXISTS (SELECT 1 FROM power_nodes b WHERE b.uid=n.uid AND b.node_type='moonbase' AND b.parent_node_id=n.node_id);

INSERT INTO power_nodes (uid,node_type,node_name,parent_node_id,orbital_slot,reactor_level,storage_level,grid_level,efficiency_level,power_stored,power_capacity,production_rate,consumption_rate)
SELECT n.uid,'spacestation',CONCAT(n.node_name,' Orbital Station'),n.node_id,'ST-HOME',2,2,1,1,12000,24000,260,300
FROM power_nodes n WHERE n.node_type='homeworld'
AND NOT EXISTS (SELECT 1 FROM power_nodes b WHERE b.uid=n.uid AND b.node_type='spacestation' AND b.parent_node_id=n.node_id);

INSERT INTO power_assets (node_id,asset_type,asset_name,base_generation,base_consumption)
SELECT n.node_id,'solar_array','Solar Array',220,20 FROM power_nodes n
WHERE NOT EXISTS (SELECT 1 FROM power_assets a WHERE a.node_id=n.node_id AND a.asset_name='Solar Array');
INSERT INTO power_assets (node_id,asset_type,asset_name,base_generation,base_consumption)
SELECT n.node_id,'battery_bank','Battery Bank',0,12 FROM power_nodes n
WHERE NOT EXISTS (SELECT 1 FROM power_assets a WHERE a.node_id=n.node_id AND a.asset_name='Battery Bank');
