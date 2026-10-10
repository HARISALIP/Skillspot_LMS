<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('lessons')) {
            Schema::table('lessons', function (Blueprint $table) {
                if (!Schema::hasColumn('lessons', 'description')) {
                    $table->text('description')->nullable();
                }
                if (!Schema::hasColumn('lessons', 'video_url')) {
                    $table->string('video_url')->nullable();
                }
                if (!Schema::hasColumn('lessons', 'video_storage')) {
                    $table->string('video_storage')->nullable();
                }
                if (!Schema::hasColumn('lessons', 'video_r2_path')) {
                    $table->string('video_r2_path')->nullable();
                }
                if (!Schema::hasColumn('lessons', 'live_platform')) {
                    $table->string('live_platform')->nullable();
                }
                if (!Schema::hasColumn('lessons', 'live_url')) {
                    $table->string('live_url')->nullable();
                }
                if (!Schema::hasColumn('lessons', 'live_share_link')) {
                    $table->string('live_share_link')->nullable();
                }
                if (!Schema::hasColumn('lessons', 'live_scheduled_at')) {
                    $table->timestamp('live_scheduled_at')->nullable();
                }
                if (!Schema::hasColumn('lessons', 'live_duration_min')) {
                    $table->integer('live_duration_min')->nullable()->default(60);
                }
                if (!Schema::hasColumn('lessons', 'live_meeting_id')) {
                    $table->string('live_meeting_id')->nullable();
                }
                if (!Schema::hasColumn('lessons', 'live_password')) {
                    $table->string('live_password')->nullable();
                }
                if (!Schema::hasColumn('lessons', 'notes')) {
                    $table->text('notes')->nullable();
                }
                if (!Schema::hasColumn('lessons', 'resources')) {
                    $table->json('resources')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('lessons')) {
            Schema::table('lessons', function (Blueprint $table) {
                $columns = [
                    'description', 'video_url', 'video_storage', 'video_r2_path',
                    'live_platform', 'live_url', 'live_share_link', 'live_scheduled_at',
                    'live_duration_min', 'live_meeting_id', 'live_password', 'notes', 'resources'
                ];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('lessons', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
