# Missing Gameplay Functions Implemented

## Scope

This update addresses two high-impact gaps found during the repository audit: hyperspace mission types that previously resolved only as generic transit statuses, and fleet deployments that previously launched without reserving source inventory or transferring ships at arrival.

## Hyperspace mission outcomes

Hyperspace missions now have distinct server-side outcomes:

| Mission type | Settlement behavior |
|---|---|
| Transfer | Delivers a convoy reward based on tonnage and gate progression. |
| Expedition | Returns randomized industrial resources, enhanced by hyperspace progression. |
| Colonize | Creates a durable record in `hyperspace_colonies` with a generated frontier world name and founding population. |

Settlement is transactional and only processes an `enroute` record once. The UI now displays a human-readable outcome for each active or recently arrived transit.

## Fleet deployment state transitions

Shipyard construction now requires the selected planet to belong to the commander. Fleet deployment also requires an owned origin planet, validates each requested hull, locks source inventory rows, subtracts reserved ships atomically, and inserts the deployment in the same transaction.

When the game tick reaches the deployment ETA, it decodes the reserved fleet composition, credits the destination inventory, and changes the deployment to `arrived` in one transaction. This closes the previous state gap where a deployment could be marked arrived without moving ships.

## Database and tests

Migration `42_hyperspace_mission_outcomes.sql` adds transit outcome fields, creates `hyperspace_colonies`, and seeds the `frontier_founder` achievement. The migration is registered in `scripts/backend/db_migrate.sh`.

The focused hyperspace suite verifies seven mission and arrival behaviors. The fleet blueprint suite now also verifies owned-planet checks, transactional source reservation, and destination transfer logic.

## Remaining audit candidates

The repository still contains a legacy `fleetdock.php` path whose older spy, raid, patrol, and expedition mission flows are separate from the newer fleet and PvP systems. Those paths should be consolidated or given dedicated settlement policies in a subsequent expansion to avoid maintaining two competing fleet architectures.
