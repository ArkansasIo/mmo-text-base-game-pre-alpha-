# database/sql/

Layered SQL bundle for database provisioning and backend reporting.

## Order

1. `01_create_database.sql`
2. `02_import_core_schema.sql`
3. `03_backend_tables.sql`
4. `04_reporting_views.sql`
5. `05_seed_backend_defaults.sql`
6. `06_starship_planet_moon_details.sql`
7. `07_unit_catalog_seed.sql`

Use `scripts/backend/db_migrate.sh` for repeatable execution.
