<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class LaravelGovernorSuperAdminSeeder extends Seeder
{
    public function run()
    {
        $superAdmins = config('genealabs-laravel-governor.superadmins');

        if (! $superAdmins) {
            return;
        }

        $superAdmins = json_decode($superAdmins);

        if (! is_array($superAdmins)) {
            return;
        }

        $roleClass = config("genealabs-laravel-governor.models.role");
        $superAdminRole = (new $roleClass)->find("SuperAdmin");
        $memberRole = (new $roleClass)->find("Member");

        if (! $superAdminRole || ! $memberRole) {
            $this->command?->warn('Skipping super admin user setup: required roles (SuperAdmin, Member) have not been created yet.');
            Log::warning('Governor: Skipping super admin user setup — required roles (SuperAdmin, Member) do not exist.');

            return;
        }

        $userModel = config('genealabs-laravel-governor.models.auth');

        if (! $userModel || ! Schema::hasTable((new $userModel)->getTable())) {
            $this->command?->warn('Skipping super admin user setup: user model table does not exist.');
            Log::warning('Governor: Skipping super admin user setup — user model table does not exist.');

            return;
        }

        $users = app()->make($userModel);

        foreach ($superAdmins as $superAdmin) {
            if ($superAdmin->email) {
                $superuser = $users
                    ->firstOrNew([
                        "email" => $superAdmin->email,
                    ]);

                if (!$superuser->exists) {
                    $superuser->fill([
                        "name" => $superAdmin->name,
                        "password" => bcrypt($superAdmin->password),
                    ]);
                    $superuser->save();
                }
                $superuser->roles()->syncWithoutDetaching([$superAdminRole->name, $memberRole->name]);
            }
        }
    }
}
