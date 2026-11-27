<?php

namespace App\Console\Commands;

use App\Support\MobileConfig;
use Illuminate\Console\Command;

/** php artisan dailytable:mobile-config — write mobile/app-config.json from this site's settings. */
class MobileConfigCommand extends Command
{
    protected $signature = 'dailytable:mobile-config';

    protected $description = 'Write the mobile apps\' configuration (mobile/app-config.json) from the website settings';

    public function handle(): int
    {
        if (! MobileConfig::write()) {
            $this->error('Could not write '.MobileConfig::path().' (is the mobile folder there and writable?).');

            return self::FAILURE;
        }
        $this->info('Written '.MobileConfig::path());
        $this->line(json_encode(MobileConfig::build(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
