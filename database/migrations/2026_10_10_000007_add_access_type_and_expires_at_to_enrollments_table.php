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
        if (Schema::hasTable('enrollments')) {
            Schema::table('enrollments', function (Blueprint $table) {
                if (!Schema::hasColumn('enrollments', 'access_type')) {
                    $table->string('access_type')->default('lifetime');
                }
                if (!Schema::hasColumn('enrollments', 'expires_at')) {
                    $table->timestamp('expires_at')->nullable();
                }
                if (!Schema::hasColumn('enrollments', 'granted_by')) {
                    $table->unsignedBigInteger('granted_by')->nullable();
                }
                if (!Schema::hasColumn('enrollments', 'notes')) {
                    $table->text('notes')->nullable();
                }
                if (!Schema::hasColumn('enrollments', 'is_restricted')) {
                    $table->boolean('is_restricted')->default(false);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('enrollments')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $columns = ['access_type', 'expires_at', 'granted_by', 'notes', 'is_restricted'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('enrollments', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
