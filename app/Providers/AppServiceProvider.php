<?php

namespace App\Providers;

use App\Models\User;
use App\Services\BriefBuilder;
use App\Services\Narrative\ClaudeNarrative;
use App\Services\Narrative\TemplateNarrative;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The AI summary is opt-in: it needs the flag and a key. Otherwise only the fixed template is used.
        $this->app->bind(BriefBuilder::class, fn ($app) => new BriefBuilder(
            new TemplateNarrative,
            config('pulse.ai_narrative') && config('services.anthropic.key') ? new ClaudeNarrative : null,
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Gate::define('plan', fn (User $user) => $user->role === 'planner');
    }
}
