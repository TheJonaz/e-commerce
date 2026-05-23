<?php

namespace App\Modules;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;

class ModuleManager
{
    /** @var array<string, array<string, mixed>> */
    protected array $modules = [];

    public function __construct(protected Application $app, protected string $modulesPath) {}

    public function discover(): self
    {
        if (! is_dir($this->modulesPath)) {
            return $this;
        }

        foreach (glob($this->modulesPath . '/*', GLOB_ONLYDIR) ?: [] as $categoryDir) {
            $category = basename($categoryDir);
            foreach (glob($categoryDir . '/*', GLOB_ONLYDIR) ?: [] as $moduleDir) {
                $manifestPath = $moduleDir . '/module.json';
                if (! is_file($manifestPath)) {
                    continue;
                }

                $manifest = json_decode((string) file_get_contents($manifestPath), true);
                if (! is_array($manifest) || empty($manifest['name'])) {
                    continue;
                }

                $key = $category . '/' . $manifest['name'];
                $this->modules[$key] = array_merge($manifest, [
                    'key' => $key,
                    'category' => $category,
                    'path' => $moduleDir,
                ]);

                $this->autoloadModule($manifest, $moduleDir);

                if (! empty($manifest['provider']) && class_exists($manifest['provider'])) {
                    $this->app->register($manifest['provider']);
                }
            }
        }

        return $this;
    }

    /** Tell Composer's autoloader where the module's src/ lives. */
    protected function autoloadModule(array $manifest, string $moduleDir): void
    {
        if (empty($manifest['namespace'])) {
            return;
        }

        $srcDir = $moduleDir . '/src';
        if (! is_dir($srcDir)) {
            return;
        }

        $loader = require base_path('vendor/autoload.php');
        $loader->addPsr4(rtrim($manifest['namespace'], '\\') . '\\', $srcDir);
    }

    public function all(): Collection
    {
        return collect($this->modules);
    }

    public function ofCategory(string $category): Collection
    {
        return $this->all()->filter(fn ($m) => $m['category'] === $category)->values();
    }

    public function find(string $key): ?array
    {
        return $this->modules[$key] ?? null;
    }
}
