# Admin UI Verification Notes

The local PHP server was available at `http://127.0.0.1:8080`.

Opening `/admin/` rendered the industrial-blue **Administrator Control Plane** login gate with username and password fields, an Enter Control Plane action, and a return link to the public title page.

Opening `/admin/email.php` while unauthenticated redirected to `/`, confirming that the Root Email Network is protected by administrator authentication.

The public title page rendered the industrial-blue command briefing, login controls, backend status indicator, and game-system panels. No separate public Administrator Console link was visible.

A logged-in dashboard view could not be opened in the browser session because no administrator credentials were entered. Backend and source-level tests cover the protected admin route, role check, CSRF guard, root-email queue, audit event, and dashboard Root Email link.

## Local QA administrator verification

On 2026-08-19, a local-only `qa_admin` account was provisioned with role `superadmin` using the environment-protected administrator provisioner. The browser entered the protected `/admin/` gate and, after the administrator credential check, rendered the Admin Control Plane as `qa_admin · superadmin`.

The control plane displayed Game Logic and Simulation controls, Economy Controls, Player Governance, server-operation queue controls, administrator provisioning, player directory, administrator accounts, queued operations, and the audit trail. The new administrator appeared as an active protected superadmin account.

The protected `/admin/email.php` page rendered successfully in the authenticated session as the Root Email Network. It exposed targeted system email with recipient UID, sender, subject, message, attachment type, currency/item/equipment key, and quantity; global server announcement with the same attachment options; recent broadcast records; the system email queue; and a return link to the control plane.

## Root browser UI verification

After clearing the previous QA sessions, the browser entered the public unified login flow with the repaired root credentials and reached `/admin/`. The dashboard explicitly displayed `Signed in as root · superadmin`, with an active Root Email link and Sign out control. The dashboard showed Players, Admins, Power Nodes, Combat Sites, and Queued Jobs status cards, followed by Game Logic and Simulation controls.

No game setting, player resource, access level, queued operation, or administrator account was changed during this verification.

The root dashboard was reviewed through the lower sections without submitting any forms. Economy Controls expose Naqadah grants and reserve editing for Metal, Crystal, Deuterium, and Energy. Player Governance exposes UID access-level updates and expired admin-session cleanup. Server Operations Queue exposes Power Grid, Economy Metrics, Universe Index, and Combat Integrity jobs with optional target UID. Create Administrator exposes username, email, role, and password fields.

The Player Directory rendered UID, username, email, access level, and last-login information. Administrator Accounts rendered ID, username, role, active status, last login, and protected disable actions. Queued Operations and the Audit Trail were also present. No administrative mutation was performed.

## Queue simulation and test-account update

Using the root superadmin browser session, a local `refresh_economy_metrics` operation targeting UID 6 (`tessssssst`) was queued through the Server Operations Queue. The panel displayed `Administrative job queued for server processing`, increased Queued Jobs to 1, showed job ID 1 with target 6 and status `queued`, and recorded an audit event by actor 4 with the requested job type and target UID.

The Player Governance form then set UID 6 (`tessssssst`) to access level `1`, shown in the panel as `Legacy admin flag`, and displayed `Player access updated.` The Player Directory state and direct database verification reported `access=1`. The latest audit event is `update_player_access` in `player_control` with `{"uid":6,"alevel":1}`.

The queue verification reported job ID 1 targeting UID 6 with job type `refresh_economy_metrics` and status `queued`. No backend worker in `scripts/backend` currently references `admin_operation_jobs` or the operation type, so the simulation successfully exercised queue creation and audit logging but did not execute the maintenance operation. The job remains safely queued for a future queue-worker implementation.

## Test account rename

The confirmed local test account was renamed from `tessssssst` to `admintest` at the same UID `6`. Its email, password, access level `1`, and game state were preserved. The new username successfully authenticated through the public player login flow, and the audit trail recorded `rename_player_username` in `player_control` with the old and new names.

## Admintest login verification

After clearing the prior session, the renamed `admintest` account authenticated successfully through the public login flow. The browser rendered the authenticated game shell with Logout, the condensed quick-access rail, command modules, resource dashboard, tactical display, and Empire Command page. The session remained active through the full authenticated dashboard render.

The exact audit verification returned actor ID `4`, action `rename_player_username`, module `player_control`, details `{"uid":6,"from":"tessssssst","to":"admintest"}`, created at `2026-08-19 14:00:53`.

## Unified dedicated administrator control plane

`admintest` was provisioned as a dedicated active `superadmin` in `admin_users` alongside the existing active `root` and `admin` superadmins. The shared `/admin/` control plane recognizes the admintest administrator session and renders the full dashboard. The provisioner was corrected to update existing shared player identities by username instead of creating duplicate legacy users. The duplicate admintest row with no dependent game-state records was removed safely; canonical UID 6 remains with the preserved game state and legacy access flag.

The refreshed browser dashboard showed `Signed in as admintest · superadmin`, Players 9, Admins 4, and the complete game logic, economy, governance, operations, administrator, directory, queue, and audit sections. Authentication, admin/email, email-system, PHP syntax, and backend healthcheck validations all passed.

## Unified session and queue simulation update

Clean cookie-based tests passed for both `root` and `admintest` control-plane logins. The corrected queue binding stored `recalculate_power` as a string for job ID 2 targeting UID 6, and the admintest session recorded a new `admin_login` actor ID 6 audit event. The earlier malformed job ID 1 remains queued with legacy type `0` from the pre-fix test and is not used by the corrected simulation.

The corrected queue simulation now contains job ID 2 `recalculate_power` and job ID 3 `refresh_economy_metrics`, both targeting UID 6 and both stored with the correct string operation names. Admintest actor ID 6 generated audit events for both queue submissions.

The third corrected queue operation, `rebuild_universe_index`, was accepted as job ID 4 for target UID 6 and recorded by admintest actor ID 6 in the operations audit trail.

The fourth corrected queue operation, `repair_combat_integrity`, was accepted as job ID 5 for target UID 6 and recorded by admintest actor ID 6. All four supported operation types have now been exercised through the control plane. The only remaining anomaly is job ID 1 with type `0`, created before the prepared-statement binding fix.

## Complete control-plane verification

Clean session tests passed for `root` and `admintest`, each following the protected admin login redirect and rendering its expected dashboard identity. The four supported queue operations were exercised against local UID 6: `recalculate_power`, `refresh_economy_metrics`, `rebuild_universe_index`, and `repair_combat_integrity`. All four were stored with valid string operation names and status `queued`. The pre-fix job ID 1 was corrected from operation type `0` to `refresh_economy_metrics` and recorded as `repair_admin_job` by actor ID 6.

The final audit review contains root and admintest login events, the `update_player_access` event, the `rename_player_username` event, all four corrected queue submissions, and the legacy queue repair. PHP syntax, authentication, admin/email, and backend healthcheck validations passed.
