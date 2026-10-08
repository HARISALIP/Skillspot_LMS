<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('courses', function (Blueprint $table) {
            $table->unsignedInteger('max_students')->default(0)->after('is_featured'); // 0 = unlimited
            $table->enum('course_type', ['open','one_time','batch'])->default('open')->after('max_students');
            $table->string('batch_name')->nullable()->after('course_type');
            $table->date('batch_start_date')->nullable()->after('batch_name');
            $table->date('batch_end_date')->nullable()->after('batch_start_date');
            $table->boolean('registration_open')->default(true)->after('batch_end_date');
            $table->foreignId('vendor_id')->nullable()->change();
        });
    }
    public function down(): void {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['max_students','course_type','batch_name','batch_start_date','batch_end_date','registration_open']);
        });
    }
};
