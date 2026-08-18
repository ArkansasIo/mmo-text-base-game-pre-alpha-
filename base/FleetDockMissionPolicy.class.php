<?php
final class FleetDockMissionPolicy
{
    public const TYPES = ['spy', 'expedition', 'raid', 'patrol'];

    public static function validType(string $type): bool
    {
        return in_array($type, self::TYPES, true);
    }

    public static function outcome(string $type, int $ships, int $targetPower = 0, int $metal = 0, int $crystal = 0, int $deuterium = 0): array
    {
        $type = strtolower($type);
        $ships = max(1, $ships);
        $targetPower = max(0, $targetPower);
        $scoutPower = $ships * 10;
        if ($type === 'spy') {
            $success = $scoutPower >= max(1, $targetPower);
            return ['success'=>$success,'reward_naquadah'=>0,'loot'=>['metal'=>0,'crystal'=>0,'deuterium'=>0],'intel'=>['fleet_power'=>$targetPower,'scan_quality'=>min(100, 35 + $ships * 8)],'patrol_bonus'=>0,'text'=>$success?'Reconnaissance returned with a verified enemy fleet profile.':'Reconnaissance was detected before a reliable profile could be recovered.'];
        }
        if ($type === 'raid') {
            $success = ($ships * 25) >= max(1, $targetPower);
            $rate = $success ? 20 : 5;
            return ['success'=>$success,'reward_naquadah'=>0,'loot'=>['metal'=>(int)floor(max(0,$metal)*$rate/100),'crystal'=>(int)floor(max(0,$crystal)*$rate/100),'deuterium'=>(int)floor(max(0,$deuterium)*$rate/100)],'intel'=>[],'patrol_bonus'=>0,'text'=>$success?'Raid succeeded and extracted a portion of the target stockpile.':'Raiders were repelled before meaningful cargo extraction.'];
        }
        if ($type === 'patrol') {
            return ['success'=>true,'reward_naquadah'=>0,'loot'=>['metal'=>0,'crystal'=>0,'deuterium'=>0],'intel'=>[],'patrol_bonus'=>min(100, 5 + (int)floor($ships / 2)),'text'=>'Defensive patrol established a temporary local security screen.'];
        }
        return ['success'=>true,'reward_naquadah'=>5000 + ($ships * 2500),'loot'=>['metal'=>0,'crystal'=>0,'deuterium'=>0],'intel'=>[],'patrol_bonus'=>0,'text'=>'Expedition returned with recovered Naquadah.'];
    }
}
?>
