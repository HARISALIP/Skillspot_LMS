<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('course_allowed_users', 'added_by')) {
            Schema::table('course_allowed_users', fn (Blueprint $table) => $table->foreignId('added_by')->nullable());
        }

        if (!Schema::hasColumn('course_allowed_users', 'note')) {
            Schema::table('course_allowed_users', fn (Blueprint $table) => $table->text('note')->nullable());
        }
    }

    public function down(): void
    {
        // Preserve access metadata if this repair is rolled back.
    }
};
