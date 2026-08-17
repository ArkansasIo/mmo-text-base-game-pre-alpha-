# Balance Patch Proposal: Progression Caps

**Patch:** 0.9.1 — Frontier Progression Guardrails  
**Status:** Proposed and partially implemented  
**Scope:** Replace uncapped progression with predictable server-side maximum levels while preserving existing player progress.

## Goals

This patch establishes a single server-authoritative cap policy for infrastructure, research, hyperspace, power, combat technology, combat sites, combat installations, military rank, unit veterancy, and battle waves. It prevents runaway values, makes the economy measurable, and gives future content releases reserved level space.

## Cap schedule

| Progression family | Cap | Existing-build treatment |
|---|---:|---|
| Infrastructure | 30 | Clamp upgrade requests; preserve values already at or below 30 |
| Core research | 30 | Clamp all legacy technology upgrades |
| Stargate and hyperspace | 25 | Clamp gateway and route technology upgrades |
| Power reactor, storage, grid, efficiency | 25 | Clamp power upgrade requests |
| Combat technology | 30 | Clamp weapons, shields, targeting, armor, reactor, and command research |
| Combat site command and sensors | 25 | Clamp site progression |
| Combat installations | 20 | Clamp weapon, shield, and structure installation levels |
| Military rank | 50 | Clamp rank promotion calculation |
| Unit veterancy | 10 | Reserved for the persistent veterancy feature |
| Battle waves | 8 | Existing hard clamp retained |

Fixed content ranges remain unchanged: universe taxonomy A–Z, world size 1–9, five RTS theaters, seven mission types, sixteen tactical orders, six fleet archetypes, seven sabotage operations, and three interface density modes.

## Compatibility policy

The server must never silently delete progress. Values already above a newly introduced cap should be marked `over_cap` for migration review, while new upgrades must be blocked. For the current pre-alpha database, a controlled normalization migration may clamp values above the cap after a backup and audit export. The first rollout should report over-cap values before changing them.

The shared `ProgressionCaps` policy is the source of truth for PHP validation. Database migrations should mirror the values for reporting and future administrator overrides. Client-side maximum attributes are informative only and must not replace server checks.

## Economy and pacing impact

The cap patch reduces late-game runaway production and combat scaling. Costs should continue to rise by level until the cap, while cap-level records should show a clear `MAXIMUM REACHED` state. The initial patch should not retroactively remove resources. Instead, it should prevent additional upgrades and use the admin audit trail for any normalization.

Recommended follow-up balance review points are 24 hours, 7 days, and 30 days after rollout. Review resource concentration, average technology level, highest combat-site level, wave completion rate, player retention, and the number of blocked cap attempts.

## Implementation plan

1. Deploy the shared server-side cap class.
2. Add cap enforcement to every upgrade endpoint and construction path.
3. Add a migration that records cap values and reports over-cap records.
4. Add automated boundary tests and run them in CI and deployment checks.
5. Add admin metrics for blocked upgrades and over-cap records.
6. Announce the cap schedule in release notes.
7. Normalize over-cap data only after backup and operator approval.

## Rollback plan

Rollback requires restoring the previous application release and, if data normalization has already occurred, restoring the pre-patch database backup. The cap policy should be feature-flagged in a production deployment so validation can be disabled without removing audit records.

## Acceptance criteria

The patch is ready when every upgrade route uses the shared policy, values above each cap cannot be increased, values at the cap remain playable, invalid negative levels are rejected or normalized, migration checks pass, and automated tests pass for every cap and fixed range.
