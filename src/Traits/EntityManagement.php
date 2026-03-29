<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Traits;

use GeneaLabs\LaravelGovernor\Exceptions\EntityNameCollisionException;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionObject;

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
        $gate = app('Illuminate\Contracts\Auth\Access\Gate');
        $reflectedGate = new ReflectionObject($gate);
        $policies = $reflectedGate->getProperty("policies");
        $policies->setAccessible(true);

        return collect($policies->getValue($gate));
    }

    public function parsePolicies(): void
    {
        $this->getPolicies()
            ->each(function ($policyClassName) {
                $this->getEntity($policyClassName);
            });
    }
}
