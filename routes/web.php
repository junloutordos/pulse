<?php

use App\Http\Controllers\BriefController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\PipelineController;
use App\Http\Controllers\PriorityController;
use App\Http\Controllers\TrustController;
use App\Http\Controllers\PlannerController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// PULSE is for authenticated staff only: there is no public page and no self-registration (see `php artisan pulse:user`).
Route::redirect('/', '/dashboard');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/trust', [TrustController::class, 'index'])->name('trust');
    Route::get('/map', [MapController::class, 'index'])->name('map');
    Route::get('/data/boundaries.geojson', [MapController::class, 'boundaries'])->name('boundaries');
    Route::get('/pipeline', [PipelineController::class, 'index'])->name('pipeline');
    Route::get('/priority', [PriorityController::class, 'index'])->name('priority');
    Route::get('/planner', [PlannerController::class, 'index'])->name('planner');
    Route::get('/brief', [BriefController::class, 'index'])->name('brief');
    Route::get('/brief/{psgc}', [BriefController::class, 'show'])->name('brief.show');
    Route::get('/brief/{psgc}/pdf', [BriefController::class, 'pdf'])->name('brief.pdf');
    Route::patch('/register/{entry}', [BriefController::class, 'complete'])->middleware('can:plan')->name('register.complete');
    Route::post('/planner/decisions', [PlannerController::class, 'decide'])->middleware('can:plan')->name('planner.decide');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
