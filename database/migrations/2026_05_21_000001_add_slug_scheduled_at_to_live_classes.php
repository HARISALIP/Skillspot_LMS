<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void {
        Schema::table('live_classes', function (Blueprint $table) {
            if (!Schema::hasColumn('live_classes', 'slug')) {
                $table->string('slug')->nullable()->after('title');
            }
            if (!Schema::hasColumn('live_classes', 'scheduled_at')) {
                $table->dateTime('scheduled_at')->nullable()->after('batch_end_date');
            }
            if (!Schema::hasColumn('live_classes', 'vendor_id_assigned')) {
                // vendor_id_assigned is already there — skip
            }
        });

        // Populate slugs for existing records
        $classes = DB::table('live_classes')->get();
        foreach ($classes as $class) {
            if (empty($class->slug)) {
                $slug = \Illuminate\Support\Str::slug($class->title) . '-' . $class->id;
                DB::table('live_classes')->where('id', $class->id)->update(['slug' => $slug]);
            }
        }

        // Add unique index if not exists
        try {
            Schema::table('live_classes', function (Blueprint $table) {
                $table->unique('slug');
            });
        } catch (\Exception $e) {}
    }

    public function down(): void {
        Schema::table('live_classes', function (Blueprint $table) {
            try { $table->dropUnique(['slug']); } catch (\Exception $e) {}
            if (Schema::hasColumn('live_classes', 'slug')) $table->dropColumn('slug');
            if (Schema::hasColumn('live_classes', 'scheduled_at')) $table->dropColumn('scheduled_at');
        });
    }
};
