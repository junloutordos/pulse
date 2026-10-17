<?php

namespace App\Services;

use App\Models\DatasetVersion;
use App\Models\MunicipalityIndicator;

class IndicatorRows
{
    /** @return list<array<string, mixed>> One row per municipality for a dataset version, snake_case to match the scoring fixtures. */
    public static function forVersion(DatasetVersion $version): array
    {
        return MunicipalityIndicator::query()
            ->where('dataset_version_id', $version->id)
            ->join('municipalities', 'municipalities.psgc', '=', 'municipality_indicators.psgc')
            ->orderBy('municipalities.province')
            ->orderBy('municipalities.name')
            ->get([
                'municipality_indicators.psgc', 'municipalities.name', 'municipalities.province', 'municipalities.lat',
                'municipalities.lon', 'status', 'applicants', 'examinees', 'qualifiers', 'grade6_pop', 'prior_applicants',
                'rate_per_1000', 'attendance', 'change', 'lisa_cluster',
            ])
            ->map(fn ($r) => $r->only([
                'psgc', 'name', 'province', 'lat', 'lon', 'status', 'applicants', 'examinees', 'qualifiers', 'grade6_pop',
                'prior_applicants', 'rate_per_1000', 'attendance', 'change', 'lisa_cluster',
            ]))
            ->all();
    }
}
