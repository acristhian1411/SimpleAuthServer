<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('users', 'reset_password')) {
            DB::table('users')->whereNull('reset_password')->update(['reset_password' => false]);

            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('reset_password')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'reset_password')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('reset_password');
            });
        }
    }
};
