<?php

namespace App\Http\Controllers;

use App\Models\DatasetVersion;
use App\Models\Municipality;
use App\Models\MunicipalityIndicator;
use Inertia\Inertia;
use Inertia\Response;

class TrustController extends Controller
{
    private const STATUS_REASON = [
        'low' => 'Fewer than 15 applicants; shown but not ranked',
        'suppressed' => 'Applicant count below 5; values withheld',
    ];

    public function index(): Response
    {
        $version = DatasetVersion::activeOrFail();
        $municipalities = Municipality::get()->keyBy('psgc');
        $indicators = MunicipalityIndicator::where('dataset_version_id', $version->id)->get();
        $sourceIssues = collect($version->issues);

        $unmatched = $sourceIssues->where('kind', 'unmatched');
        $aliases = $sourceIssues->where('kind', 'alias')->unique('psgc');
        $shared = $municipalities->groupBy('name')->filter(fn ($g) => $g->pluck('province')->unique()->count() > 1)->flatten();
        $byStatus = fn (string $s) => $indicators->where('status', $s);

        $issues = collect()
            ->merge($unmatched->map(fn ($i) => ['psgc' => null, 'name' => $i['raw_name'], 'province' => $i['raw_province'] ?? '', 'kind' => 'unmatched', 'reason' => $i['reason']]))
            ->merge($aliases->map(fn ($i) => $this->issue($municipalities[$i['psgc']], 'alias', "Source spelling “{$i['raw_name']}” matched by the alias table")))
            ->merge($sourceIssues->where('kind', 'duplicate')->unique('psgc')->map(fn ($i) => $this->issue($municipalities[$i['psgc']], 'duplicate', $i['reason'])))
            ->merge($shared->map(fn ($m) => $this->issue($m, 'shared_name', 'Name shared with another province; matched using the province column')))
            ->merge($indicators->whereIn('status', ['low', 'suppressed', 'missing'])->map(fn ($r) => $this->issue(
                $municipalities[$r->psgc], $r->status,
                self::STATUS_REASON[$r->status] ?? "No row for the {$version->cycle} cycle in the source",
            )))
            ->values();

        return Inertia::render('Trust', [
            'counts' => [
                'total' => $municipalities->count(),
                'matched' => $indicators->where('status', '!=', 'missing')->count(),
                'unmatched' => $unmatched->count(),
                'aliasFixes' => $aliases->count(),
                'sharedNames' => $shared->pluck('name')->unique()->count(),
                'low' => $byStatus('low')->count(),
                'suppressed' => $byStatus('suppressed')->count(),
                'missing' => $byStatus('missing')->count(),
            ],
            'lineage' => $this->lineage($version, $municipalities->count()),
            'reconciliation' => $version->reconciliation,
            'issues' => $issues,
        ]);
    }

    private function issue(Municipality $m, string $kind, string $reason): array
    {
        return ['psgc' => $m->psgc, 'name' => $m->name, 'province' => $m->province, 'kind' => $kind, 'reason' => $reason];
    }

    /** @return list<array{label: string, detail: string, ref: string}> */
    private function lineage(DatasetVersion $v, int $municipalities): array
    {
        $sha = substr($v->checksums['indicators.jsonl'] ?? '', 0, 7);

        return [
            ['label' => 'Source', 'detail' => $v->source === 'synthetic' ? 'Synthetic generator (seed 2026)' : 'GATES Lakehouse', 'ref' => "cycle {$v->cycle}"],
            ['label' => 'Validate', 'detail' => 'Schema and suppression rules', 'ref' => 'suppress below 5, low sample below 15'],
            ['label' => 'Geography', 'detail' => 'PSGC match and boundaries', 'ref' => "{$municipalities} municipalities"],
            ['label' => 'Indicators', 'detail' => 'Rates, trend and clusters', 'ref' => "formula {$v->formula_version}"],
            ['label' => 'Dataset', 'detail' => $v->version, 'ref' => "formula {$v->formula_version} · sha {$sha}"],
        ];
    }
}
