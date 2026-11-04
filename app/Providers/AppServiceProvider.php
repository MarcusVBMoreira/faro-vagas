<?php

namespace App\Providers;

use App\Contracts\AiClient;
use App\Services\Ai\OllamaClient;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AiClient::class, OllamaClient::class);
    }

    public function boot(): void
    {
        Password::defaults(fn () => Password::min(8)->letters()->numbers());
    }
}
