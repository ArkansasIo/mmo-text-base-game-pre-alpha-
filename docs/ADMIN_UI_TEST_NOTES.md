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
