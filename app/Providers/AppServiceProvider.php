<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\TemplateService;
use App\Services\LLMService;
use App\Services\AdvisorGenerationService;
use App\Services\AdvisorConfigService;
use App\Services\Validation\AdvisorQualityService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TemplateService::class, function ($app) {
            return new TemplateService();
        });

        $this->app->singleton(LLMService::class, function ($app) {
            return new LLMService();
        });

        $this->app->singleton(AdvisorConfigService::class, function ($app) {
            return new AdvisorConfigService();
        });

        $this->app->singleton(AdvisorQualityService::class, function ($app) {
            return new AdvisorQualityService();
        });

        $this->app->singleton(AdvisorGenerationService::class, function ($app) {
            return new AdvisorGenerationService(
                $app->make(TemplateService::class),
                $app->make(LLMService::class),
                $app->make(AdvisorConfigService::class),
                $app->make(AdvisorQualityService::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
