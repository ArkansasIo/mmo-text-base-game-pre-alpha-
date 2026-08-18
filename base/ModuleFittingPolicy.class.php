<?php
final class ModuleFittingPolicy
{
    public const SLOTS = ['high','medium','low'];
    public const MODULES = [
        'railgun_array' => ['name'=>'Railgun Array','slot'=>'high','power_grid'=>18,'cpu'=>12,'capacitor'=>8,'attack'=>14,'defense'=>0,'capacity'=>0],
        'missile_battery' => ['name'=>'Missile Battery','slot'=>'high','power_grid'=>16,'cpu'=>10,'capacitor'=>10,'attack'=>11,'defense'=>0,'capacity'=>0],
        'siege_lance' => ['name'=>'Siege Lance','slot'=>'high','power_grid'=>34,'cpu'=>18,'capacitor'=>22,'attack'=>31,'defense'=>0,'capacity'=>0],
        'shield_hardener' => ['name'=>'Shield Hardener','slot'=>'medium','power_grid'=>12,'cpu'=>15,'capacitor'=>16,'attack'=>0,'defense'=>15,'capacity'=>0],
        'warp_scrambler' => ['name'=>'Warp Scrambler','slot'=>'medium','power_grid'=>8,'cpu'=>20,'capacitor'=>12,'attack'=>4,'defense'=>3,'capacity'=>0],
        'sensor_dampener' => ['name'=>'Sensor Dampener','slot'=>'medium','power_grid'=>7,'cpu'=>25,'capacitor'=>10,'attack'=>0,'defense'=>5,'capacity'=>0],
        'cargo_optimizer' => ['name'=>'Cargo Optimizer','slot'=>'low','power_grid'=>3,'cpu'=>9,'capacitor'=>4,'attack'=>0,'defense'=>0,'capacity'=>70],
        'reinforced_bulkhead' => ['name'=>'Reinforced Bulkhead','slot'=>'low','power_grid'=>5,'cpu'=>6,'capacitor'=>3,'attack'=>0,'defense'=>10,'capacity'=>0],
        'nanite_repair_bay' => ['name'=>'Nanite Repair Bay','slot'=>'low','power_grid'=>9,'cpu'=>14,'capacitor'=>14,'attack'=>0,'defense'=>12,'capacity'=>0],
    ];

    public static function module(string $key): ?array { return self::MODULES[$key] ?? null; }
    public static function validModule(string $key): bool { return isset(self::MODULES[$key]); }

    public static function normalize(array $fitting): array
    {
        $normalized = [];
        foreach ($fitting as $key => $quantity) {
            $key = (string)$key;
            $quantity = max(0, min(32, (int)$quantity));
            if ($quantity > 0 && self::validModule($key)) $normalized[$key] = $quantity;
        }
        ksort($normalized);
        return $normalized;
    }

    public static function summarize(array $fitting): array
    {
        $fitting = self::normalize($fitting);
        $summary = ['high'=>0,'medium'=>0,'low'=>0,'power_grid'=>0,'cpu'=>0,'capacitor'=>0,'attack'=>0,'defense'=>0,'capacity'=>0];
        foreach ($fitting as $key => $quantity) {
            $module = self::MODULES[$key];
            $summary[$module['slot']] += $quantity;
            foreach (['power_grid','cpu','capacitor','attack','defense','capacity'] as $field) $summary[$field] += $module[$field] * $quantity;
        }
        return $summary;
    }

    public static function fit(array $blueprint, array $fitting): array
    {
        $summary = self::summarize($fitting);
        $slots = ['high'=>(int)($blueprint['high_slots'] ?? 0),'medium'=>(int)($blueprint['medium_slots'] ?? 0),'low'=>(int)($blueprint['low_slots'] ?? 0)];
        $limits = ['power_grid'=>(int)($blueprint['power_grid'] ?? 0),'cpu'=>(int)($blueprint['sensor'] ?? 0),'capacitor'=>(int)($blueprint['capacitor'] ?? 0)];
        $errors = [];
        foreach (self::SLOTS as $slot) if ($summary[$slot] > $slots[$slot]) $errors[] = ucfirst($slot).' slots exceeded ('.$summary[$slot].'/'.$slots[$slot].').';
        foreach ($limits as $field => $limit) if ($summary[$field] > $limit) $errors[] = strtoupper($field).' fitting exceeded ('.$summary[$field].'/'.$limit.').';
        return ['valid'=>!$errors,'errors'=>$errors,'summary'=>$summary,'slots'=>$slots,'limits'=>$limits];
    }

    public static function fittedPower(array $blueprint, array $fitting): array
    {
        $base = ['attack'=>(int)$blueprint['attack'],'defense'=>(int)$blueprint['defense'],'capacity'=>(int)$blueprint['capacity']];
        $summary = self::summarize($fitting);
        return ['attack'=>$base['attack']+$summary['attack'],'defense'=>$base['defense']+$summary['defense'],'capacity'=>$base['capacity']+$summary['capacity']];
    }
}
?>
