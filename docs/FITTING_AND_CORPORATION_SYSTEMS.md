# Fitting Simulator and Corporation Systems

## Visual fitting simulator

The Visual Fitting Simulator is available from **Warfare Systems → Visual Fitting Simulator**. It loads all 90 fleet blueprints and exposes their high-, medium-, and low-power slot profiles. Players select a hull, adjust module quantities, and receive an immediate fitting report.

The simulator reports slot usage, power-grid usage, CPU usage, capacitor usage, attack, defense, and fleet capacity. Invalid layouts are highlighted when slot groups or fitting resources exceed the selected hull. The simulator uses the same blueprint and module metadata as the server-side `ModuleFittingPolicy`, so it is intended for planning before construction or deployment.

## Corporation network

The Corporation Fleet Network is a player organization layer for cooperative research and fleet operations. A corporation supports up to 150 members and uses the following roles:

| Role | Authority |
|---|---|
| Member | Contribute to shared pools and join forming operations |
| Researcher | Advance shared corporation research |
| Operator | Assemble and launch cooperative fleet operations |
| Officer | Add members and manage corporation activity |
| Director | Full corporation authority |

Corporations have separate shared research and fleet pools. Contributions are removed from the player’s Naquadah balance inside a transaction and recorded in `corporation_contributions`.

## Shared research

Corporation research covers Fleet Doctrine, Industrial Logistics, Warp Coordination, and Shield Network. Research levels are paid from the shared research pool and stored in `corporation_research`, allowing members to benefit from a common progression investment.

## Cooperative operations

Operators can assemble Joint Defense, Expedition, Coordinated Strike, or Territory Relief operations. A forming operation stores its target and creator fleet. Other members join with their own validated fleet and fitting JSON. When launched, the operation aggregates member attack and defense power, records a timed resolution, and sends player notifications to participating members.

All operation fleet inputs pass through the existing server-side fitting policy. The client simulator is therefore a planning tool, not an authority bypass.

## Migration and tests

Apply the ordered migration runner to create the corporation tables:

```bash
scripts/backend/db_migrate.sh
```

The feature migration is:

```text
database/sql/37_corporations_cooperative_ops.sql
```

Run focused checks with:

```bash
php tests/fitting_corporation_test.php
```
