<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use PDO;

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

            'shop_name' => ['required', 'string', 'max:255'],
            'shop_currency' => ['required', 'string', 'size:3'],
            'shop_locale' => ['required', 'string', 'in:sv,en'],

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

        Setting::many([
            'shop.name' => $data['shop_name'],
            'shop.currency' => strtoupper($data['shop_currency']),
            'shop.locale' => $data['shop_locale'],
        ]);

        User::create([
            'name' => $data['admin_name'],
            'email' => $data['admin_email'],
            'password' => Hash::make($data['admin_password']),
            'role' => User::ROLE_ADMIN,
        ]);

        if (! empty($data['seed_demo'])) {
            Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);
        }

        $this->lock();

        return redirect('/')->with('status', 'Installation complete.');
    }

    public function testDatabase(Request $request): JsonResponse
    {
        if ($this->isLocked()) {
            abort(404);
        }

        $data = $request->validate([
            'db_connection' => ['required', Rule::in(['mysql', 'sqlite'])],
            'db_host' => ['nullable', 'string'],
            'db_port' => ['nullable', 'integer'],
            'db_database' => ['required', 'string'],
            'db_username' => ['nullable', 'string'],
            'db_password' => ['nullable', 'string'],
        ]);

        try {
            $started = microtime(true);
            $info = $this->probeDatabase($data);
            $ms = (int) round((microtime(true) - $started) * 1000);

            return response()->json([
                'ok' => true,
                'driver' => $data['db_connection'],
                'server' => $info['server'] ?? null,
                'database' => $data['db_database'],
                'duration_ms' => $ms,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => $this->humanizeDbError($e->getMessage()),
            ], 200);
        }
    }

    protected function probeDatabase(array $data): array
    {
        if ($data['db_connection'] === 'sqlite') {
            $path = $data['db_database'];

            if (! str_starts_with($path, '/') && ! str_starts_with($path, ':')) {
                $path = base_path($path);
            }

            if ($path !== ':memory:' && ! is_file($path)) {
                $dir = dirname($path);
                if (! is_dir($dir) || ! is_writable($dir)) {
                    throw new \RuntimeException("Path is not writable: $dir");
                }
            }

            $pdo = new PDO('sqlite:' . $path);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $version = $pdo->query('SELECT sqlite_version()')->fetchColumn();

            return ['server' => 'SQLite ' . $version];
        }

        $host = $data['db_host'] ?: '127.0.0.1';
        $port = $data['db_port'] ?: 3306;
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $data['db_database']);

        $pdo = new PDO($dsn, $data['db_username'] ?? '', $data['db_password'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);
        $version = $pdo->query('SELECT VERSION()')->fetchColumn();

        return ['server' => 'MySQL ' . $version];
    }

    protected function humanizeDbError(string $raw): string
    {
        if (str_contains($raw, 'Access denied')) {
            return 'Fel användarnamn eller lösenord.';
        }
        if (str_contains($raw, 'Unknown database')) {
            return 'Databasen finns inte.';
        }
        if (str_contains($raw, 'Connection refused') || str_contains($raw, 'getaddrinfo')) {
            return 'Kan inte nå databasservern (host/port).';
        }
        if (str_contains($raw, 'unable to open database file')) {
            return 'Kan inte öppna SQLite-filen (kontrollera sökväg + skrivrättigheter).';
        }

        return $raw;
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
            'APP_LOCALE' => $data['shop_locale'],
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
