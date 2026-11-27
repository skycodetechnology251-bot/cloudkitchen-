<?php

namespace App\Console\Commands;

use App\Support\Install\InstallState;
use App\Support\Install\Upgrader;
use Illuminate\Console\Command;
use Throwable;

/** After uploading new files: php artisan dailytable:update (same as Admin → System → Back up and update now). */
class UpdateCommand extends Command
{
    protected $signature = 'dailytable:update {--no-backup : Skip the database backup}';

    protected $description = 'Back up the database, then apply database updates, new roles and permissions';

    public function handle(): int
    {
        if (! InstallState::installed()) {
            $this->error('Daily Table is not installed yet. Open /install in a browser or run php artisan dailytable:install.');

            return self::FAILURE;
        }
        try {
            $r = (new Upgrader)->run(function (string $label, callable $work) {
                $this->components->task($label, function () use ($work) {
                    $work();

                    return true;
                });
            }, backup: ! $this->option('no-backup'));
            InstallState::writeLock();
        } catch (Throwable $e) {
            $this->error('Update stopped: '.$e->getMessage());

            return self::FAILURE;
        }
        $this->info("Updated {$r['from']} → {$r['to']} (".count($r['migrations']).' database update(s)).');
        if ($r['backup']) {
            $this->line('Backup: '.$r['backup']);
        }

        return self::SUCCESS;
    }
}
