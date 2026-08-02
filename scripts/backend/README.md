# scripts/backend/

Shell automation for backend maintenance.

## Key Scripts

- `db_init.sh`: initialize DB and import baseline schema.
- `db_migrate.sh`: apply layered SQL migration files.
- `db_backup.sh`: database backup operation.
- `healthcheck.sh`: runtime/database readiness checks.
- `export_reports.sh`: generate SQL/CSV reporting exports.

Supplementary schedule notes: [cron_jobs.md](cron_jobs.md)
