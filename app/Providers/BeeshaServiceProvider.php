<?php

namespace App\Providers;

use App\Services\Beesha\AnthropicAssistantProvider;
use App\Services\Beesha\Contracts\AssistantProvider;
use App\Services\Beesha\DemoAssistantProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Binds the configured AI provider. Defaults to the deterministic demo
 * assistant, so no company data can reach an external service unless
 * BEESHA_PROVIDER is deliberately changed and a key is supplied.
 */
class BeeshaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AssistantProvider::class, function ($app) {
            return match (config('phm.beesha.provider')) {
                'anthropic' => $app->make(AnthropicAssistantProvider::class),
                default => $app->make(DemoAssistantProvider::class),
            };
        });
    }
}
