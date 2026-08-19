<?php
final class GameEmailPolicy
{
    public const ROOT_ADDRESS = 'root@universecivilization.game';
    public const MAX_SUBJECT = 190;
    public const MAX_BODY = 20000;
    public static function validAddress(string $address): bool { return (bool)filter_var($address, FILTER_VALIDATE_EMAIL); }
    public static function cleanSubject(string $subject): string { return trim(mb_substr($subject, 0, self::MAX_SUBJECT)); }
    public static function cleanBody(string $body): string { return trim(mb_substr($body, 0, self::MAX_BODY)); }
}
?>
