<?php

namespace App\Console\Commands;

use App\Support\Install\DatabaseProvisioner;
use App\Support\Install\Installer;
use App\Support\Install\InstallState;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Throwable;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Same steps as the browser wizard at /install, for a terminal (SSH):
 *   php artisan dailytable:install
 * On a database that already has Daily Table it updates it instead (backup first, data kept).
 */
class InstallCommand extends Command
{
    protected $signature = 'dailytable:install
        {--url= : Website address, e.g. https://wownaturals.in}
        {--admin-name= : Admin full name}
        {--admin-email= : Admin email}
        {--demo : Add demo kitchen, menu and staff}';

    protected $description = 'Install (or update) Daily Table: database, tables, roles and the admin account';

    public function handle(): int
    {
        $this->line('');
        $this->info('  Daily Table setup');
        $this->line('  You need the MySQL database name, user and password from your hosting panel.');
        $this->line('');

        $db = [
            'host' => text('MySQL host', default: InstallState::envValue('DB_HOST') ?? 'localhost', required: true),
            'port' => (int) text('MySQL port', default: InstallState::envValue('DB_PORT') ?? '3306', required: true),
            'database' => text('Database name', default: InstallState::envValue('DB_DATABASE') ?? 'dailytable', required: true,
                validate: fn ($v) => preg_match('/^[A-Za-z0-9_]{1,64}$/', $v) ? null : 'Use only letters, digits and underscores.'),
            'username' => text('Database user', default: InstallState::envValue('DB_USERNAME') ?? '', required: true),
            'password' => password('Database password'),
        ];

        if (confirm('Create the database and user with a MySQL admin login (e.g. root)? Usually only on a VPS.', default: false)) {
            try {
                $p = DatabaseProvisioner::connect([
                    'user' => text('MySQL admin user', default: 'root'), 'password' => password('MySQL admin password (not saved)'),
                    'host' => $db['host'], 'port' => $db['port'],
                ]);
                $p->provision($db['database'], $db['username'], $db['password'], ['localhost', '127.0.0.1'], resetPassword: true);
                foreach ($p->log as $l) {
                    $this->line('  ✓ '.$l);
                }
            } catch (Throwable $e) {
                $this->error('  Could not create the database: '.Installer::mysqlReason($e));

                return self::FAILURE;
            }
        }

        try {
            $version = Installer::useDatabase($db);
            $this->line("  ✓ Connected to MySQL {$version}");
        } catch (Throwable $e) {
            $this->error('  Could not connect to MySQL: '.Installer::mysqlReason($e));

            return self::FAILURE;
        }

        $url = $this->option('url') ?: text('Website address', default: InstallState::envValue('APP_URL') ?: 'https://wownaturals.in', required: true,
            validate: fn ($v) => preg_match('#^https?://[^\s/]+#', $v) ? null : 'Start with https://');
        $name = text('Site name', default: InstallState::envValue('APP_NAME') ?: 'Daily Table', required: true);

        $admin = ['name' => $this->option('admin-name'), 'email' => $this->option('admin-email'), 'password' => null];
        $admin['name'] = $admin['name'] ?: text('Admin full name', required: true);
        $admin['email'] = $admin['email'] ?: text('Admin email', required: true, validate: fn ($v) => filter_var($v, FILTER_VALIDATE_EMAIL) ? null : 'Enter a valid email.');
        do {
            $pw = password('Admin password (10+ characters, upper and lower case and a number)');
            $check = Validator::make(['p' => $pw, 'p_confirmation' => password('Repeat the password')], ['p' => Installer::passwordRules()]);
            foreach ($check->errors()->all() as $m) {
                $this->error('  '.$m);
            }
        } while ($check->fails());
        $admin['password'] = $pw;
        $demo = $this->option('demo') || confirm('Add demo data (Adyar kitchen, dishes, demo staff)?', default: false);

        InstallState::writeEnv(Installer::envFor($db, $url, str_starts_with($url, 'https://'), $name));
        config(['app.name' => $name, 'app.url' => $url]);

        try {
            $r = (new Installer)->run(function (string $label, callable $work) {
                $this->components->task($label, function () use ($work) {
                    $work();

                    return true;
                });
            }, $admin, $demo);
        } catch (Throwable $e) {
            $this->error('  Setup stopped: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->line('');
        $this->info($r['updated'] ? '  Daily Table is updated. Your data is kept.' : '  Daily Table is installed.');
        $this->line('  Website:     '.$url);
        $this->line('  Staff login: '.$url.'/staff/login  ('.$r['email'].')');
        if ($r['backup']) {
            $this->line('  Backup:      '.$r['backup']);
        }
        if ($r['demo_password']) {
            $this->warn('  Demo staff password (shown once): '.$r['demo_password']);
        }
        $this->line('  Cron (every minute): * * * * * php '.base_path('artisan').' schedule:run >> /dev/null 2>&1');
        $this->line('');

        return self::SUCCESS;
    }
}
