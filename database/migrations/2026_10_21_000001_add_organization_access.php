<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('vendor_users', 'added_by')) {
            Schema::table('vendor_users', fn (Blueprint $table) => $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete());
        }
        Schema::table('vendors', function (Blueprint $table) {
            $table->decimal('organization_fee_percent', 5, 2)->default(0);
        });
        Schema::table('courses', function (Blueprint $table) {
            $table->string('organization_audience', 16)->default('all');
            $table->boolean('organization_published')->default(false);
        });
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->string('sale_channel', 16)->default('marketplace');
        });
        Schema::table('marketplace_sales', function (Blueprint $table) {
            $table->string('sale_channel', 16)->default('marketplace');
        });
        Schema::create('organization_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->string('name', 120);
            $table->timestamps();
            $table->unique(['vendor_id', 'name']);
        });
        Schema::create('organization_class_students', function (Blueprint $table) {
            $table->foreignId('organization_class_id')->constrained('organization_classes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['organization_class_id', 'user_id']);
        });
        Schema::create('organization_class_courses', function (Blueprint $table) {
            $table->foreignId('organization_class_id')->constrained('organization_classes')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->primary(['organization_class_id', 'course_id']);
        });
        Schema::create('course_access_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('channel', 16);
            $table->string('source', 32);
            $table->foreignId('payment_order_id')->nullable()->constrained('payment_orders')->nullOnDelete();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'course_id', 'channel', 'source'], 'course_access_source_unique');
        });
        Schema::create('organization_access_migration_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->unique()->constrained('enrollments')->cascadeOnDelete();
            $table->string('reason', 100);
            $table->timestamps();
        });

        DB::table('courses')->whereNotNull('vendor_id')
            ->where(fn ($query) => $query->where('is_published', true)
                ->orWhereExists(fn ($enrollments) => $enrollments->selectRaw('1')->from('enrollments')->whereColumn('enrollments.course_id', 'courses.id')))
            ->update(['organization_published' => true]);
        DB::table('payment_orders')->orderBy('id')->chunkById(500, function ($orders) {
            foreach ($orders as $order) {
                $course = DB::table('courses')->where('id', $order->course_id)->first(['vendor_id', 'marketplace_visible', 'marketplace_status']);
                if (!$course?->vendor_id || ($course->marketplace_status === 'live' && $course->marketplace_visible)) continue;
                DB::table('payment_orders')->where('id', $order->id)->update(['sale_channel' => 'organization']);
                DB::table('marketplace_sales')->where('payment_order_id', $order->id)->update(['sale_channel' => 'organization']);
            }
        });
        DB::table('enrollments')->orderBy('id')->chunkById(500, function ($enrollments) {
            foreach ($enrollments as $enrollment) {
                $course = DB::table('courses')->where('id', $enrollment->course_id)->first(['vendor_id', 'marketplace_visible', 'marketplace_status']);
                if (!$course) continue;
                $member = $course->vendor_id && DB::table('student_vendor_access')
                    ->where('vendor_id', $course->vendor_id)->where('user_id', $enrollment->user_id)->exists();
                $publicMarketplace = $course->marketplace_status === 'live' && $course->marketplace_visible;
                $channels = [];
                if ($member) $channels[] = 'organization';
                if (!$course->vendor_id || $publicMarketplace) $channels[] = 'marketplace';
                if ($course->vendor_id && !$member && !$publicMarketplace) {
                    DB::table('student_vendor_access')->insertOrIgnore([
                        'user_id' => $enrollment->user_id, 'vendor_id' => $course->vendor_id,
                        'note' => 'recovered from legacy enrollment', 'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $channels[] = 'organization';
                }
                if ($course->vendor_id && (($member && $publicMarketplace) || (!$member && !$publicMarketplace))) {
                    DB::table('organization_access_migration_issues')->insertOrIgnore([
                        'enrollment_id' => $enrollment->id,
                        'reason' => $member ? 'Both channels plausible; both retained' : 'Missing membership recovered from enrollment',
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                foreach ($channels as $channel) {
                    DB::table('course_access_grants')->insertOrIgnore([
                        'user_id' => $enrollment->user_id, 'course_id' => $enrollment->course_id,
                        'channel' => $channel, 'source' => 'legacy',
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        // Preserve vendor_users.added_by on rollback: older sites may already have it.
        Schema::dropIfExists('organization_access_migration_issues');
        Schema::dropIfExists('course_access_grants');
        Schema::dropIfExists('organization_class_courses');
        Schema::dropIfExists('organization_class_students');
        Schema::dropIfExists('organization_classes');
        Schema::table('marketplace_sales', fn (Blueprint $table) => $table->dropColumn('sale_channel'));
        Schema::table('payment_orders', fn (Blueprint $table) => $table->dropColumn('sale_channel'));
        Schema::table('courses', fn (Blueprint $table) => $table->dropColumn(['organization_audience', 'organization_published']));
        Schema::table('vendors', fn (Blueprint $table) => $table->dropColumn('organization_fee_percent'));
    }
};
