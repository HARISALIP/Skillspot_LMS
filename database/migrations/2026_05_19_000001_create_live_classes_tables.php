<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('live_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete(); // vendor/teacher who created

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('platform', 30)->default('zoom'); // zoom, google_meet, teams, other
            $table->string('meeting_url', 500)->nullable();
            $table->string('meeting_id', 100)->nullable();
            $table->string('meeting_password', 100)->nullable();

            // Schedule type
            $table->enum('schedule_type', ['hours_based', 'date_based'])->default('hours_based');

            // Hours-based fields
            $table->decimal('total_hours', 6, 2)->nullable();     // target total hours
            $table->decimal('completed_hours', 6, 2)->default(0); // auto-calculated from sessions

            // Date-based fields
            $table->date('batch_start_date')->nullable();
            $table->date('batch_end_date')->nullable();
            $table->string('batch_name', 255)->nullable();

            $table->enum('status', ['draft', 'active', 'live', 'completed', 'cancelled'])->default('draft');
            $table->integer('max_students')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('live_class_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_class_id')->constrained('live_classes')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('stopped_at')->nullable();
            $table->integer('duration_minutes')->nullable(); // auto-calculated on stop
            $table->decimal('duration_hours', 5, 2)->nullable(); // auto-calculated on stop

            $table->enum('status', ['live', 'ended'])->default('live');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('live_class_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_class_id')->constrained('live_classes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('enrolled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->timestamps();

            $table->unique(['live_class_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_class_enrollments');
        Schema::dropIfExists('live_class_sessions');
        Schema::dropIfExists('live_classes');
    }
};
