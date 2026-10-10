<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', fn (Blueprint $table) => $table->foreignId('vendor_id')->nullable()->change());
    }

    public function down(): void
    {
        // Existing Skillspot enrollments may have no vendor.
    }
};
