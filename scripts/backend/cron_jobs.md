# Scheduled Operations

The game uses `scripts/backend/cron_runner.sh` as the single entry point for deterministic background work. It provides per-job locks, UTC timestamps, append-only logs, timeouts, dry-run output, and nonzero exit codes for monitoring.

## Available jobs

| Job | Purpose | Suggested schedule |
|---|---|---|
| `game_tick` | Resources, territory economy, research completion, fleet arrivals, raids, events, alerts, and achievements | Every 5 minutes |
| `healthcheck` | PHP, database, migration, and backend integrity checks | Every 15 minutes |
| `backup` | Database backup using the project backup script | Daily at 02:15 |
| `reports` | Generate operational exports and reports | Hourly |
| `migrate` | Apply ordered SQL migrations; run manually during deployment | Manual or release window |

The game tick remains authoritative for all state changes. Running it every five minutes allows the worker to catch up to the configured game cadence while its idempotent settlement logic prevents duplicate rewards.

## Installation

From the repository root, make the dispatcher executable:

```bash
chmod +x scripts/backend/cron_runner.sh
mkdir -p var/log/cron var/lock
```

Set database and runtime variables in the service account environment rather than embedding secrets in crontab:

```bash
export SGW_DB_NAME=sgw
export SGW_DB_USER=sgw
export SGW_DB_PASS='replace-with-local-secret'
export PHP_BIN=/usr/bin/php
export CRON_TIMEOUT_SECONDS=900
```

## Recommended crontab

Replace `/srv/universe-civilization` with the absolute repository path and install these entries with `crontab -e`:

```cron
*/5 * * * * cd /srv/universe-civilization && scripts/backend/cron_runner.sh game_tick >> var/log/cron/dispatcher.log 2>&1
*/15 * * * * cd /srv/universe-civilization && scripts/backend/cron_runner.sh healthcheck >> var/log/cron/dispatcher.log 2>&1
0 * * * * cd /srv/universe-civilization && scripts/backend/cron_runner.sh reports >> var/log/cron/dispatcher.log 2>&1
15 2 * * * cd /srv/universe-civilization && scripts/backend/cron_runner.sh backup >> var/log/cron/dispatcher.log 2>&1
```

Run migrations separately during a controlled deployment rather than automatically on every five-minute tick:

```bash
scripts/backend/cron_runner.sh migrate
```

## Dry-run and manual operation

Use dry-run mode to verify command selection and lock paths without changing the database:

```bash
scripts/backend/cron_runner.sh game_tick --dry-run
scripts/backend/cron_runner.sh healthcheck --dry-run
```

Run a production-equivalent tick manually when investigating a report:

```bash
scripts/backend/cron_runner.sh game_tick
```

## Locks, logs, and failures

Each job uses a separate advisory file lock under `var/lock`. If the same job is already running, the second invocation exits successfully with a `skipped` record rather than executing concurrently. Logs are written under `var/log/cron/<job>.log` and include UTC start, completion, duration, dry-run, and failure status lines.

The dispatcher applies a bounded timeout through `CRON_TIMEOUT_SECONDS`. A failed job returns the underlying nonzero exit code so system cron, Supervisor, or an external monitor can alert an operator. Inspect the matching job log first, then run the job manually with the same environment. Do not remove locks while a worker may still be alive; verify the process has stopped before clearing stale lock files.

## Monitoring checks

A minimal monitoring command is:

```bash
scripts/backend/cron_runner.sh healthcheck
printf 'last game tick: '; tail -1 var/log/cron/game_tick.log
```

Backups and logs must be stored outside the public web root. Rotate logs with the host’s standard logrotate configuration and keep database backup retention appropriate for the deployment.

## Production installation checklist

Create a dedicated service account and ensure it can read the application and write only to the runtime log, lock, backup, and export directories. Store database variables in a protected environment file such as `/etc/universe-civilization/game.env` with mode `600`, then load it from a wrapper or the crontab shell command:

```bash
sudo install -o game -g game -m 600 /dev/null /etc/universe-civilization/game.env
sudo sh -c 'cat > /etc/universe-civilization/game.env <<EOF
SGW_DB_NAME=sgw
SGW_DB_USER=sgw
SGW_DB_PASS=replace-with-secret
PHP_BIN=/usr/bin/php
CRON_TIMEOUT_SECONDS=900
EOF'
```

Test the environment and dispatcher before installing cron:

```bash
cd /srv/universe-civilization
set -a; . /etc/universe-civilization/game.env; set +a
scripts/backend/cron_runner.sh game_tick --dry-run
scripts/backend/cron_runner.sh healthcheck
```

Install the crontab as the application service account:

```bash
sudo -u game crontab -e
```

Use the following entries, including the environment file and absolute paths:

```cron
SHELL=/bin/bash
*/5 * * * * . /etc/universe-civilization/game.env; cd /srv/universe-civilization && scripts/backend/cron_runner.sh game_tick >> var/log/cron/dispatcher.log 2>&1
*/15 * * * * . /etc/universe-civilization/game.env; cd /srv/universe-civilization && scripts/backend/cron_runner.sh healthcheck >> var/log/cron/dispatcher.log 2>&1
0 * * * * . /etc/universe-civilization/game.env; cd /srv/universe-civilization && scripts/backend/cron_runner.sh reports >> var/log/cron/dispatcher.log 2>&1
15 2 * * * . /etc/universe-civilization/game.env; cd /srv/universe-civilization && scripts/backend/cron_runner.sh backup >> var/log/cron/dispatcher.log 2>&1
```

## Checking cron execution and logs

Verify the installed schedule and service process:

```bash
sudo -u game crontab -l
pgrep -af cron
```

Inspect dispatcher and job logs:

```bash
tail -f /srv/universe-civilization/var/log/cron/dispatcher.log
tail -f /srv/universe-civilization/var/log/cron/game_tick.log
grep -E 'status=(failed|skipped)' /srv/universe-civilization/var/log/cron/*.log
```

On systemd-based distributions, inspect the cron daemon journal. The service name may be `cron` or `crond`:

```bash
sudo journalctl -u cron --since '1 hour ago'
sudo journalctl -u crond --since '1 hour ago'
```

A healthy job log contains `status=started` followed by `status=completed`. A `status=skipped reason=already-running` line is normal when overlapping invocations are prevented by the lock. A `status=failed` line requires reviewing the preceding command output and the database/PHP environment.

For log rotation, configure the host’s logrotate facility to rotate `var/log/cron/*.log` daily, retain an appropriate number of compressed copies, and signal no process because the dispatcher opens the log for each invocation. Never expose the logs through the public document root.
