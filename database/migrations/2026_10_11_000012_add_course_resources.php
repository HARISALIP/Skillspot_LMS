<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('courses', 'resources')) {
            Schema::table('courses', fn (Blueprint $table) => $table->json('resources')->nullable());
        }
    }

    public function down(): void
    {
        // Preserve uploaded course materials during rollback.
    }
};
