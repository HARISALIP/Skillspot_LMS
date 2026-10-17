<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vendor_teachers') && ! Schema::hasColumn('vendor_teachers', 'added_by')) {
            Schema::table('vendor_teachers', function (Blueprint $table) {
                $table->foreignId('added_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('vendor_teachers') && Schema::hasColumn('vendor_teachers', 'added_by')) {
            Schema::table('vendor_teachers', function (Blueprint $table) {
                $table->dropConstrainedForeignId('added_by');
            });
        }
    }
};
