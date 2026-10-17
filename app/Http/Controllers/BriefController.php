<?php

namespace App\Http\Controllers;

use App\Models\ActionRegisterEntry;
use App\Models\DatasetVersion;
use App\Models\Municipality;
use App\Services\BriefBuilder;
use App\Services\IndicatorRows;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Inertia\Inertia;
use Inertia\Response;

class BriefController extends Controller
{
    public function index(): Response
    {
        $version = DatasetVersion::active() ?? abort(409, 'No active dataset.');
        $names = Municipality::pluck('name', 'psgc');

        return Inertia::render('Brief', [
            'rows' => IndicatorRows::forVersion($version),
            'register' => ActionRegisterEntry::where('dataset_version_id', $version->id)->orderBy('planned_on')->get()
                ->map(fn ($e) => [
                    'id' => $e->id, 'psgc' => $e->psgc, 'name' => $names[$e->psgc] ?? $e->psgc, 'action' => $e->action,
                    'office' => $e->office, 'status' => $e->status,
                    'planned_on' => $e->planned_on->toDateString(), 'completed_on' => $e->completed_on?->toDateString(),
                ]),
            'canPlan' => Gate::allows('plan'),
        ]);
    }

    public function show(BriefBuilder $builder, string $psgc): HttpResponse
    {
        return response(View::make('brief', $this->data($builder, $psgc)));
    }

    public function pdf(BriefBuilder $builder, string $psgc): HttpResponse
    {
        $data = $this->data($builder, $psgc);

        return Pdf::loadView('brief', $data + ['forPdf' => true])->setPaper('a4')->stream("pulse-brief-{$psgc}.pdf");
    }

    public function complete(ActionRegisterEntry $entry): RedirectResponse
    {
        if ($entry->status !== 'completed') {
            $entry->update(['status' => 'completed', 'completed_on' => today()]);
        }

        return back();
    }

    /** @return array<string, mixed> */
    private function data(BriefBuilder $builder, string $psgc): array
    {
        $version = DatasetVersion::active() ?? abort(409, 'No active dataset.');

        return $builder->build($version, $psgc);
    }
}
