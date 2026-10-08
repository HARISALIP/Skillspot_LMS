<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('lessons', function (Blueprint $table) {
            if (!Schema::hasColumn('lessons', 'recording_url')) {
                $table->string('recording_url')->nullable();
            }
            if (!Schema::hasColumn('lessons', 'recording_shared')) {
                $table->boolean('recording_shared')->default(false);
            }
            if (!Schema::hasColumn('lessons', 'live_share_link')) {
                $table->string('live_share_link')->nullable();
            }
        });
    }
    public function down(): void {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['recording_url','recording_shared','live_share_link']);
        });
    }
};
