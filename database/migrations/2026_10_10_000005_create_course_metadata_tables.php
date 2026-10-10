<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. course_categories
        if (!Schema::hasTable('course_categories')) {
            Schema::create('course_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('icon')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('order_col')->default(0);
                $table->timestamps();
            });

            // Seed default categories
            $categories = [
                ['name' => 'Web Development', 'icon' => '💻', 'order_col' => 1],
                ['name' => 'Mobile App Development', 'icon' => '📱', 'order_col' => 2],
                ['name' => 'Data Science & AI', 'icon' => '🤖', 'order_col' => 3],
                ['name' => 'Cyber Security', 'icon' => '🛡️', 'order_col' => 4],
                ['name' => 'Cloud Computing', 'icon' => '☁️', 'order_col' => 5],
                ['name' => 'UI/UX Design', 'icon' => '🎨', 'order_col' => 6],
                ['name' => 'Digital Marketing', 'icon' => '📈', 'order_col' => 7],
                ['name' => 'Business & Management', 'icon' => '💼', 'order_col' => 8],
            ];

            foreach ($categories as $cat) {
                DB::table('course_categories')->insert([
                    'name'        => $cat['name'],
                    'slug'        => Str::slug($cat['name']),
                    'icon'        => $cat['icon'],
                    'description' => $cat['name'] . ' courses and certifications',
                    'is_active'   => true,
                    'order_col'   => $cat['order_col'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }

        // 2. course_levels
        if (!Schema::hasTable('course_levels')) {
            Schema::create('course_levels', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('order_col')->default(0);
                $table->timestamps();
            });

            // Seed default levels
            $levels = [
                ['name' => 'All Levels', 'slug' => 'all-levels', 'order_col' => 1],
                ['name' => 'Beginner', 'slug' => 'beginner', 'order_col' => 2],
                ['name' => 'Intermediate', 'slug' => 'intermediate', 'order_col' => 3],
                ['name' => 'Advanced', 'slug' => 'advanced', 'order_col' => 4],
            ];

            foreach ($levels as $lvl) {
                DB::table('course_levels')->insert([
                    'name'        => $lvl['name'],
                    'slug'        => $lvl['slug'],
                    'description' => $lvl['name'] . ' difficulty level',
                    'is_active'   => true,
                    'order_col'   => $lvl['order_col'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }

        // 3. course_languages
        if (!Schema::hasTable('course_languages')) {
            Schema::create('course_languages', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 10)->unique();
                $table->boolean('is_active')->default(true);
                $table->integer('order_col')->default(0);
                $table->timestamps();
            });

            // Seed default languages
            $languages = [
                ['name' => 'English', 'code' => 'en', 'order_col' => 1],
                ['name' => 'Hindi', 'code' => 'hi', 'order_col' => 2],
                ['name' => 'Spanish', 'code' => 'es', 'order_col' => 3],
                ['name' => 'French', 'code' => 'fr', 'order_col' => 4],
                ['name' => 'German', 'code' => 'de', 'order_col' => 5],
                ['name' => 'Arabic', 'code' => 'ar', 'order_col' => 6],
            ];

            foreach ($languages as $lang) {
                DB::table('course_languages')->insert([
                    'name'       => $lang['name'],
                    'code'       => $lang['code'],
                    'is_active'  => true,
                    'order_col'  => $lang['order_col'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_languages');
        Schema::dropIfExists('course_levels');
        Schema::dropIfExists('course_categories');
    }
};
