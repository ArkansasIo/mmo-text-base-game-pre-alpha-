<?php
/**
 * Population and starting-unit rules for Universe Civilization: Empire at Wars.
 * Random values are generated server-side and remain inside database-safe bounds.
 */
final class PopulationModel
{
    public const STARTING_UNTRAINED_UNITS = 2500000;
    public const PLANET_MIN = 100000;
    public const PLANET_MAX = 5000000;
    public const MOON_MIN = 10000;
    public const MOON_MAX = 750000;

    public static function randomPlanet(int $size = 5, int $habitability = 50): int
    {
        $size = max(1, min(9, $size));
        $habitability = max(0, min(100, $habitability));
        $floor = self::PLANET_MIN + (($size - 1) * 30000) + ($habitability * 500);
        $ceiling = self::PLANET_MAX + (($size - 1) * 250000) + ($habitability * 10000);
        return self::randomBetween(min($floor, self::PLANET_MAX), min($ceiling, 10000000));
    }

    public static function randomMoon(int $size = 3, int $habitability = 20): int
    {
        $size = max(1, min(9, $size));
        $habitability = max(0, min(100, $habitability));
        $floor = self::MOON_MIN + (($size - 1) * 5000) + ($habitability * 100);
        $ceiling = self::MOON_MAX + (($size - 1) * 25000) + ($habitability * 2000);
        return self::randomBetween(min($floor, self::MOON_MAX), min($ceiling, 2000000));
    }

    public static function randomBetween(int $min, int $max): int
    {
        return random_int(max(0, $min), max($min, $max));
    }
}
?>
