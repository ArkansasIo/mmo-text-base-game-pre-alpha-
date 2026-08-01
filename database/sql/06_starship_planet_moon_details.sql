-- Migration 06: Starship, Planet, and Moon detail columns + seed data
-- Safe to re-run: uses ADD COLUMN IF NOT EXISTS and ON DUPLICATE KEY UPDATE

-- ─────────────────────────────────────────────────────────────────────────────
-- STARSHIP CATALOG — full stats
-- ─────────────────────────────────────────────────────────────────────────────

ALTER TABLE `mega_starship_catalog`
  ADD COLUMN IF NOT EXISTS `size_class`        varchar(4)   NOT NULL DEFAULT 'E',
  ADD COLUMN IF NOT EXISTS `size_subclass`     varchar(8)   NOT NULL DEFAULT 'II',
  ADD COLUMN IF NOT EXISTS `ship_subtype`      varchar(40)  NOT NULL DEFAULT 'Standard',
  ADD COLUMN IF NOT EXISTS `length_m`          int(11)      NOT NULL DEFAULT 100,
  ADD COLUMN IF NOT EXISTS `width_m`           int(11)      NOT NULL DEFAULT 30,
  ADD COLUMN IF NOT EXISTS `height_m`          int(11)      NOT NULL DEFAULT 20,
  ADD COLUMN IF NOT EXISTS `mass_kton`         int(11)      NOT NULL DEFAULT 50,
  ADD COLUMN IF NOT EXISTS `max_speed`         int(11)      NOT NULL DEFAULT 5000,
  ADD COLUMN IF NOT EXISTS `hyperdrive`        tinyint(1)   NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `shields`           int(11)      NOT NULL DEFAULT 100,
  ADD COLUMN IF NOT EXISTS `armor`             int(11)      NOT NULL DEFAULT 150,
  ADD COLUMN IF NOT EXISTS `hull_hp`           int(11)      NOT NULL DEFAULT 200,
  ADD COLUMN IF NOT EXISTS `weapons_count`     int(11)      NOT NULL DEFAULT 2,
  ADD COLUMN IF NOT EXISTS `primary_weapon`    varchar(60)  NOT NULL DEFAULT 'Rail Cannon',
  ADD COLUMN IF NOT EXISTS `secondary_weapon`  varchar(60)  NOT NULL DEFAULT 'None',
  ADD COLUMN IF NOT EXISTS `special_ability`   varchar(120) NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS `crew_min`          int(11)      NOT NULL DEFAULT 5,
  ADD COLUMN IF NOT EXISTS `crew_max`          int(11)      NOT NULL DEFAULT 10,
  ADD COLUMN IF NOT EXISTS `cargo_capacity`    int(11)      NOT NULL DEFAULT 1000,
  ADD COLUMN IF NOT EXISTS `hangar_bays`       int(11)      NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `build_time_min`    int(11)      NOT NULL DEFAULT 30,
  ADD COLUMN IF NOT EXISTS `tech_req`          int(11)      NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `lore`              text;

-- Tier 1: Scout — Class D, ~62 m
UPDATE `mega_starship_catalog` SET
  size_class='D', size_subclass='I', ship_subtype='Recon Corvette',
  length_m=62, width_m=18, height_m=12, mass_kton=8,
  max_speed=18000, hyperdrive=1,
  shields=120, armor=80, hull_hp=150,
  weapons_count=2, primary_weapon='Pulse Cannon Mk-I', secondary_weapon='None',
  special_ability='Deep Scan Array: reveals enemy planet resource levels',
  crew_min=4, crew_max=8, cargo_capacity=800, hangar_bays=0,
  build_time_min=20, tech_req=0,
  lore='The lightest warframe in service, built for speed and stealth over raw firepower. Assigned to forward recon, border probe sweeps, and covert insertion missions. Its sublight burst capability makes it one of the fastest vessels in the fleet.'
WHERE tier=1;

-- Tier 2: Frigate — Class E, ~145 m
UPDATE `mega_starship_catalog` SET
  size_class='E', size_subclass='I', ship_subtype='Light Patrol Frigate',
  length_m=145, width_m=42, height_m=28, mass_kton=32,
  max_speed=12000, hyperdrive=1,
  shields=380, armor=280, hull_hp=520,
  weapons_count=4, primary_weapon='Twin Rail Cannon', secondary_weapon='Point Defense Array',
  special_ability='Intercept Protocol: +15% defense when stationed at home planet',
  crew_min=18, crew_max=35, cargo_capacity=3500, hangar_bays=0,
  build_time_min=45, tech_req=1,
  lore='A versatile multi-role warship that forms the backbone of most patrol fleets. Frigates fill the gap between rapid scouts and heavy warships, capable of sustained engagements and convoy escort duties. The standard frigate carries four weapon emplacements and an advanced ECM suite.'
