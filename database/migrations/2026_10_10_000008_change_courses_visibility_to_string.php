<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('courses')) {
            if (!Schema::hasColumn('courses', 'visibility')) {
                Schema::table('courses', function (Blueprint $table) {
                    $table->string('visibility', 50)->default('draft')->after('is_published');
                });
            } else {
                $driver = DB::getDriverName();
                if ($driver === 'mysql') {
                    DB::statement("ALTER TABLE `courses` MODIFY COLUMN `visibility` VARCHAR(50) NOT NULL DEFAULT 'draft'");
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op for rollback safety
    }
};
