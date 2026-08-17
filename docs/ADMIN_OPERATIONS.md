# Administrator Operations Guide

## Purpose

The administrator control plane is the server-side operations interface for Universe Civilization: Empire at Wars. It manages game settings, maintenance, economy, player access, power, combat tuning, operations jobs, administrator accounts, metrics, and audit history.

## Access and roles

Open `/admin/` through a restricted deployment route. Administrator authentication uses dedicated administrator tables and sessions, separate from player login. Sessions expire after twelve hours, and state-changing actions require authorization and CSRF validation.

| Role | Scope |
|---|---|
| `moderator` | Review supported operational data and perform approved moderation actions |
| `operator` | Manage game settings, players, economy, power, combat tuning, maintenance jobs, and reports |
| `superadmin` | Operator capabilities plus administrator-account management and security controls |

Permissions are checked on the server. Hiding a button in the browser does not grant or remove authorization.

## Game controls

Operators can manage login requirement, registration, maintenance mode, combat and expedition availability, turn interval, and maintenance messaging. Use maintenance mode for planned downtime instead of disabling authentication on a public deployment.

## Economy and power

Economy controls include bounded resource grants, player reserves, production multipliers, and economy refresh jobs. Power controls include the power-grid multiplier and recalculation jobs. Resource operations should be accompanied by an audit reason and should remain within the configured administrator ceilings.

## Combat and operations

Combat tuning includes fleet speed, damage, defense repair, and combat availability. Operations jobs include power recalculation, economy refresh, universe index rebuild, and combat-integrity repair. Queue jobs during maintenance windows when possible and review job results in the audit trail.

## Player governance

Player governance allows an operator to review access state and perform bounded resource or account-state changes. Confirm the target UID before any action. Do not use direct database edits when an audited control-plane operation exists.

## Security operations

Superadmins can create administrator accounts, enable or disable other administrators, clean expired sessions, and review security events. Never disable the current administrator session accidentally, and rotate all development credentials before production deployment.

## Audit and recovery

The control plane records state-changing actions, actor identity, target, operation type, and result. Before major economy, combat, or schema changes, create a database backup and record the release identifier. Use the documented rollback plan rather than manually reversing uncertain changes.

## Operator checklist

Before a release, run the backend health check, syntax validation, migration checks, progression tests, public title-page test, player login test, protected-admin test, and representative module requests. After release, confirm login, account settings, power, communications, combat, wave campaigns, sabotage, and audit history.
