<?php

declare(strict_types=1);

namespace App\Modules\Certificate\Services;

/** The fine interlaced line work of banknotes and diplomas, drawn as SVG paths. */
final class Guilloche
{
    /** @return list<string> one closed path per ring */
    public static function rosette(float $cx, float $cy, float $inner, float $outer, int $lobes, int $rings): array
    {
        $paths = [];

        for ($ring = 0; $ring < $rings; $ring++) {
            $phase = $ring * M_PI / $rings;
            $radius = ($inner + $outer) / 2;
            $amplitude = ($outer - $inner) / 2;
            $points = [];

            for ($step = 0; $step < 360; $step += 2) {
                $angle = deg2rad($step);
                $distance = $radius + $amplitude * sin($lobes * $angle + $phase);
                $points[] = round($cx + $distance * cos($angle), 1).' '.round($cy + $distance * sin($angle), 1);
            }

            $paths[] = 'M'.implode('L', $points).'Z';
        }

        return $paths;
    }

    /** @return list<string> vertical waves that drift apart and meet again */
    public static function waves(float $width, float $height, int $lines): array
    {
        $paths = [];

        for ($line = 0; $line < $lines; $line++) {
            $base = $width * ($line + 0.5) / $lines;
            $phase = $line * 0.32;
            $points = [];

            for ($y = 0; $y <= $height; $y += 9) {
                $x = $base + 16 * sin($y / 46 + $phase) + 7 * sin($y / 19 - $phase);
                $points[] = round($x, 1).' '.$y;
            }

            $paths[] = 'M'.implode('L', $points);
        }

        return $paths;
    }
}
