#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"
PHP_BIN="${PHP_BIN:-/usr/bin/php}"
PHP_ARGS="${PHP_ARGS:--dxdebug.mode=off}"

echo "[healthcheck] Running backend checks..."
"$PHP_BIN" $PHP_ARGS tools/backend/healthcheck.php

echo "[healthcheck] Completed."
