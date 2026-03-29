<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Traits;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait EntityManagement
{
    protected function getEntity(string $policyClassName): string
    {
        $entityClass = config("genealabs-laravel-governor.models.entity");
        $nameSpaceParts = collect(explode('\\', $policyClassName));
        $policyClass = $nameSpaceParts->last();
        $entityName = str_replace('Policy', '', $policyClass);
        $entityName = trim(implode(" ", preg_split('/(?=[A-Z])/', $entityName)));

        if (! Str::contains($policyClassName, "App")) {
            $nameSpaceParts->shift();
            $packageName = $nameSpaceParts->shift();
            $packageName = trim(implode(" ", preg_split('/(?=[A-Z])/', $packageName)));
            $entityName .= " ({$packageName})";
        }

        $entity = app("governor-entities")
            ->where("name", ucwords($entityName))
            ->first();

        if (! $entity) {
            $entity = (new $entityClass())->firstOrCreate([
                "name" => ucwords($entityName),
            ]);
        }

        return $entity->name;
    }

    public function getEntityFromModel(string $modelClass): string
    {
        $policy = app(Gate::class)
            ->getPolicyFor($modelClass);

        if (! $policy) {
            return "";
        }

        return $this->getEntity(get_class($policy));
    }

    protected function getPolicies(): Collection
    {
        $gate = app(Gate::class);

        $registeredPolicies = collect($gate->policies());

        $autoDiscoveredPolicies = $this->discoverPolicies($gate);

        return $registeredPolicies->merge($autoDiscoveredPolicies)->unique();
    }

    protected function discoverPolicies(Gate $gate): Collection
    {
        $discovered = collect();

        $policyPaths = config(
            'genealabs-laravel-governor.policy_paths',
            [app_path('Policies')],
        );

        foreach ($policyPaths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            $this->discoverPoliciesInPath($path, $gate, $discovered);
        }

        return $discovered;
    }

    protected function discoverPoliciesInPath(
        string $path,
        Gate $gate,
        Collection $discovered,
    ): void {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path),
        );

        foreach ($files as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            $className = $this->resolveClassNameFromFile($file->getPathname());

            if ($className === null || ! class_exists($className)) {
                continue;
            }

            if (! Str::endsWith($className, 'Policy')) {
                continue;
            }

            $modelClass = $this->guessModelForPolicy($className);

            if ($modelClass === null) {
                continue;
            }

            $resolvedPolicy = $gate->getPolicyFor($modelClass);

            if ($resolvedPolicy !== null && get_class($resolvedPolicy) === $className) {
                $discovered->put($modelClass, $className);
            }
        }
    }

    protected function resolveClassNameFromFile(string $filePath): ?string
    {
        $contents = file_get_contents($filePath);

        if (! preg_match('/namespace\s+(.+?);/', $contents, $nsMatch)) {
            return null;
        }

        if (! preg_match('/class\s+(\w+)/', $contents, $classMatch)) {
            return null;
        }

        return $nsMatch[1] . '\\' . $classMatch[1];
    }

    protected function guessModelForPolicy(string $policyClass): ?string
    {
        $policyBaseName = class_basename($policyClass);
        $modelName = Str::replaceLast('Policy', '', $policyBaseName);

        if (empty($modelName)) {
            return null;
        }

        $policyNamespace = Str::beforeLast($policyClass, '\\');
        $modelNamespace = Str::replaceLast('\\Policies', '\\Models', $policyNamespace);

        if (class_exists($modelNamespace . '\\' . $modelName)) {
            return $modelNamespace . '\\' . $modelName;
        }

        $appNamespace = Str::replaceLast('\\Policies', '', $policyNamespace);

        if (class_exists($appNamespace . '\\' . $modelName)) {
            return $appNamespace . '\\' . $modelName;
        }

        return null;
    }

    public function parsePolicies(): void
    {
        $this->getPolicies()
            ->each(function ($policyClassName) {
                $this->getEntity($policyClassName);
            });
    }
}
