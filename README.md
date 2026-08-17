# test game

Open-source PHP/MySQL browser strategy game inspired by classic turn-based empire games and the Stargate universe.

## Project Guide

This repository now includes a full folder-level documentation layout.

- Start here: [docs/README.md](docs/README.md)
- Full layout map: [docs/PROJECT_STRUCTURE.md](docs/PROJECT_STRUCTURE.md)
- Backend setup: [BACKEND_SETUP.md](BACKEND_SETUP.md)
- Legacy readme retained: [README](README)

## Quick Start

1. Initialize database:

```bash
mysql -u root -p < database/sql/01_create_database.sql
mysql -u sgw -psgwpass sgw < game.sql
./scripts/backend/db_migrate.sh
```

2. Validate backend:

```bash
./scripts/backend/healthcheck.sh
```

3. Run locally:

```bash
/usr/bin/php -S 0.0.0.0:8080
```

4. Open:

- http://127.0.0.1:8080/index.php
