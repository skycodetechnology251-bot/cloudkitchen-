<?php

namespace App\Http\Controllers;

use App\Support\Install\DatabaseProvisioner;
use App\Support\Install\Installer;
use App\Support\Install\InstallState;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

/**
 * Browser setup wizard at /install: no terminal needed. One page: server check, database,
 * admin account. Also updates an existing Daily Table database (backup first, data kept).
 * Available only until Daily Table is installed (OnlyBeforeInstall returns 404 afterwards).
 */
class InstallController extends Controller
{
    public function show(Request $request): View
    {
        return view('install.show', [
            'checks' => $this->checks(),
            'url' => rtrim($request->root(), '/'),
            'defaults' => [
                'db_host' => $this->envDefault('DB_HOST') ?? 'localhost',
                'db_port' => $this->envDefault('DB_PORT') ?? '3306',
                'db_database' => $this->envDefault('DB_DATABASE') ?? '',
                'db_username' => $this->envDefault('DB_USERNAME') ?? '',
                'app_name' => $this->envDefault('APP_NAME') ?? 'Daily Table',
            ],
        ]);
    }

    public function store(Request $request): View|RedirectResponse
    {
        if (collect($this->checks())->contains(fn ($c) => $c['required'] && ! $c['ok'])) {
            return back()->withInput()->with('error', 'Fix the items marked "Missing" first, then reload this page.');
        }

        $data = $request->validate([
            'db_host' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9.\-:\[\]_]+$/'],
            'db_port' => ['required', 'integer', 'between:1,65535'],
            'db_database' => ['required', 'regex:/^[A-Za-z0-9_]{1,64}$/'],
            'db_username' => ['required', 'regex:/^[A-Za-z0-9_.\-]{1,80}$/'],
            'db_password' => ['nullable', 'string', 'max:200'],
            'create_database' => ['nullable', 'boolean'],
            'admin_username' => ['required_if:create_database,1', 'nullable', 'string', 'max:80'],
            'admin_password' => ['nullable', 'string', 'max:200'],
            'app_name' => ['required', 'string', 'max:60', 'regex:/^[^\r\n"\\\\$]+$/'],
            'admin_name' => ['required', 'string', 'max:120'],
            'admin_email' => ['required', 'email', 'max:190'],
            'admin_pass' => Installer::passwordRules(),
            'demo' => ['nullable', 'boolean'],
        ], [
            'db_database.regex' => 'Use only letters, digits and underscores.',
            'db_username.regex' => 'Use only letters, digits, dots, dashes and underscores.',
            'admin_username.required_if' => 'Enter the MySQL admin user (often root), or untick "Create the database for me".',
        ], [
            'admin_pass' => 'password', 'admin_name' => 'name', 'admin_email' => 'email', 'app_name' => 'site name',
        ]);

        $db = [
            'host' => $data['db_host'], 'port' => (int) $data['db_port'], 'database' => $data['db_database'],
            'username' => $data['db_username'], 'password' => (string) ($data['db_password'] ?? ''),
        ];
        $keepInput = $request->except(['db_password', 'admin_password', 'admin_pass', 'admin_pass_confirmation']);

        // 1. Optionally create the database and user with MySQL admin access (not saved).
        if ($request->boolean('create_database')) {
            try {
                DatabaseProvisioner::connect([
                    'user' => $data['admin_username'], 'password' => (string) ($data['admin_password'] ?? ''),
                    'host' => $db['host'], 'port' => $db['port'],
                ])->provision($db['database'], $db['username'], $db['password'], ['localhost', '127.0.0.1'], resetPassword: true);
            } catch (Throwable $e) {
                throw ValidationException::withMessages(['admin_username' => 'Could not create the database: '.Installer::mysqlReason($e)]);
            }
        }

        // 2. Try the database on a scratch connection; switch to it only once it works.
        try {
            Installer::useDatabase($db);
        } catch (Throwable $e) {
            throw ValidationException::withMessages(['db_password' => 'Could not connect to MySQL: '.Installer::mysqlReason($e)]);
        }

        // 3. Save the settings.
        $url = rtrim($request->root(), '/');
        InstallState::writeEnv(Installer::envFor($db, $url, $request->isSecure(), $data['app_name']));
        config(['app.name' => $data['app_name'], 'app.url' => $url]);

        // 4. Tables, roles, admin, demo data, lock.
        try {
            $result = (new Installer)->run(fn (string $label, callable $work) => $work(), [
                'name' => $data['admin_name'], 'email' => $data['admin_email'], 'password' => $data['admin_pass'],
            ], $request->boolean('demo'));
        } catch (Throwable $e) {
            report($e);

            return back()->withInput($keepInput)->with('error', 'Setting up the database stopped: '.$e->getMessage());
        }

        // Sessions move from files to the database at this moment, so the result is shown directly
        // instead of after a redirect. Reloading this page gives 404 (installed).
        return view('install.done', $result + ['loginUrl' => url('/staff/login'), 'url' => $url, 'cron' => $this->cronCommand()]);
    }

    private function cronCommand(): string
    {
        return '/usr/local/bin/php '.base_path('artisan').' schedule:run >> /dev/null 2>&1';
    }

    /** A value from .env, ignoring the placeholders of the example file. */
    private function envDefault(string $key): ?string
    {
        $v = InstallState::envValue($key);

        return $v === null || preg_match('/cpaneluser|your-domain|^\$\{/i', $v) ? null : $v;
    }

    /** @return list<array{label: string, found: string, ok: bool, required: bool}> */
    private function checks(): array
    {
        $req = config('dailytable.requirements');
        $rows = [['label' => 'PHP '.$req['php'].' or newer', 'found' => PHP_VERSION, 'ok' => version_compare(PHP_VERSION, $req['php'], '>='), 'required' => true]];
        foreach ($req['extensions'] as $ext) {
            $rows[] = ['label' => "PHP extension {$ext}", 'found' => extension_loaded($ext) ? 'Loaded' : 'Not loaded', 'ok' => extension_loaded($ext), 'required' => true];
        }
        foreach ($req['writable'] as $dir) {
            $w = is_dir(base_path($dir)) && is_writable(base_path($dir));
            $rows[] = ['label' => "Folder {$dir} is writable", 'found' => $w ? 'Yes' : 'No', 'ok' => $w, 'required' => true];
        }
        $env = InstallState::envPath();
        $envOk = is_file($env) ? is_writable($env) : is_writable(dirname($env));
        $rows[] = ['label' => 'Settings file .env can be written', 'found' => $envOk ? 'Yes' : 'No', 'ok' => $envOk, 'required' => true];
        $vendor = is_file(base_path('vendor/autoload.php'));
        $rows[] = ['label' => 'Bundled packages (vendor folder)', 'found' => $vendor ? 'Present' : 'Missing', 'ok' => $vendor, 'required' => true];

        return $rows;
    }
}
