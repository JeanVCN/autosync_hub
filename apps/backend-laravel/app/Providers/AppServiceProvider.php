<?php

namespace App\Providers;

use App\Services\IntegrationHub\IntegrationHubClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(IntegrationHubClient::class, fn (): IntegrationHubClient => IntegrationHubClient::fromConfig());
    }
}
