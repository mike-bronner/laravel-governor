<?php

declare(strict_types=1);

namespace GeneaLabs\LaravelGovernor\Listeners;

use Ramsey\Uuid\Uuid;

class CreatingInvitationListener
{
    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function handle($model)
    {
        $model->token = Uuid::uuid4();

        // Set deprecated governor_owned_by column for backward compatibility.
        // Polymorphic ownership record is created by CreatedListener after save.
        if (auth()->check()) {
            $model->governor_owned_by = auth()->user()->getKey();
        }
    }
}
