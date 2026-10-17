<?php

namespace App\Services;

/** Combines the percentile components stored by the pipeline with a set of weights. The percentiles themselves are never recomputed here. */
final class WeightedScore
{
    public const KEYS = ['reach', 'attendance', 'decline'];

    public const BALANCED = ['reach' => 1 / 3, 'attendance' => 1 / 3, 'decline' => 1 / 3];

    /**
     * @param  array{reach: ?float, attendance: ?float, decline: ?float}  $components
     * @param  array{reach: float|int, attendance: float|int, decline: float|int}  $weights
     */
    public static function score(array $components, array $weights): ?float
    {
        $numerator = 0.0;
        $weightSum = 0.0;
        foreach (self::KEYS as $key) {
            if ($components[$key] === null) {
                continue;
            }
            $numerator += $weights[$key] * $components[$key];
            $weightSum += $weights[$key];
        }

        return $weightSum > 0 ? 100 * $numerator / $weightSum : null;
    }

    /**
     * @param  array<string, ?float>  $scores  keyed by municipality code
     * @return array<string, int>  minimum rank per municipality, 1 = highest score; nulls are not ranked
     */
    public static function ranks(array $scores): array
    {
        $ranked = array_filter($scores, fn ($s) => $s !== null);
        $ranks = [];
        foreach ($ranked as $key => $score) {
            $ranks[$key] = 1 + count(array_filter($ranked, fn ($other) => $other > $score));
        }

        return $ranks;
    }
}
