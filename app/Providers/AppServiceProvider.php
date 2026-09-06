<?php

namespace App\Providers;

use App\Contracts\ComplianceEngine;
use App\Models\ExportSession;
use App\Policies\ExportSessionPolicy;
use App\Services\Compliance\AnthropicComplianceEngine;
use App\Services\Compliance\BackendAiComplianceEngine;
use App\Services\Compliance\GeminiComplianceEngine;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ComplianceEngine::class, function ($app) {
            return match (config('compliance.driver')) {
                'anthropic' => $app->make(AnthropicComplianceEngine::class),
                'gemini' => $app->make(GeminiComplianceEngine::class),
                default => $app->make(BackendAiComplianceEngine::class),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(ExportSession::class, ExportSessionPolicy::class);
    }
}
