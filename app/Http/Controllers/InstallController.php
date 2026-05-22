<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class InstallController extends Controller
{
    public function show(Request $request)
    {
        if ($this->isLocked()) {
            abort(404);
        }

        return view('install.index', [
            'checks' => $this->runChecks(),
            'old' => $request->session()->getOldInput(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($this->isLocked()) {
            abort(404);
        }

        $data = $request->validate([
            'db_connection' => ['required', Rule::in(['mysql', 'sqlite'])],
            'db_host' => ['required_if:db_connection,mysql', 'nullable', 'string'],
            'db_port' => ['nullable', 'integer'],
            'db_database' => ['required', 'string'],
            'db_username' => ['required_if:db_connection,mysql', 'nullable', 'string'],
            'db_password' => ['nullable', 'string'],

            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email'],
            'admin_password' => ['required', 'string', 'min:8'],

            'tenant_slug' => ['required', 'string', 'alpha_dash', 'max:64'],
            'tenant_name' => ['required', 'string', 'max:255'],
            'tenant_currency' => ['required', 'string', 'size:3'],
            'tenant_locale' => ['required', 'string', 'in:sv,en'],

            'seed_demo' => ['nullable', 'boolean'],
        ]);

        if (! app()->environment('testing')) {
            $this->writeEnv($data);

            Artisan::call('config:clear');

            try {
                DB::purge();
                DB::connection()->getPdo();
            } catch (\Throwable $e) {
                return back()->withInput()->withErrors([
                    'db_connection' => 'Could not connect to the database: ' . $e->getMessage(),
                ]);
            }

            Artisan::call('migrate', ['--force' => true]);
        }

        $tenant = Tenant::create([
            'slug' => $data['tenant_slug'],
            'name' => $data['tenant_name'],
            'currency' => strtoupper($data['tenant_currency']),
            'locale' => $data['tenant_locale'],
            'is_active' => true,
        ]);

        User::create([
            'tenant_id' => $tenant->id,
            'name' => $data['admin_name'],
            'email' => $data['admin_email'],
            'password' => Hash::make($data['admin_password']),
            'role' => User::ROLE_ADMIN,
        ]);

        if (! empty($data['seed_demo'])) {
            app()->instance('currentTenant', $tenant);
            Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);
        }

        $this->lock();

        return redirect('/')->with('status', 'Installation complete.');
    }

    protected function isLocked(): bool
    {
        return file_exists(storage_path('install.lock'));
    }

    protected function lock(): void
    {
        file_put_contents(storage_path('install.lock'), now()->toIso8601String());
    }

    protected function runChecks(): array
    {
        $required = ['xml', 'mbstring', 'curl', 'zip', 'bcmath', 'intl', 'pdo'];

        $checks = [
            'php_version' => [
                'label' => 'PHP ≥ 8.2',
                'ok' => version_compare(PHP_VERSION, '8.2.0', '>='),
                'value' => PHP_VERSION,
            ],
        ];

        foreach ($required as $ext) {
            $checks['ext_' . $ext] = [
                'label' => "PHP extension: $ext",
                'ok' => extension_loaded($ext),
                'value' => extension_loaded($ext) ? 'loaded' : 'missing',
            ];
        }

        $checks['storage_writable'] = [
            'label' => 'storage/ writable',
            'ok' => is_writable(storage_path()),
            'value' => is_writable(storage_path()) ? 'yes' : 'no',
        ];

        $checks['env_writable'] = [
            'label' => '.env writable',
            'ok' => is_writable(base_path('.env')) || is_writable(base_path()),
            'value' => is_writable(base_path('.env')) ? 'yes' : (is_writable(base_path()) ? 'parent writable' : 'no'),
        ];

        return $checks;
    }

    protected function writeEnv(array $data): void
    {
        $envPath = base_path('.env');
        $env = file_exists($envPath) ? file_get_contents($envPath) : file_get_contents(base_path('.env.example'));

        $patch = [
            'DB_CONNECTION' => $data['db_connection'],
            'DB_HOST' => $data['db_host'] ?? '127.0.0.1',
            'DB_PORT' => $data['db_port'] ?? ($data['db_connection'] === 'mysql' ? '3306' : ''),
            'DB_DATABASE' => $data['db_database'],
            'DB_USERNAME' => $data['db_username'] ?? '',
            'DB_PASSWORD' => $data['db_password'] ?? '',
        ];

        foreach ($patch as $key => $value) {
            $line = $key . '=' . (preg_match('/\s/', (string) $value) ? '"' . $value . '"' : $value);
            if (preg_match("/^{$key}=.*/m", $env)) {
                $env = preg_replace("/^{$key}=.*/m", $line, $env);
            } else {
                $env .= PHP_EOL . $line;
            }
        }

        file_put_contents($envPath, $env);
    }
}
