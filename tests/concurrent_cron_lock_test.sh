#!/usr/bin/env bash
set -Eeuo pipefail
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
RUNNER="$ROOT_DIR/scripts/backend/cron_runner.sh"
FAKE_PHP="$ROOT_DIR/tests/fixtures/fake_php_sleep.sh"
TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT
mkdir -p "$TMP_DIR/log" "$TMP_DIR/lock"
chmod +x "$RUNNER" "$FAKE_PHP"
export PHP_BIN="$FAKE_PHP"
export CRON_LOG_DIR="$TMP_DIR/log"
export CRON_LOCK_DIR="$TMP_DIR/lock"
export CRON_TIMEOUT_SECONDS=10
export FAKE_GAME_TICK_SLEEP=2
pids=()
for _ in 1 2 3 4 5; do
  "$RUNNER" game_tick >>"$TMP_DIR/launcher.log" 2>&1 &
  pids+=("$!")
done
for pid in "${pids[@]}"; do wait "$pid"; done
log="$TMP_DIR/log/game_tick.log"
completed=$(grep -c 'status=completed' "$log" || true)
skipped=$(grep -c 'status=skipped reason=already-running' "$log" || true)
[[ "$completed" -eq 1 ]] || { echo "expected one completed worker, got $completed" >&2; cat "$log"; exit 1; }
[[ "$skipped" -eq 4 ]] || { echo "expected four skipped workers, got $skipped" >&2; cat "$log"; exit 1; }
echo "Concurrent cron lock test passed: completed=$completed skipped=$skipped"