WHERE tier=2;

-- Tier 3: Destroyer — Class F, ~268 m
UPDATE `mega_starship_catalog` SET
  size_class='F', size_subclass='II', ship_subtype='Heavy Destroyer',
  length_m=268, width_m=74, height_m=48, mass_kton=120,
  max_speed=9500, hyperdrive=1,
  shields=900, armor=750, hull_hp=1400,
  weapons_count=6, primary_weapon='Heavy Ion Cannon', secondary_weapon='Missile Battery',
  special_ability='Hull Breach Protocol: 10% chance to disable target weapons on hit',
  crew_min=55, crew_max=90, cargo_capacity=8000, hangar_bays=2,
  build_time_min=90, tech_req=2,
  lore='Destroyers are true warships — purpose-built for fleet engagements and orbital bombardment. Their heavy ion cannons can punch through planetary shields and their missile batteries provide long-range engagement capability. Two small hangar bays allow for fighter escort deployment.'
WHERE tier=3;

-- Tier 4: Cruiser — Class G-II, ~435 m
UPDATE `mega_starship_catalog` SET
  size_class='G', size_subclass='II', ship_subtype='Siege Cruiser',
  length_m=435, width_m=110, height_m=72, mass_kton=380,
  max_speed=7200, hyperdrive=1,
  shields=2200, armor=1900, hull_hp=3800,
  weapons_count=9, primary_weapon='Plasma Lance Array', secondary_weapon='Torpedo Tubes x6',
  special_ability='Siege Mode: +25% attack against planetary defenses',
  crew_min=140, crew_max=200, cargo_capacity=18000, hangar_bays=6,
  build_time_min=180, tech_req=3,
  lore='The cruiser represents the minimum viable capital ship for independent strategic operations. With nine weapon emplacements including the devastating plasma lance array, cruisers can engage orbital stations, suppression fleets, and surface fortifications. Six fighter bays provide tactical air cover during planet approaches.'
WHERE tier=4;

-- Tier 5: Battlecruiser — Class G-IV, ~664 m
UPDATE `mega_starship_catalog` SET
  size_class='G', size_subclass='IV', ship_subtype='Command Battlecruiser',
  length_m=664, width_m=165, height_m=105, mass_kton=950,
  max_speed=6000, hyperdrive=1,
  shields=5500, armor=5000, hull_hp=9500,
  weapons_count=14, primary_weapon='Naquadah Beam Cannon', secondary_weapon='Heavy Torpedo Array',
  special_ability='Fleet Command Aura: +10% power to all ships in fleet',
  crew_min=320, crew_max=480, cargo_capacity=40000, hangar_bays=12,
  build_time_min=360, tech_req=4,
  lore='The Battlecruiser is a command-capable warship designed to lead strike fleets in sustained combat operations. Its Naquadah-enhanced beam cannon delivers catastrophic focused energy strikes. A Fleet Command Aura boosts all subordinate vessels, making the Battlecruiser a force multiplier as much as a weapon platform.'
WHERE tier=5;

-- Tier 6: Carrier — Class H-I, ~920 m
UPDATE `mega_starship_catalog` SET
  size_class='H', size_subclass='I', ship_subtype='Expedition Carrier',
  length_m=920, width_m=240, height_m=160, mass_kton=2800,
  max_speed=4800, hyperdrive=1,
  shields=9000, armor=8500, hull_hp=18000,
  weapons_count=8, primary_weapon='Defensive Pulse Grid', secondary_weapon='Point Defense Turrets x16',
  special_ability='Mobile Fleet Logistics: restores 5% hull to allied ships each turn',
  crew_min=750, crew_max=1100, cargo_capacity=120000, hangar_bays=40,
  build_time_min=720, tech_req=5,
  lore='The Carrier sacrifices offensive firepower for unmatched logistical capability. Forty hangar bays can hold an entire wing of fighters, dropships, or colonization pods. Its Mobile Fleet Logistics ability makes it invaluable during extended campaigns far from home systems. Carriers rarely fight alone — they anchor the fleet while fighters do the work.'
WHERE tier=6;

