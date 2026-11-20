<?php

namespace App\Support;

/**
 * Small helpers for the hand-drawn SVG charts on the dashboard.
 */
class Chart
{
    /**
     * Map values onto an SVG box: x spread evenly, y = 0 at the bottom, $max at the top.
     *
     * @param  array<int, int|float>  $values
     * @return array<int, array{0: float, 1: float}>
     */
    public static function points(array $values, float $width, float $height, float $max, float $padTop = 0, float $padBottom = 0): array
    {
        $values = array_values($values);
        $count = count($values);
        $max = max($max, 1);
        $usable = $height - $padTop - $padBottom;

        return array_map(fn ($value, int $i) => [
            $count > 1 ? $i * $width / ($count - 1) : $width / 2,
            $height - $padBottom - ($value / $max) * $usable,
        ], $values, array_keys($values));
    }

    /**
     * A smooth line through the points (Catmull-Rom turned into cubic Bézier curves).
     * Control points are clamped so the curve never dips below the baseline.
     *
     * @param  array<int, array{0: float, 1: float}>  $points
     */
    public static function smoothPath(array $points, ?float $floor = null): string
    {
        if (count($points) < 2) {
            return '';
        }

        $clamp = fn (float $y) => $floor === null ? $y : min($y, $floor);
        $path = sprintf('M%.2f,%.2f', ...$points[0]);
        $last = count($points) - 1;

        for ($i = 0; $i < $last; $i++) {
            $p0 = $points[max($i - 1, 0)];
            $p1 = $points[$i];
            $p2 = $points[$i + 1];
            $p3 = $points[min($i + 2, $last)];

            $c1 = [$p1[0] + ($p2[0] - $p0[0]) / 6, $clamp($p1[1] + ($p2[1] - $p0[1]) / 6)];
            $c2 = [$p2[0] - ($p3[0] - $p1[0]) / 6, $clamp($p2[1] - ($p3[1] - $p1[1]) / 6)];

            $path .= sprintf(' C%.2f,%.2f %.2f,%.2f %.2f,%.2f', $c1[0], $c1[1], $c2[0], $c2[1], $p2[0], $p2[1]);
        }

        return $path;
    }

    /** A round top value for the y axis that splits into four equal steps. */
    public static function niceMax(float $max): int
    {
        return (int) max(4, ceil($max / 4) * 4);
    }
}
