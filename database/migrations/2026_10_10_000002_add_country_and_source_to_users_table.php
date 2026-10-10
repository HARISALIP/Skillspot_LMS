<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'country')) {
                $table->string('country', 10)->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'country_name')) {
                $table->string('country_name', 100)->nullable()->after('country');
            }
            if (!Schema::hasColumn('users', 'registered_via')) {
                $table->string('registered_via', 50)->nullable()->default('web')->after('country_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'country')) {
                $table->dropColumn(['country', 'country_name', 'registered_via']);
            }
        });
    }
};
