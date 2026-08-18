<?php
final class PlayerActionCache
{
    private static array $local = [];

    public static function remember(string $key, int $ttl, callable $loader): mixed
    {
        $key = 'uc_eaw_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $key);
        if (function_exists('apcu_fetch')) {
            $success = false;
            $value = apcu_fetch($key, $success);
            if ($success) {
                return $value;
            }
            $value = $loader();
            if ($value !== null) {
                apcu_store($key, $value, max(1, min(300, $ttl)));
            }
            return $value;
        }
        $now = microtime(true);
        if (isset(self::$local[$key]) && self::$local[$key]['expires'] > $now) {
            return self::$local[$key]['value'];
        }
        $value = $loader();
        self::$local[$key] = ['expires' => $now + max(1, min(30, $ttl)), 'value' => $value];
        return $value;
    }

    public static function forget(string $key): void
    {
        $key = 'uc_eaw_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $key);
        unset(self::$local[$key]);
        if (function_exists('apcu_delete')) {
            apcu_delete($key);
        }
    }
}
?>
