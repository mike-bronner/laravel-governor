<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Database\Seeders;

use GeneaLabs\LaravelGovernor\Traits\EntityManagement;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class LaravelGovernorUpgradeTo0130 extends Seeder
{
    use EntityManagement;

    public function run(): void
    {
        $this->migrateOwnedByData();
    }

    protected function migrateOwnedByData(): void
    {
        $this->getModels()
            ->each(function (string $modelClass): void {
                $model = new $modelClass;
                $table = $model->getTable();
                $connection = $model->getConnectionName();

                if (! Schema::connection($connection)->hasColumn($table, 'governor_owned_by')) {
                    return;
                }

                $records = DB::connection($connection)
                    ->table($table)
                    ->whereNotNull('governor_owned_by')
                    ->select([$model->getKeyName(), 'governor_owned_by'])
                    ->get();

                foreach ($records as $record) {
                    $keyName = $model->getKeyName();

                    DB::table('governor_ownables')->insertOrIgnore([
                        'ownable_type' => $modelClass,
                        'ownable_id' => $record->{$keyName},
                        'user_id' => $record->governor_owned_by,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    protected function getModels(): Collection
    {
        if (! is_dir(app_path())) {
            return collect();
        }

        return collect(File::allFiles(app_path()))
            ->map(function ($item) {
                $path = $item->getRelativePathName();
                $class = sprintf(
                    '\%s%s',
                    Container::getInstance()->getNamespace(),
                    strtr(substr($path, 0, strrpos($path, '.')), '/', '\\'),
                );

                return $class;
            })
            ->filter(function ($class) {
                if (! class_exists($class)) {
                    return false;
                }

                $reflection = new \ReflectionClass($class);

                return $reflection->isSubclassOf(Model::class)
                    && ! $reflection->isAbstract()
                    && in_array(
                        'GeneaLabs\LaravelGovernor\Traits\Governable',
                        class_uses_recursive($class),
                    );
            })
            ->values();
    }
}
