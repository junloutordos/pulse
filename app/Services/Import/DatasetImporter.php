<?php

namespace App\Services\Import;

use App\Models\DatasetVersion;
use App\Models\Municipality;
use App\Models\MunicipalityIndicator;
use App\Models\MunicipalityNeighbor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DatasetImporter
{
    private const CHUNK = 500;

    public function import(string $versionDir, bool $activate = true): DatasetVersion
    {
        $versionDir = rtrim($versionDir, '/');
        $prov = json_decode(file_get_contents("$versionDir/provenance.json"), true, 512, JSON_THROW_ON_ERROR);
        $this->verifyChecksums($versionDir, $prov['files']);

        $munis = $this->rows("$versionDir/munis.jsonl");
        $indicators = $this->rows("$versionDir/indicators.jsonl");
        $neighbors = $this->rows("$versionDir/neighbors.jsonl");
        $scores = array_column($this->rows("$versionDir/scores.jsonl"), null, 'psgc');
        $boundariesPath = "versions/{$prov['dataset_version']}/boundaries.geojson";
        Storage::disk('local')->put($boundariesPath, file_get_contents("$versionDir/boundaries.geojson"));

        return DB::transaction(function () use ($prov, $munis, $indicators, $neighbors, $scores, $versionDir, $boundariesPath, $activate) {
            Municipality::upsert(
                array_map(fn ($m) => [
                    'psgc' => $m['psgc'], 'name' => $m['name'], 'province_psgc' => $m['province_psgc'],
                    'province' => $m['province'], 'lat' => $m['lat'], 'lon' => $m['lon'],
                ], $munis),
                ['psgc'],
                ['name', 'province_psgc', 'province', 'lat', 'lon'],
            );

            $version = DatasetVersion::updateOrCreate(
                ['version' => $prov['dataset_version']],
                [
                    'source' => $prov['source'],
                    'cycle' => $prov['cycle'],
                    'prior_cycle' => $prov['prior_cycle'],
                    'formula_version' => $prov['formula_version'],
                    'schema_version' => $prov['schema_version'],
                    'refreshed_at' => $prov['refreshed_at'],
                    'checksums' => $prov['files'],
                    'reconciliation' => $this->rows("$versionDir/reconciliation.jsonl"),
                    'issues' => $this->rows("$versionDir/issues.jsonl"),
                    'boundaries_path' => $boundariesPath,
                ],
            );

            MunicipalityIndicator::where('dataset_version_id', $version->id)->delete();
            foreach (array_chunk($indicators, self::CHUNK) as $chunk) {
                MunicipalityIndicator::insert(array_map(fn ($r) => [
                    'dataset_version_id' => $version->id,
                    'psgc' => $r['psgc'],
                    'applicants' => $r['applicants'],
                    'examinees' => $r['examinees'],
                    'qualifiers' => $r['qualifiers'],
                    'grade6_pop' => $r['grade6_pop'],
                    'prior_applicants' => $r['prior_applicants'],
                    'rate_per_1000' => $r['rate_per_1000'],
                    'attendance' => $r['attendance'],
                    'change' => $r['change'],
                    'status' => $r['status'],
                    'lisa_cluster' => $r['lisa_cluster'],
                    'c_reach' => $scores[$r['psgc']]['c_reach'] ?? null,
                    'c_attendance' => $scores[$r['psgc']]['c_attendance'] ?? null,
                    'c_decline' => $scores[$r['psgc']]['c_decline'] ?? null,
                    'not_ranked_reason' => $scores[$r['psgc']]['not_ranked_reason'] ?? null,
                ], $chunk));
            }

            MunicipalityNeighbor::where('dataset_version_id', $version->id)->delete();
            foreach (array_chunk($neighbors, self::CHUNK) as $chunk) {
                MunicipalityNeighbor::insert(array_map(fn ($r) => [
                    'dataset_version_id' => $version->id,
                    'psgc' => $r['psgc'],
                    'neighbor_psgc' => $r['neighbor_psgc'],
                    'kind' => $r['kind'],
                    'neighbor_order' => $r['order'],
                    'km' => $r['km'],
                ], $chunk));
            }

            if ($activate) {
                $version->activate();
            }

            return $version->fresh();
        });
    }

    /** @param array<string, string> $files */
    private function verifyChecksums(string $dir, array $files): void
    {
        foreach ($files as $name => $expected) {
            $path = "$dir/$name";
            if (! is_file($path) || ! hash_equals($expected, hash_file('sha256', $path))) {
                throw new ChecksumMismatch("Checksum mismatch for $name; nothing was imported.");
            }
        }
    }

    /** @return list<array<string, mixed>> */
    private function rows(string $path): array
    {
        $rows = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $rows[] = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
        }

        return $rows;
    }
}
