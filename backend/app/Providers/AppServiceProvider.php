<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Behind a hosting proxy the site is still served over HTTPS: build https links.
        // Not before setup, so the setup page also opens on a domain whose SSL isn't on yet.
        if (str_starts_with((string) config('app.url'), 'https://') && \App\Support\Install\InstallState::installed()) {
            URL::forceScheme('https');
        }
    }
}
