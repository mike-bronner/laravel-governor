<?php

namespace GeneaLabs\LaravelGovernor\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class LaravelGovernorAdminSeeder extends Seeder
{
    public function run()
    {
        $admins = config('genealabs-laravel-governor.admins');

        if (! $admins) {
            return;
        }

        $admins = json_decode($admins);

        if (! is_array($admins)) {
            return;
        }

        $roleClass = config("genealabs-laravel-governor.models.role");
        $adminRole = (new $roleClass)->find("Admin");
        $memberRole = (new $roleClass)->find("Member");

        if (! $adminRole || ! $memberRole) {
            $this->command?->warn('Skipping admin user setup: required roles (Admin, Member) have not been created yet.');
            Log::warning('Governor: Skipping admin user setup — required roles (Admin, Member) do not exist.');

            return;
        }

        $userModel = config('genealabs-laravel-governor.models.auth');

        if (! $userModel || ! Schema::hasTable((new $userModel)->getTable())) {
            $this->command?->warn('Skipping admin user setup: user model table does not exist.');
            Log::warning('Governor: Skipping admin user setup — user model table does not exist.');

            return;
        }

        $users = app()->make($userModel);

        foreach ($admins as $admin) {
            if ($admin->email) {
                $adminUser = $users
                    ->firstOrNew([
                        "email" => $admin->email,
                    ]);

                if (!$adminUser->exists) {
                    $adminUser->fill([
                        "name" => $admin->name,
                        "password" => bcrypt($admin->password),
                    ]);
                    $adminUser->save();
                }

                $adminUser->roles()->syncWithoutDetaching([$adminRole->name, $memberRole->name]);
            }
        }
    }
}
