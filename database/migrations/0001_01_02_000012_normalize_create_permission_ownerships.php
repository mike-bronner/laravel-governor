<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        $affected = DB::table('governor_permissions')
            ->where('action_name', 'create')
            ->whereNotIn('ownership_name', ['no', 'any'])
            ->get();

        if ($affected->isNotEmpty()) {
            Log::warning(
                'Normalizing create permission ownerships: '
                . $affected->count()
                . ' records with invalid ownership values will be updated to "any".',
                ['affected_ids' => $affected->pluck('id')->toArray()]
            );
        }

        DB::table('governor_permissions')
            ->where('action_name', 'create')
            ->whereNotIn('ownership_name', ['no', 'any'])
            ->update(['ownership_name' => 'any']);
    }
};
