# Administrator Account Details

## Unified login

Administrator accounts use the same **Civilization Login** form as player accounts. The public title page does not expose a separate administrator login link. After a shared administrator account is provisioned, open the normal game login page with its username or email and password. The protected control plane remains available at `/admin/` and the root email center is at `/admin/email.php`.

## Root account provisioning

Create or reset the root administrator only from the server shell. Never commit the password to Git or place it in a public web directory.

```bash
SGW_ROOT_USERNAME=root \
SGW_ROOT_EMAIL=root@universecivilization.game \
SGW_ROOT_PASSWORD='use-a-unique-16-character-password' \
php scripts/backend/create_root_email_admin.php
```

The command creates an active `superadmin` record in `admin_users` and a matching shared `users` record. Administrative privileges are still decided by the server-side `admin_users.role` value, not by the legacy player access flag alone.

## Roles

| Role | Purpose |
|---|---|
| `moderator` | Review and moderate supported administrative views. |
| `operator` | Use controlled game operations, player-resource tools, queued jobs, and root email delivery. |
| `superadmin` | Manage administrator accounts and all operator controls. |

## Root email identity

The default in-game root sender is `root@universecivilization.game`. This address is an application identity; it is not an external mailbox until a real SMTP provider and verified sending domain are configured.

## SMTP configuration

Copy the relevant variables from `.env.example` into the deployment environment, replacing every placeholder:

```bash
GAME_MAIL_TRANSPORT=smtp
SMTP_HOST=smtp.provider.example
SMTP_PORT=587
SMTP_USERNAME=provided-smtp-username
SMTP_PASSWORD='provided-smtp-password'
SMTP_FROM=root@your-verified-domain.example
SMTP_ENCRYPTION=tls
SMTP_TIMEOUT=15
```

The `email_tick` worker reads these values without storing them in the repository. The worker records every attempt in `game_email_delivery_log`. Keep `GAME_MAIL_TRANSPORT=log` until the provider credentials and sender domain are verified; use `smtp` only after that setup is complete.

## Security requirements

Use a unique password of at least 16 characters for the root account. Rotate the password through the provisioning command, restrict shell and database access, keep SMTP credentials outside Git, and review the admin audit trail after root-email operations. Do not publish real credentials in README files, screenshots, tickets, or chat messages.
