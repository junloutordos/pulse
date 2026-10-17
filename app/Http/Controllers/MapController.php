<?php

namespace App\Http\Controllers;

use App\Models\DatasetVersion;
use App\Services\IndicatorRows;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class MapController extends Controller
{
    private const METRICS = [
        ['key' => 'rate', 'label' => 'Applications per 1,000 Grade 6'],
        ['key' => 'attendance', 'label' => 'Exam attendance'],
        ['key' => 'change', 'label' => 'Change vs prior cycle'],
        ['key' => 'score', 'label' => 'Priority score'],
        ['key' => 'cluster', 'label' => 'Spatial clusters'],
    ];

    public function index(): Response
    {
        $version = DatasetVersion::activeOrFail();

        return Inertia::render('Map', ['rows' => IndicatorRows::forVersion($version), 'metrics' => self::METRICS]);
    }

    public function boundaries(): HttpResponse
    {
        $version = DatasetVersion::activeOrFail();

        return response(Storage::disk('local')->get($version->boundaries_path), 200, [
            'Content-Type' => 'application/geo+json',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
