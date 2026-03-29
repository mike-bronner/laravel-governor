<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Console\Commands;

use Illuminate\Console\Command;

class Setup extends Command
{
    protected $signature = 'governor:setup
        {--superadmin= : Email address of the user to assign as SuperAdmin}
        {--user= : ID of the user to assign as SuperAdmin}';

    protected $description = 'Set up Governor roles and assign a SuperAdmin user.';

    public function handle(): int
    {
        $email = $this->option('superadmin');
        $userId = $this->option('user');

        if (! $email && ! $userId) {
            $this->error('You must provide either --superadmin=<email> or --user=<id>.');

            return self::FAILURE;
        }

        if ($email && $userId) {
            $this->error('Provide only one of --superadmin=<email> or --user=<id>, not both.');

            return self::FAILURE;
        }

        $authModel = app()->make(config('genealabs-laravel-governor.models.auth'));

        if ($email) {
            $user = $authModel->where('email', $email)->first();

            if (! $user) {
                $this->error("No user found with email: {$email}");

                return self::FAILURE;
            }
        } else {
            $user = $authModel->find($userId);

            if (! $user) {
                $this->error("No user found with ID: {$userId}");

                return self::FAILURE;
            }
        }

        $roleClass = config('genealabs-laravel-governor.models.role');
        $superAdminRole = (new $roleClass)->find('SuperAdmin');

        if (! $superAdminRole) {
            $this->error('SuperAdmin role not found. Please run the Governor database seeder first.');

            return self::FAILURE;
        }

        $memberRole = (new $roleClass)->find('Member');
        $rolesToSync = ['SuperAdmin'];

        if ($memberRole) {
            $rolesToSync[] = 'Member';
        }

        $user->roles()->syncWithoutDetaching($rolesToSync);

        $identifier = $email ?? "ID {$userId}";
        $this->info("User {$identifier} has been assigned the SuperAdmin role.");

        return self::SUCCESS;
    }
}
