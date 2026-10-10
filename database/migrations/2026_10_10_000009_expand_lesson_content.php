<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', fn (Blueprint $table) => $table->longText('content')->nullable()->change());
    }

    public function down(): void
    {
        // Keep existing lesson content instead of truncating it to 255 characters.
    }
};