-- Tier 7: Dreadnought — Class I-II, ~1520 m
UPDATE `mega_starship_catalog` SET
  size_class='I', size_subclass='II', ship_subtype='Orbital Fortress',
  length_m=1520, width_m=390, height_m=260, mass_kton=8500,
  max_speed=3200, hyperdrive=1,
  shields=22000, armor=20000, hull_hp=45000,
  weapons_count=22, primary_weapon='Subspace Rupture Cannon', secondary_weapon='Mass Driver Array x8',
  special_ability='Fortress Mode: when stationary +50% shields and +30% weapons output',
  crew_min=1800, crew_max=2600, cargo_capacity=280000, hangar_bays=80,
  build_time_min=1440, tech_req=6,
  lore='The Dreadnought is a walking death sentence against any planetary defense network. Its Subspace Rupture Cannon tears holes in conventional energy shields and its mass driver array delivers kinetic kill vehicles at relativistic velocities. When deployed in Fortress Mode, a Dreadnought becomes effectively immovable. Few empires can field more than two or three simultaneously.'
WHERE tier=7;

-- Tier 8: Titan — Class J-III, ~2460 m
UPDATE `mega_starship_catalog` SET
  size_class='J', size_subclass='III', ship_subtype='World Ender',
  length_m=2460, width_m=620, height_m=420, mass_kton=28000,
  max_speed=2100, hyperdrive=1,
  shields=55000, armor=52000, hull_hp=120000,
  weapons_count=36, primary_weapon='Ascension Cannon (planet-killer)', secondary_weapon='Quantum Torpedo Batteries x12',
  special_ability='World Scar: targeted planet loses 20% income and population for 24 hours',
  crew_min=4500, crew_max=7000, cargo_capacity=800000, hangar_bays=200,
  build_time_min=4320, tech_req=8,
  lore='Titans are the rarest and most feared warships in the known universe. Their Ascension Cannons are derived from Ancient weapons technology and can crack planetary crusts. The World Scar ability leaves lasting devastation on targeted worlds, disrupting their productive capacity for extended periods. Building a Titan requires the combined resources of multiple developed systems and years of dedicated shipyard time.'
WHERE tier=8;

-- Tier 9: Mothership — Class L-IV, ~4200 m
UPDATE `mega_starship_catalog` SET
  size_class='L', size_subclass='IV', ship_subtype='Sovereign Command Platform',
  length_m=4200, width_m=1100, height_m=750, mass_kton=95000,
  max_speed=1400, hyperdrive=1,
  shields=180000, armor=165000, hull_hp=400000,
  weapons_count=60, primary_weapon='Trinium-Naquadah Nova Beam', secondary_weapon='Drone Swarm Launchers x24',
  special_ability='Empire Nexus: all empire income and unit production +15% while Mothership is active',
  crew_min=12000, crew_max=18000, cargo_capacity=5000000, hangar_bays=500,
  build_time_min=14400, tech_req=10,
  lore='The Mothership is not merely a warship — it is a mobile sovereign capital, a self-sustaining city among the stars. Carrying an entire civilization''s worth of industrial capacity, the Mothership''s Trinium-Naquadah Nova Beam can erase orbital installations in a single discharge. Its 500 hangar bays support an independent war machine capable of projecting force anywhere in the galaxy simultaneously. Only the most advanced and resource-rich empires can construct and operate one.'
WHERE tier=9;


-- ─────────────────────────────────────────────────────────────────────────────
-- PLANETS TABLE — full classification and colony columns
-- ─────────────────────────────────────────────────────────────────────────────

