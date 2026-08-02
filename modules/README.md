# modules/

Gameplay modules rendered through the legacy `sendData(...)` pattern.

## Notes

- Each PHP file usually represents a page/panel/action endpoint.
- `pages.php` is the high-level command hub for many subsystems.
- Keep module output HTML compatible with existing template shell.

After modifying module routing, verify navigation from `index.php` UI menus.
