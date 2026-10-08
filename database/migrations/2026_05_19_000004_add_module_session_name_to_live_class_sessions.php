<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('live_class_sessions', function (Blueprint $table) {
            $table->string('module_name')->nullable()->after('teacher_id');
            $table->string('session_name')->nullable()->after('module_name');
        });
    }
    public function down(): void
    {
        Schema::table('live_class_sessions', function (Blueprint $table) {
            $table->dropColumn(['module_name','session_name']);
        });
    }
};
