<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sections', fn (Blueprint $table) => $table->boolean('is_scorm')->default(false)->after('order'));
    }

    public function down(): void
    {
        Schema::table('sections', fn (Blueprint $table) => $table->dropColumn('is_scorm'));
    }
};
