<?php
// Basic backend healthcheck: DB connection, core tables, backend tables, reporting views.

require_once dirname(__DIR__, 2) . '/config.php';

if (!extension_loaded('mysqli') || !class_exists('mysqli')) {
    fwrite(STDERR, "Healthcheck error: PHP mysqli extension is not available for this runtime." . PHP_EOL);
    fwrite(STDERR, "Install/enable mysqli for CLI or run with a PHP binary that includes mysqli." . PHP_EOL);
    exit(3);
}

if (!isset($conf) || !is_array($conf)) {
    fwrite(STDERR, "Healthcheck error: missing or invalid database configuration." . PHP_EOL);
    exit(3);
}

$requiredKeys = ['db_server', 'db_username', 'db_password', 'db_name'];
foreach ($requiredKeys as $key) {
    if (!array_key_exists($key, $conf)) {
        fwrite(STDERR, "Healthcheck error: missing config key '" . $key . "'." . PHP_EOL);
        exit(3);
    }
}

$mysqli = @new mysqli($conf['db_server'], $conf['db_username'], $conf['db_password'], $conf['db_name']);
if ($mysqli->connect_errno) {
    fwrite(STDERR, "DB connection failed: " . $mysqli->connect_error . PHP_EOL);
    exit(1);
}

$checks = [
    'tables' => ['users', 'userdata', 'bank', 'units', 'technology', 'power', 'app_settings', 'app_migrations', 'app_audit_log', 'app_server_jobs'],
    'views' => ['vw_player_core', 'vw_player_economy', 'vw_player_military'],
];

$allGood = true;

foreach ($checks['tables'] as $table) {
    $res = $mysqli->query("SHOW TABLES LIKE '" . $mysqli->real_escape_string($table) . "'");
    $exists = ($res && $res->num_rows > 0);
    echo sprintf("[table] %-25s %s\n", $table, $exists ? 'OK' : 'MISSING');
    if (!$exists) {
        $allGood = false;
    }
    if ($res) {
        $res->free();
    }
}

foreach ($checks['views'] as $view) {
    $res = $mysqli->query("SHOW FULL TABLES WHERE Table_type='VIEW' AND Tables_in_" . $conf['db_name'] . "='" . $mysqli->real_escape_string($view) . "'");
    $exists = ($res && $res->num_rows > 0);
    echo sprintf("[view ] %-25s %s\n", $view, $exists ? 'OK' : 'MISSING');
    if (!$exists) {
        $allGood = false;
    }
    if ($res) {
        $res->free();
    }
}

$mysqli->close();

if (!$allGood) {
    exit(2);
}

echo "Backend healthcheck passed." . PHP_EOL;
