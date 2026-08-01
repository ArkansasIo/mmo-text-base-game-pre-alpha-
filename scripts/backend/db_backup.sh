#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

DB_NAME="${SGW_DB_NAME:-sgw}"
DB_USER="${SGW_DB_USER:-sgw}"
DB_PASS="${SGW_DB_PASS:-sgwpass}"
STAMP="$(date +%Y%m%d_%H%M%S)"
OUT_DIR="backups/sql"
OUT_FILE="$OUT_DIR/${DB_NAME}_$STAMP.sql.gz"

mkdir -p "$OUT_DIR"

echo "[db_backup] Creating backup at $OUT_FILE"
mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" | gzip > "$OUT_FILE"

echo "[db_backup] Backup complete."
