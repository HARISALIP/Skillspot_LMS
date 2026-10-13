<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->text('tags')->nullable()->after('category');
            $table->text('meta_description')->nullable()->after('description');
            $table->text('meta_keywords')->nullable()->after('meta_description');
            $table->string('thumbnail_color', 7)->nullable()->after('thumbnail');
            $table->json('related_course_ids')->nullable()->after('resources');
            $table->boolean('upcoming')->default(false)->after('is_featured');
            $table->boolean('enforce_lesson_completion')->default(false)->after('upcoming');
            $table->boolean('enable_certification')->default(true)->after('enforce_lesson_completion');
        });
    }

    public function down(): void
    {
        Schema::table('courses', fn (Blueprint $table) => $table->dropColumn([
            'tags', 'meta_description', 'meta_keywords', 'thumbnail_color', 'related_course_ids',
            'upcoming', 'enforce_lesson_completion', 'enable_certification',
        ]));
    }
};
