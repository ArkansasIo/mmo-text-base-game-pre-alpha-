# Root Administrator Email System

The game now provides a root administrator email identity and an in-game Email Network. The default root sender address is `root@universecivilization.game`; this is an in-game identity and is not automatically a registered external mailbox.

Use the protected CLI provisioner to create or reset the unified root administrator account. It creates the `admin_users` root record and the matching `users` record used by the normal Civilization Login flow:

```bash
SGW_ROOT_USERNAME=root \
SGW_ROOT_EMAIL=root@universecivilization.game \
SGW_ROOT_PASSWORD='use-a-unique-16-character-password' \
php scripts/backend/create_root_email_admin.php
```

The player-facing Email Network supports inbox, sent mail, compose, read, delete, and player-to-player delivery. The protected administrator center at `/admin/email.php` lets operator-level administrators queue system messages to player UIDs. All administrative sends are CSRF-protected and audited.

Queued system messages are processed by `scripts/backend/email_tick.php`. The default `GAME_MAIL_TRANSPORT=log` mode records the delivery attempt without contacting an external mail server. To use the host’s PHP mail transport, set `GAME_MAIL_TRANSPORT=mail` only after configuring a verified sender and domain with the hosting provider. Delivery attempts are recorded in `game_email_delivery_log`.

Migration 46 creates `game_email_messages`, `game_email_delivery_log`, and the root email settings. The locked cron dispatcher exposes the `email_tick` job. External SMTP credentials are intentionally not stored in the repository.
