<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Observers;

use GeneaLabs\LaravelGovernor\GovernorCache;
use Illuminate\Database\Eloquent\Model;

class LookupTableObserver
{
    public function __construct(protected GovernorCache $cache) {}

    public function saved(Model $model): void
    {
        $this->invalidate();
    }

    public function deleted(Model $model): void
    {
        $this->invalidate();
    }

    protected function invalidate(): void
    {
        $this->cache->flush();

        app()->forgetInstance('governor-actions');
        app()->forgetInstance('governor-entities');
        app()->forgetInstance('governor-permissions');
        app()->forgetInstance('governor-roles');
    }
}
