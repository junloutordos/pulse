<?php

namespace App\Http\Controllers;

use App\Models\DatasetVersion;
use App\Services\IndicatorRows;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PriorityController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Priority', [
            'rows' => IndicatorRows::forVersion(DatasetVersion::activeOrFail()),
            'canPlan' => Gate::allows('plan'),
        ]);
    }
}
