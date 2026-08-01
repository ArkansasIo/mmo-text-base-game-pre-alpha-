# Backend Setup and Operations

This document covers database provisioning, backend migrations, health checks, backups, and report exports.

## Prerequisites

- MariaDB or MySQL running
- CLI tools available: mysql, mysqldump
- PHP binary with mysqli support (recommended: /usr/bin/php)

## Configuration Sources

Runtime values are loaded in this order:

1. Defaults in config.php
2. Environment variables
3. Optional config.local.php override

Templates:

- .env.example
- config.local.php.example

## Database Setup Paths

### Option A: Full Bootstrap (first-time setup)

```bash
chmod +x scripts/backend/*.sh
./scripts/backend/db_init.sh
```

What it does:

1. Creates database/user grants (database/sql/01_create_database.sql)
2. Imports core legacy schema from game.sql
3. Applies backend tables
4. Applies reporting views
5. Seeds backend defaults

### Option B: Existing Core DB, Apply Backend Layer Only

```bash
chmod +x scripts/backend/*.sh
./scripts/backend/db_migrate.sh
```

## Validation

Run backend health checks:

```bash
./scripts/backend/healthcheck.sh
```

This checks:

- Core game tables
- Backend operational tables
- Reporting views

## Reporting and Excel Exports

Generate SQL snapshots and CSV reports:

```bash
./scripts/backend/export_reports.sh
```

Output folders:

- exports/sql
- exports/excel

CSV outputs are Excel-compatible.

## Backups

Create compressed SQL backup:

```bash
./scripts/backend/db_backup.sh
```

Output folder:

- backups/sql

## Script Reference

- scripts/backend/db_init.sh: one-shot full setup
- scripts/backend/db_migrate.sh: backend SQL migration batch
- scripts/backend/db_backup.sh: DB dump + gzip
- scripts/backend/healthcheck.sh: readiness validation
- scripts/backend/export_reports.sh: SQL/CSV report generation

## Runtime Troubleshooting

### mysqli class not found

Use system PHP explicitly:

```bash
PHP_BIN=/usr/bin/php ./scripts/backend/healthcheck.sh
PHP_BIN=/usr/bin/php ./scripts/backend/export_reports.sh
```

### Access denied for DB user

- Verify SGW_DB_* values in environment or config.local.php
- Re-run database/sql/01_create_database.sql with admin permissions

### Views or tables missing

Re-run:

```bash
./scripts/backend/db_migrate.sh
./scripts/backend/healthcheck.sh
```

