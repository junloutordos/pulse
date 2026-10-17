<?php

namespace App\Http\Controllers;

use App\Models\DatasetVersion;
use App\Services\IndicatorRows;
use Inertia\Inertia;
use Inertia\Response;

class PipelineController extends Controller
{
    public function index(): Response
    {
        $version = DatasetVersion::activeOrFail();
        $rows = collect(IndicatorRows::forVersion($version));
        $shown = $rows->whereIn('status', ['ok', 'low']);

        return Inertia::render('Pipeline', [
            'rows' => $rows->all(),
            'regionMedians' => [
                'rate' => $this->median($shown->pluck('rate_per_1000')),
                'attendance' => $this->median($shown->pluck('attendance')),
            ],
            'hidden' => [
                'suppressed' => $rows->where('status', 'suppressed')->count(),
                'missing' => $rows->where('status', 'missing')->count(),
            ],
        ]);
    }

    private function median($values): ?float
    {
        $v = $values->filter(fn ($x) => $x !== null)->sort()->values();
        $n = $v->count();

        return match (true) {
            $n === 0 => null,
            $n % 2 === 1 => (float) $v[intdiv($n, 2)],
            default => ($v[$n / 2 - 1] + $v[$n / 2]) / 2,
        };
    }
}
