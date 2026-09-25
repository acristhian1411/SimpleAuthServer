<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('oauth_client_roles', function (Blueprint $table) {
            $table->id();
            $table->uuid('client_id');
            $table->unsignedBigInteger('role_id');

            if (DB::getDriverName() !== 'sqlite') {
                $table->foreign('client_id')->references('id')->on('oauth_clients')->cascadeOnDelete();
                $table->foreign('role_id')->references('role_id')->on('roles')->cascadeOnDelete();
            }

            $table->unique(['client_id', 'role_id']);
            $table->index('client_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_client_roles');
    }
};
