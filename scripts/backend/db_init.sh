#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

MYSQL_ADMIN_USER="${MYSQL_ADMIN_USER:-root}"
MYSQL_ADMIN_PASS="${MYSQL_ADMIN_PASS:-}"

MYSQL_ADMIN_ARGS=("-u" "$MYSQL_ADMIN_USER")
if [[ -n "$MYSQL_ADMIN_PASS" ]]; then
  MYSQL_ADMIN_ARGS+=("-p$MYSQL_ADMIN_PASS")
fi

echo "[db_init] Creating database and grants..."
mysql "${MYSQL_ADMIN_ARGS[@]}" < database/sql/01_create_database.sql

echo "[db_init] Importing core schema..."
mysql -u "${SGW_DB_USER:-sgw}" -p"${SGW_DB_PASS:-sgwpass}" "${SGW_DB_NAME:-sgw}" < game.sql

echo "[db_init] Applying backend tables..."
mysql -u "${SGW_DB_USER:-sgw}" -p"${SGW_DB_PASS:-sgwpass}" "${SGW_DB_NAME:-sgw}" < database/sql/03_backend_tables.sql

echo "[db_init] Applying reporting views..."
mysql -u "${SGW_DB_USER:-sgw}" -p"${SGW_DB_PASS:-sgwpass}" "${SGW_DB_NAME:-sgw}" < database/sql/04_reporting_views.sql

echo "[db_init] Seeding backend defaults..."
mysql -u "${SGW_DB_USER:-sgw}" -p"${SGW_DB_PASS:-sgwpass}" "${SGW_DB_NAME:-sgw}" < database/sql/05_seed_backend_defaults.sql

echo "[db_init] Done."
