# Research Blueprint Integration

## Overview

The 90-ship blueprint catalog is now part of the player research progression. The catalog remains visible for planning, but construction access is controlled by `player_blueprint_research`. A commander must research a blueprint before the shipyard can construct it or the fleet system can treat it as an unlocked design.

## Research prerequisites

Blueprints use their existing progression tier to derive infrastructure requirements. The starter tier requires no research infrastructure. Higher tiers require increasing levels of Research Campus, Simulation Core, and Data Vault.

| Research system | Blueprint effect |
|---|---|
| Research Campus | Required for the main progression ladder |
| Simulation Core | Required for advanced hull modeling and higher-tier designs |
| Data Vault | Required for the upper research bands |
| Blueprint Research console | Displays requirements, discovery costs, status, and unlock actions |

Unlocking a blueprint consumes Naquadah, metal, crystal, deuterium, and energy. Settlement occurs inside a transaction and records the unlock source as `research`.

## Access boundaries

The shipyard rejects construction requests for blueprints not present as `unlocked` in `player_blueprint_research`. The Blueprint Catalog shows every design for planning but labels locked designs. The Blueprint Research console is the authoritative place to discover a design.

Blueprint licenses acquired through the Player Exchange are also written to `player_blueprint_research` with source `market`. Direct trades use the same path, so a blueprint received from another commander becomes available after the trade transaction commits. Legacy owned blueprints are migrated with source `legacy`, while each commander’s starter Scout is marked with source `starter`.

## Migration

Apply the ordered migration:

```bash
scripts/backend/db_migrate.sh
```

The research integration is provided by:

```text
database/sql/35_research_blueprint_unlocks.sql
base/ResearchBlueprintPolicy.class.php
modules/blueprint_research.php
```

## Validation

Run the focused integration suite with:

```bash
php tests/research_blueprint_test.php
```

The suite verifies tier scaling, prerequisite enforcement, discovery costs, research-console actions, shipyard gating, catalog status, market access propagation, migration behavior, and navigation access.
