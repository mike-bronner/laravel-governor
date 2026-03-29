<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPolicyClassToGovernorEntitiesTable extends Migration
{
    public function up(): void
    {
        Schema::table('governor_entities', function (Blueprint $table): void {
            $table->string('policy_class')
                ->nullable()
                ->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('governor_entities', function (Blueprint $table): void {
            $table->dropColumn('policy_class');
        });
    }
}
