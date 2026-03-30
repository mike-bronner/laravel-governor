<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rename entity "Action (Laravel Governor)" to "Ability (Laravel Governor)"
        DB::table('governor_entities')
            ->where('name', 'Action (Laravel Governor)')
            ->update(['name' => 'Ability (Laravel Governor)']);

        // Rename entity "Ownership (Laravel Governor)" to "Owned Resource (Laravel Governor)"
        DB::table('governor_entities')
            ->where('name', 'Ownership (Laravel Governor)')
            ->update(['name' => 'Owned Resource (Laravel Governor)']);
    }

    public function down(): void
    {
        // Rollback: restore original names
        DB::table('governor_entities')
            ->where('name', 'Ability (Laravel Governor)')
            ->update(['name' => 'Action (Laravel Governor)']);

        DB::table('governor_entities')
            ->where('name', 'Owned Resource (Laravel Governor)')
            ->update(['name' => 'Ownership (Laravel Governor)']);
    }
};
