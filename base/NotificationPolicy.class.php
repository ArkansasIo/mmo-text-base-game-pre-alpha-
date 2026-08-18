<?php
final class NotificationPolicy
{
    public static function ensureTable(mysqli $db): void
    {
        $db->query("CREATE TABLE IF NOT EXISTS player_notifications (notification_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,uid INT NOT NULL,category VARCHAR(40) NOT NULL,title VARCHAR(160) NOT NULL,body VARCHAR(500) NOT NULL,data_json LONGTEXT NULL,dedupe_key VARCHAR(160) NOT NULL,is_read TINYINT(1) NOT NULL DEFAULT 0,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY(notification_id),UNIQUE KEY uq_notification_dedupe(uid,dedupe_key),KEY idx_notification_feed(uid,is_read,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    public static function push(mysqli $db, int $uid, string $category, string $title, string $body, string $dedupeKey, array $data = []): bool
    {
        self::ensureTable($db); $category = substr(trim($category),0,40); $title = substr(trim($title),0,160); $body = substr(trim($body),0,500); $dedupeKey = substr(trim($dedupeKey),0,160); $json = $data ? json_encode($data, JSON_UNESCAPED_SLASHES) : null;
        $st = $db->prepare('INSERT IGNORE INTO player_notifications(uid,category,title,body,data_json,dedupe_key) VALUES(?,?,?,?,?,?)');
        if (!$st) return false; $st->bind_param('isssss',$uid,$category,$title,$body,$json,$dedupeKey); return $st->execute();
    }
}
?>
