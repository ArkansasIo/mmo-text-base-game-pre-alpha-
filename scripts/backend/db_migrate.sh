#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

DB_NAME="${SGW_DB_NAME:-sgw}"
DB_USER="${SGW_DB_USER:-sgw}"
DB_PASS="${SGW_DB_PASS:-sgwpass}"

for sql_file in database/sql/03_backend_tables.sql database/sql/04_reporting_views.sql database/sql/05_seed_backend_defaults.sql database/sql/06_starship_planet_moon_details.sql database/sql/07_admin_control_plane.sql database/sql/07_unit_catalog_seed.sql database/sql/08_legacy_training_defaults.sql database/sql/09_remove_nexus_empire.sql database/sql/10_power_grid.sql database/sql/11_universe_seed.sql database/sql/12_universe_taxonomy.sql database/sql/13_universe_intelligence.sql database/sql/19_player_accounts.sql database/sql/20_communications.sql database/sql/21_guild_system.sql database/sql/22_population_model.sql database/sql/23_guild_territory.sql database/sql/24_territory_economy.sql database/sql/25_market_trade_routes.sql database/sql/26_guild_research_diplomacy_warfare.sql database/sql/27_guild_dynamic_events.sql database/sql/28_fleet_leaderboards_achievements.sql database/sql/29_crafting_notifications.sql database/sql/30_race_government_system.sql database/sql/31_player_pvp.sql database/sql/32_concurrency_indexes.sql database/sql/33_module_fitting.sql database/sql/34_player_market_trading.sql database/sql/35_research_blueprint_unlocks.sql database/sql/36_player_notifications.sql database/sql/37_corporations_cooperative_ops.sql database/sql/38_strategic_resources_dark_matter.sql database/sql/39_corporation_rare_orderbook.sql database/sql/40_wormhole_exploration.sql database/sql/41_wormhole_stability_degradation.sql database/sql/42_hyperspace_mission_outcomes.sql database/sql/43_legacy_fleet_mission_outcomes.sql database/sql/44_pvp_rankings.sql database/sql/45_pvp_replays_matchmaking_rewards.sql database/sql/46_game_email_system.sql database/sql/47_mail_broadcast_attachments.sql database/sql/48_historical_strategy_state.sql; do
  echo "[db_migrate] Applying $sql_file"
  mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$sql_file"
done

php scripts/backend/seed_universe.php
echo "[db_migrate] Migration batch complete."
