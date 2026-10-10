<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('lesson_progress', 'progress')) {
            Schema::table('lesson_progress', fn (Blueprint $table) => $table->unsignedTinyInteger('progress')->default(0));
        }

        if (!Schema::hasColumn('lesson_progress', 'completed_at')) {
            Schema::table('lesson_progress', fn (Blueprint $table) => $table->timestamp('completed_at')->nullable());
        }

        if (!Schema::hasColumn('certificates', 'vendor_id')) {
            Schema::table('certificates', fn (Blueprint $table) => $table->foreignId('vendor_id')->nullable());
        }

        if (!Schema::hasColumn('certificates', 'enrollment_id')) {
            Schema::table('certificates', fn (Blueprint $table) => $table->foreignId('enrollment_id')->nullable());
        }
    }

    public function down(): void
    {
        // Preserve saved lesson progress if this repair is rolled back.
    }
};
