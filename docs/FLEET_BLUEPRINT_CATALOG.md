# Fleet Blueprint Catalog

## Overview

Universe Civilization: Empire At Wars now includes **90 fleet blueprints** distributed across the complete **A–Z ship-class taxonomy**. The catalog is implemented in `base/FleetBlueprintCatalog.class.php` and exposed through `FleetPolicy::BLUEPRINTS`, so existing shipyard construction, fleet deployment, PvP dispatch, and battle-resolution code can use the new designs without changing their public APIs.

The player-facing catalog is available through **Warfare Systems → 90-Blueprint Catalog**. It supports filtering by class, role, and progression tier and displays the ship’s combat profile, industrial cost, fitting requirements, and descriptive information.

## Blueprint fields

| Field group | Fields | Meaning |
|---|---|---|
| Identity | `name`, `class_code`, `class_name`, `role`, `tier`, `description` | Player-facing classification and design information |
| Combat | `attack`, `defense`, `hull`, `shield` | Direct combat power, mitigation, structural durability, and energy protection |
| Mobility and logistics | `speed`, `warp`, `cargo`, `capacity`, `evasion` | Strategic travel, fleet movement, cargo volume, and survivability in transit |
| Fitting | `high_slots`, `medium_slots`, `low_slots`, `power_grid`, `sensor`, `capacitor`, `crew` | Module slot groups and fitting constraints enforced during fleet dispatch |
| Specialist systems | `armor`, `signature`, `drone_bandwidth`, `salvage` | Secondary attributes for mitigation, detection exposure, drone operation, and recovery |
| Industrial | `metal`, `crystal`, `energy`, `build_minutes` | Per-unit resource costs and construction time |

## A–Z taxonomy

The classes represent broad strategic identities rather than a direct copy of another game’s labels. Assault hulls emphasize damage, Bastions hold defensive lines, Couriers move quickly, Dreadnoughts project capital power, Expedition and Recon hulls support discovery and intelligence, Haulers and Miners support logistics, Jammer and Quantum designs specialize in electronic or advanced mobility systems, and Zenith designs represent the highest progression band.

Every class has three or four role variants: **Scout**, **Escort**, **Line**, or **Command**. The role changes the stat emphasis while the class changes the strategic specialization. This creates a catalog that supports combined-arms fleets instead of a single linear upgrade path.

## Progression and balancing

Blueprint tiers rise gradually across the alphabet, with higher tiers increasing industrial cost, fitting requirements, and strategic specialization. A higher-tier hull is not automatically superior in every dimension. For example, Couriers and Interceptors retain mobility advantages, Haulers retain cargo advantages, and Jammer or Recon hulls retain specialist sensor advantages even when slower capital ships have higher raw attack or defense.

The existing `FleetPolicy::fleetPower()` method continues to use `attack`, `defense`, and `capacity` for base deployment and PvP calculations. `FleetPolicy::fittedPower()` adds validated module effects, while `FleetPolicy::validateFitting()` enforces each hull's high, medium, and low slot counts together with power-grid, CPU, and capacitor limits. Fitted loadouts are persisted with fleet deployments and PvP battles so combat reports can be reproduced from the dispatched configuration.

## Module fitting

The fitting policy defines three module groups. **High** slots accept weapon and siege systems such as Railgun Arrays, Missile Batteries, and Siege Lances. **Medium** slots accept Shield Hardeners, Warp Scramblers, and Sensor Dampeners. **Low** slots accept Cargo Optimizers, Reinforced Bulkheads, and Nanite Repair Bays. A module consumes its assigned slot group and also consumes power grid, CPU, and capacitor budget. Fittings that exceed any limit are rejected before a fleet leaves its origin world.

The fitting JSON format is intentionally compact and is shared by fleet deployments and PvP launches. For example:

```json
{"railgun_array":1,"shield_hardener":1,"cargo_optimizer":1}
```

## Validation

Run the catalog regression suite with:

```bash
php tests/fleet_blueprints_test.php
php tests/module_fitting_test.php
```

The test verifies the exact 90-entry count, unique keys, complete A–Z coverage, compatibility aliases for the original `scout`, `frigate`, `destroyer`, and `carrier` keys, positive stats and costs, required field completeness, and navigation access.
