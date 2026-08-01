#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

DB_NAME="${SGW_DB_NAME:-sgw}"
DB_USER="${SGW_DB_USER:-sgw}"
DB_PASS="${SGW_DB_PASS:-sgwpass}"

for sql_file in database/sql/03_backend_tables.sql database/sql/04_reporting_views.sql database/sql/05_seed_backend_defaults.sql; do
  echo "[db_migrate] Applying $sql_file"
  mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$sql_file"
done

echo "[db_migrate] Migration batch complete."
