<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->string('scorm_package_name')->nullable()->after('is_scorm');
            $table->string('scorm_package_path')->nullable()->after('scorm_package_name');
            $table->string('scorm_manifest_path')->nullable()->after('scorm_package_path');
            $table->string('scorm_launch_path')->nullable()->after('scorm_manifest_path');
            $table->unsignedBigInteger('scorm_package_size')->nullable()->after('scorm_launch_path');
        });
        Schema::table('lesson_progress', function (Blueprint $table) {
            $table->text('scorm_data')->nullable()->after('watched_seconds');
        });
    }

    public function down(): void
    {
        Schema::table('lesson_progress', fn (Blueprint $table) => $table->dropColumn('scorm_data'));
        Schema::table('sections', fn (Blueprint $table) => $table->dropColumn([
            'scorm_package_name', 'scorm_package_path', 'scorm_manifest_path',
            'scorm_launch_path', 'scorm_package_size',
        ]));
    }
};
