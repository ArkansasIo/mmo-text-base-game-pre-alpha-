#!/usr/bin/env bash
set -Eeuo pipefail
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"
PHP_BIN="${PHP_BIN:-/usr/bin/php}"
LOG_DIR="${CRON_LOG_DIR:-$ROOT_DIR/var/log/cron}"
LOCK_DIR="${CRON_LOCK_DIR:-$ROOT_DIR/var/lock}"
JOB="${1:-}"
DRY_RUN=0
for arg in "${@:2}"; do [[ "$arg" == "--dry-run" ]] && DRY_RUN=1; done
mkdir -p "$LOG_DIR" "$LOCK_DIR"
if [[ -z "$JOB" ]]; then
  echo "Usage: $0 {game_tick|pvp_tick|pvp_rewards_tick|email_tick|wormhole_tick|healthcheck|backup|reports|migrate} [--dry-run]" >&2
  exit 64
fi
case "$JOB" in
  game_tick) COMMAND=("$PHP_BIN" scripts/backend/game_tick.php) ;;
  pvp_tick) COMMAND=("$PHP_BIN" scripts/backend/pvp_tick.php) ;;
  pvp_rewards_tick) COMMAND=("$PHP_BIN" scripts/backend/pvp_rewards_tick.php) ;;
  email_tick) COMMAND=("$PHP_BIN" scripts/backend/email_tick.php) ;;
  wormhole_tick) COMMAND=("$PHP_BIN" scripts/backend/wormhole_tick.php) ;;
  healthcheck) COMMAND=(bash scripts/backend/healthcheck.sh) ;;
  backup) COMMAND=(bash scripts/backend/db_backup.sh) ;;
  reports) COMMAND=(bash scripts/backend/export_reports.sh) ;;
  migrate) COMMAND=(bash scripts/backend/db_migrate.sh) ;;
  *) echo "Unknown cron job: $JOB" >&2; exit 64 ;;
esac
LOG_FILE="$LOG_DIR/${JOB}.log"
LOCK_FILE="$LOCK_DIR/${JOB}.lock"
exec 9>"$LOCK_FILE"
if ! flock -n 9; then
  printf '%s job=%s status=skipped reason=already-running\n' "$(date -u +%FT%TZ)" "$JOB" | tee -a "$LOG_FILE"
  exit 0
fi
started=$(date +%s)
printf '%s job=%s status=started dry_run=%s\n' "$(date -u +%FT%TZ)" "$JOB" "$DRY_RUN" | tee -a "$LOG_FILE"
if (( DRY_RUN )); then
  printf 'dry-run command:'; printf ' %q' "${COMMAND[@]}"; printf '\n' | tee -a "$LOG_FILE"
  exit 0
fi
set +e
if command -v timeout >/dev/null 2>&1; then
  timeout --signal=TERM --kill-after=15s "${CRON_TIMEOUT_SECONDS:-900}" "${COMMAND[@]}" >>"$LOG_FILE" 2>&1
  code=$?
else
  "${COMMAND[@]}" >>"$LOG_FILE" 2>&1
  code=$?
fi
set -e
finished=$(date +%s)
duration=$((finished-started))
if (( code == 0 )); then
  printf '%s job=%s status=completed duration_seconds=%s\n' "$(date -u +%FT%TZ)" "$JOB" "$duration" | tee -a "$LOG_FILE"
else
  printf '%s job=%s status=failed exit_code=%s duration_seconds=%s\n' "$(date -u +%FT%TZ)" "$JOB" "$code" "$duration" | tee -a "$LOG_FILE" >&2
fi
exit "$code"
