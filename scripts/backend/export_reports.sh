#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"
PHP_BIN="${PHP_BIN:-/usr/bin/php}"

echo "[export_reports] Generating SQL and Excel-compatible CSV reports..."
"$PHP_BIN" tools/backend/export_reports.php

echo "[export_reports] Done."
