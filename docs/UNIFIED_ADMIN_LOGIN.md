# Unified Administrator Login

Administrator accounts now use the same public **Civilization Login** entry as player accounts. The public title page no longer exposes a separate Administrator Console login link.

When an administrator is provisioned, the provisioning script creates or updates the corresponding legacy `users` account with the same username, email, and password-derived login value. The administrator remains separately represented in `admin_users`, where the server-side role (`moderator`, `operator`, or `superadmin`) is enforced.

After the shared player login succeeds, the admin control plane recognizes the authenticated session only when the logged-in user matches an active `admin_users` record by username or email. Ordinary players are not granted administrative access. Existing admin-session tokens and role checks remain supported for backward compatibility.

To provision or reset a unified administrator account, use a strong password through the environment-protected command:

```bash
SGW_ADMIN_USERNAME=admin \
SGW_ADMIN_EMAIL=admin@example.com \
SGW_ADMIN_PASSWORD='use-a-unique-12-character-password' \
SGW_ADMIN_ROLE=superadmin \
php scripts/backend/create_admin.php
```

The public login remains at `/`, while the protected control plane remains at `/admin/`. The admin URL is no longer advertised on the public title page, and access is still denied unless the server-side role check succeeds.
