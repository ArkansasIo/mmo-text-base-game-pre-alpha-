<?php
/** Guild research tree and deterministic technology modifiers. */
final class GuildResearchPolicy
{
    public const MAX_LEVEL = 10;
    public static function tree(): array
    {
        return [
            'industrial_logistics'=>['name'=>'Industrial Logistics','base_cost'=>50000,'prerequisite'=>null,'production'=>2,'military'=>0,'defense'=>0,'diplomacy'=>0],
            'military_doctrine'=>['name'=>'Military Doctrine','base_cost'=>75000,'prerequisite'=>null,'production'=>0,'military'=>3,'defense'=>1,'diplomacy'=>0],
            'fortress_networks'=>['name'=>'Fortress Networks','base_cost'=>100000,'prerequisite'=>'industrial_logistics','production'=>0,'military'=>0,'defense'=>4,'diplomacy'=>0],
            'diplomatic_protocols'=>['name'=>'Diplomatic Protocols','base_cost'=>125000,'prerequisite'=>'industrial_logistics','production'=>0,'military'=>0,'defense'=>0,'diplomacy'=>1],
        ];
    }
    public static function cost(string $techKey, int $nextLevel): int { $node=self::tree()[$techKey]??null; return $node ? (int)min(2000000000,$node['base_cost']*max(1,$nextLevel)*max(1,$nextLevel)) : 0; }
    public static function canResearch(string $techKey, int $currentLevel, array $levels): bool { $node=self::tree()[$techKey]??null; if(!$node||$currentLevel>=self::MAX_LEVEL)return false; $pre=$node['prerequisite']; return $pre===null || (int)($levels[$pre]??0)>=1; }
    public static function durationMinutes(int $nextLevel): int { return max(30,min(1440,30*max(1,$nextLevel))); }
    public static function modifiers(array $levels): array { $out=['production_percent'=>0,'military_percent'=>0,'defense_percent'=>0,'diplomacy_slots'=>0]; foreach(self::tree() as $key=>$node){$level=max(0,min(self::MAX_LEVEL,(int)($levels[$key]??0)));$out['production_percent']+=$level*$node['production'];$out['military_percent']+=$level*$node['military'];$out['defense_percent']+=$level*$node['defense'];$out['diplomacy_slots']+=$level*$node['diplomacy'];} return $out; }
}
?>