ALTER TABLE `planets`
  ADD COLUMN IF NOT EXISTS `pid`             int(11)      NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `coord`           varchar(32)  NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS `world_type`      varchar(32)  NOT NULL DEFAULT 'Terran',
  ADD COLUMN IF NOT EXISTS `biome`           varchar(64)  NOT NULL DEFAULT 'Temperate Forest',
  ADD COLUMN IF NOT EXISTS `habitability`    int(11)      NOT NULL DEFAULT 50,
  ADD COLUMN IF NOT EXISTS `temperature`     int(11)      NOT NULL DEFAULT 20,
  ADD COLUMN IF NOT EXISTS `atmosphere`      varchar(32)  NOT NULL DEFAULT 'Breathable',
  ADD COLUMN IF NOT EXISTS `hazard_level`    int(11)      NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `population`      bigint(20)   NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `pop_cap`         bigint(20)   NOT NULL DEFAULT 1000000,
  ADD COLUMN IF NOT EXISTS `colony_status`   varchar(32)  NOT NULL DEFAULT 'unclaimed',
  ADD COLUMN IF NOT EXISTS `defense_rating`  int(11)      NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `build_slots`     int(11)      NOT NULL DEFAULT 4,
  ADD COLUMN IF NOT EXISTS `slots_used`      int(11)      NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `last_tick`       int(11)      NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `size_class`      varchar(4)   NOT NULL DEFAULT 'F',
  ADD COLUMN IF NOT EXISTS `size_subclass`   varchar(4)   NOT NULL DEFAULT 'II',
  ADD COLUMN IF NOT EXISTS `planet_type`     varchar(32)  NOT NULL DEFAULT 'Terrestrial',
  ADD COLUMN IF NOT EXISTS `planet_subtype`  varchar(32)  NOT NULL DEFAULT 'Temperate',
  ADD COLUMN IF NOT EXISTS `diameter_km`     int(11)      NOT NULL DEFAULT 12000,
  ADD COLUMN IF NOT EXISTS `gravity`         float        NOT NULL DEFAULT 1.0,
  ADD COLUMN IF NOT EXISTS `day_length`      float        NOT NULL DEFAULT 24.0,
  ADD COLUMN IF NOT EXISTS `year_length`     int(11)      NOT NULL DEFAULT 365,
  ADD COLUMN IF NOT EXISTS `axial_tilt`      float        NOT NULL DEFAULT 23.5,
  ADD COLUMN IF NOT EXISTS `magnetic_field`  varchar(16)  NOT NULL DEFAULT 'Moderate',
  ADD COLUMN IF NOT EXISTS `radiation_level` int(11)      NOT NULL DEFAULT 3,
  ADD COLUMN IF NOT EXISTS `tectonic_activity` varchar(16) NOT NULL DEFAULT 'Low',
  ADD COLUMN IF NOT EXISTS `water_coverage`  int(11)      NOT NULL DEFAULT 40,
  ADD COLUMN IF NOT EXISTS `oxygen_pct`      int(11)      NOT NULL DEFAULT 21,
  ADD COLUMN IF NOT EXISTS `metal_deposit`   int(11)      NOT NULL DEFAULT 500,
  ADD COLUMN IF NOT EXISTS `crystal_deposit` int(11)      NOT NULL DEFAULT 400,
  ADD COLUMN IF NOT EXISTS `deut_deposit`    int(11)      NOT NULL DEFAULT 300,
  ADD COLUMN IF NOT EXISTS `anomaly`         varchar(128) NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS `moon_count`      int(11)      NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `discovered_at`   int(11)      NOT NULL DEFAULT 0;


-- ─────────────────────────────────────────────────────────────────────────────
-- MOON DATA TABLE — full classification
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `moon_data` (
  `moon_id`        int(11)      NOT NULL AUTO_INCREMENT,
  `pid`            int(11)      NOT NULL DEFAULT 0,
  `uid`            int(11)      NOT NULL DEFAULT 0,
  `moon_name`      varchar(64)  NOT NULL DEFAULT '',
  `moon_class`     varchar(32)  NOT NULL DEFAULT 'Rocky',
  `scan_bonus`     int(11)      NOT NULL DEFAULT 0,
  `def_bonus`      int(11)      NOT NULL DEFAULT 0,
  `deut_bonus`     int(11)      NOT NULL DEFAULT 0,
  `has_base`       tinyint(1)   NOT NULL DEFAULT 0,
  `base_level`     int(11)      NOT NULL DEFAULT 0,
  `discovered`     tinyint(1)   NOT NULL DEFAULT 0,
  `moon_type`      varchar(32)  NOT NULL DEFAULT 'Rocky',
  `moon_subtype`   varchar(32)  NOT NULL DEFAULT 'Cratered',
  `size_class`     varchar(4)   NOT NULL DEFAULT 'B',
  `diameter_km`    int(11)      NOT NULL DEFAULT 1200,
  `orbit_period`   float        NOT NULL DEFAULT 28.0,
  `tidal_locked`   tinyint(1)   NOT NULL DEFAULT 1,
  `surface_temp`   int(11)      NOT NULL DEFAULT -50,
  `gravity`        float        NOT NULL DEFAULT 0.17,
  `atmosphere`     varchar(32)  NOT NULL DEFAULT 'None',
  `radiation_level` int(11)     NOT NULL DEFAULT 8,
  `water_ice`      tinyint(1)   NOT NULL DEFAULT 0,
  `anomaly`        varchar(128) NOT NULL DEFAULT '',
  `coord`          varchar(32)  NOT NULL DEFAULT '',
  PRIMARY KEY (`moon_id`),
  UNIQUE KEY `pid_name` (`pid`, `moon_name`),
  KEY `idx_uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
