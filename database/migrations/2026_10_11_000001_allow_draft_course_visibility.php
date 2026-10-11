<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('visibility', 50)->default('draft')->change();
        });
    }

    public function down(): void
    {
        // Keeping the wider visibility values avoids invalidating existing drafts.
    }
};
