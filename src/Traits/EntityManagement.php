<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Traits;

use GeneaLabs\LaravelGovernor\Exceptions\EntityNameCollisionException;
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

            if ($packageName) {
                $packageName = trim(implode(" ", preg_split('/(?=[A-Z])/', $packageName)));
            }

            if (! $packageName) {
                return "";
            }

            $entityName .= " ({$packageName})";
        }

        $normalizedName = ucwords($entityName);

        $entity = app("governor-entities")
            ->where("name", $normalizedName)
            ->first();

        if ($entity) {
            if ($entity->policy_class
                && $entity->policy_class !== $policyClassName
            ) {
                throw new EntityNameCollisionException(
                    $normalizedName,
                    $entity->policy_class,
                    $policyClassName
                );
            }

            if (! $entity->policy_class) {
                (new $entityClass)
                    ->where("name", $normalizedName)
                    ->update(["policy_class" => $policyClassName]);
            }

            return $entity->name;
        }

        $entity = (new $entityClass)->firstOrCreate(
            ["name" => $normalizedName],
            ["policy_class" => $policyClassName]
        );

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
        return cache()->remember("genealabs:laravel-governor:policies", 300, function (): Collection {
            $gate = app(Gate::class);

            $registeredPolicies = collect($gate->policies());

            $autoDiscoveredPolicies = $this->discoverPolicies($gate);

            return $registeredPolicies->merge($autoDiscoveredPolicies)->unique();
        });
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

        if ($contents === false) {
            return null;
        }

        $tokens = token_get_all($contents);
        $namespace = null;
        $className = null;

        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            if (! is_array($tokens[$i])) {
                continue;
            }

            if ($tokens[$i][0] === T_NAMESPACE) {
                $namespaceParts = [];

                for ($j = $i + 1; $j < $count; $j++) {
                    if (is_array($tokens[$j]) && in_array($tokens[$j][0], [T_NAME_QUALIFIED, T_STRING], true)) {
                        $namespaceParts[] = $tokens[$j][1];
                    } elseif (! is_array($tokens[$j]) && $tokens[$j] === ';') {
                        break;
                    }
                }

                $namespace = implode('\\', $namespaceParts);
            }

            if ($tokens[$i][0] === T_CLASS
                && isset($tokens[$i - 1])
                && (! is_array($tokens[$i - 1]) || $tokens[$i - 1][0] !== T_DOUBLE_COLON)
            ) {
                for ($j = $i + 1; $j < $count; $j++) {
                    if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                        $className = $tokens[$j][1];
                        break 2;
                    }
                }
            }
        }

        if ($namespace === null || $className === null) {
            return null;
        }

        return $namespace . '\\' . $className;
    }

    protected function guessModelForPolicy(string $policyClass): ?string
    {
        $policyBaseName = class_basename($policyClass);
        $modelName = Str::replaceLast('Policy', '', $policyBaseName);

        if (empty($modelName)) {
            return null;
        }

        $candidates = $this->buildModelCandidates($policyClass, $modelName);

        foreach ($candidates as $candidate) {
            if (class_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    protected function buildModelCandidates(string $policyClass, string $modelName): array
    {
        $policyNamespace = Str::beforeLast($policyClass, '\\');
        $candidates = [];

        // Standard Laravel: App\Policies\FooPolicy -> App\Models\Foo
        $modelsNamespace = Str::replaceLast('\\Policies', '\\Models', $policyNamespace);
        $candidates[] = $modelsNamespace . '\\' . $modelName;

        // Flat app namespace: App\Policies\FooPolicy -> App\Foo
        $appNamespace = Str::replaceLast('\\Policies', '', $policyNamespace);
        $candidates[] = $appNamespace . '\\' . $modelName;

        // Package convention: Vendor\Package\Policies\FooPolicy -> Vendor\Package\Foo
        $candidates[] = $appNamespace . '\\' . $modelName;

        // Composer autoloader: try all registered PSR-4 namespaces
        $autoloader = $this->getComposerAutoloader();

        if ($autoloader !== null) {
            foreach ($autoloader->getPrefixesPsr4() as $prefix => $paths) {
                $candidate = $prefix . 'Models\\' . $modelName;

                if (class_exists($candidate)) {
                    $candidates[] = $candidate;
                }

                $candidate = $prefix . $modelName;

                if (class_exists($candidate)) {
                    $candidates[] = $candidate;
                }
            }
        }

        return array_unique($candidates);
    }

    protected function getComposerAutoloader(): ?\Composer\Autoload\ClassLoader
    {
        foreach (spl_autoload_functions() as $autoloader) {
            if (is_array($autoloader) && $autoloader[0] instanceof \Composer\Autoload\ClassLoader) {
                return $autoloader[0];
            }
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
