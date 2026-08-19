<?php
final class GameEmailPolicy
{
    public const ROOT_ADDRESS = 'root@universecivilization.game';
    public const MAX_SUBJECT = 190;
    public const MAX_BODY = 20000;
    public static function validAddress(string $address): bool { return (bool)filter_var($address, FILTER_VALIDATE_EMAIL); }
    public static function cleanSubject(string $subject): string { return trim(mb_substr($subject, 0, self::MAX_SUBJECT)); }
    public static function cleanBody(string $body): string { return trim(mb_substr($body, 0, self::MAX_BODY)); }
    public static function attachmentTypes(): array { return ['currency','item','equipment']; }
    public static function resourceKeys(): array { return ['metal','crystal','deuterium','energy','naquadah','antimatter','iridium','tritanium','plasma','exotic_matter','dark_matter']; }
    public static function validAttachment(string $type, string $resourceKey, string $assetKey, int $quantity): bool
    {
        if (!in_array($type, self::attachmentTypes(), true) || $quantity < 1 || $quantity > 1000000000) return false;
        if ($type === 'currency') return in_array($resourceKey, self::resourceKeys(), true) && $assetKey === '';
        return $assetKey !== '' && preg_match('/^[A-Za-z0-9._:-]{1,128}$/', $assetKey) === 1 && $resourceKey === '';
    }
    public static function attachmentLabel(array $attachment): string
    {
        $type=(string)($attachment['attachment_type']??'');$key=(string)($attachment['resource_key']?:$attachment['asset_key']??'');return strtoupper($type).': '.$key.' × '.number_format((int)($attachment['quantity']??0));
    }
}
?>
