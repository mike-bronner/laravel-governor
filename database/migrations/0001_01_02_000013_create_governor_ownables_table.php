<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGovernorOwnablesTable extends Migration
{
    public function __construct()
    {
        if (app()->bound("Hyn\Tenancy\Environment")) {
            $this->connection = config("tenancy.db.tenant-connection-name");
        }
    }

    public function up(): void
    {
        Schema::create('governor_ownables', function (Blueprint $table): void {
            $table->id();
            $table->morphs('ownable');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->unique(['ownable_type', 'ownable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('governor_ownables');
    }
}
