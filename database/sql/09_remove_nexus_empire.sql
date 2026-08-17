-- Remove the retired Nexus Empire expansion from existing databases.
DROP VIEW IF EXISTS vw_nexus_empire_overview;
DROP TABLE IF EXISTS nexus_fleet_missions;
DROP TABLE IF EXISTS nexus_fleet_ships;
DROP TABLE IF EXISTS nexus_buildings;
DROP TABLE IF EXISTS nexus_research;
DROP TABLE IF EXISTS nexus_lifeforms;
DROP TABLE IF EXISTS nexus_empire_state;
DROP TABLE IF EXISTS nexus_resources;
DROP TABLE IF EXISTS nexus_planets;
DELETE FROM app_settings WHERE setting_key IN ('resource_production_multiplier','fleet_speed_multiplier');
