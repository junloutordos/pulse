<?php

namespace App\Http\Controllers;

use App\Models\ActionRegisterEntry;
use App\Models\DatasetVersion;
use App\Models\Municipality;
use App\Models\MunicipalityNeighbor;
use App\Models\PlannerDecision;
use App\Services\IndicatorRows;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PlannerController extends Controller
{
    private const NEARBY_LIMIT = 3;

    public function index(): Response
    {
        $version = DatasetVersion::active() ?? abort(409, 'No active dataset.');
        $names = Municipality::pluck('name', 'psgc');

        $nearby = MunicipalityNeighbor::where('dataset_version_id', $version->id)
            ->where('kind', 'nearby')->orderBy('neighbor_order')->get()
            ->groupBy('psgc')
            ->map(fn ($group) => $group->take(self::NEARBY_LIMIT)->map(fn ($n) => [
                'psgc' => $n->neighbor_psgc, 'name' => $names[$n->neighbor_psgc] ?? $n->neighbor_psgc, 'km' => round($n->km, 1),
            ])->values());

        return Inertia::render('Planner', [
            'rows' => IndicatorRows::forVersion($version),
            'decisions' => PlannerDecision::where('dataset_version_id', $version->id)->get(['psgc', 'status', 'reason', 'weights']),
            'register' => ActionRegisterEntry::where('dataset_version_id', $version->id)->get(['id', 'psgc', 'action', 'status', 'planned_on']),
            'nearby' => $nearby,
            'canPlan' => Gate::allows('plan'),
        ]);
    }

    public function decide(Request $request): RedirectResponse
    {
        Gate::authorize('plan');

        $version = DatasetVersion::active() ?? abort(409, 'No active dataset.');

        $data = $request->validate([
            'psgc' => ['required', 'string', Rule::exists('municipality_indicators', 'psgc')->where('dataset_version_id', $version->id)],
            'status' => ['required', Rule::in(['accepted', 'deferred'])],
            'reason' => ['required', 'string', 'max:255'],
            'action' => ['required_if:status,accepted', 'nullable', 'string', 'max:255'],
            'weights' => ['required', 'array'],
            'weights.reach' => ['required', 'numeric', 'min:0'],
            'weights.attendance' => ['required', 'numeric', 'min:0'],
            'weights.decline' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($request, $version, $data) {
            $key = ['dataset_version_id' => $version->id, 'psgc' => $data['psgc']];
            $entry = ActionRegisterEntry::where($key)->first();

            if ($data['status'] === 'deferred' && $entry?->status === 'completed') {
                throw ValidationException::withMessages(['psgc' => 'This action is already completed and cannot be deferred.']);
            }

            $decision = PlannerDecision::updateOrCreate($key, [
                'user_id' => $request->user()->id,
                'status' => $data['status'],
                'reason' => $data['reason'],
                'weights' => $data['weights'],
            ]);

            if ($data['status'] === 'deferred') {
                $entry?->delete();

                return;
            }

            $entry ??= new ActionRegisterEntry($key + ['status' => 'planned', 'planned_on' => today()]);
            $entry->fill(['action' => $data['action'], 'planner_decision_id' => $decision->id])->save();
        });

        return back();
    }

    /** Undo: remove the decision and its planned register entry. A completed action cannot be undone. */
    public function undo(string $psgc): RedirectResponse
    {
        Gate::authorize('plan');
        $version = DatasetVersion::activeOrFail();
        $key = ['dataset_version_id' => $version->id, 'psgc' => $psgc];

        DB::transaction(function () use ($key) {
            $entry = ActionRegisterEntry::where($key)->first();
            if ($entry?->status === 'completed') {
                throw ValidationException::withMessages(['psgc' => 'This action is already completed and cannot be undone.']);
            }
            $entry?->delete();
            PlannerDecision::where($key)->delete();
        });

        return back();
    }
}
