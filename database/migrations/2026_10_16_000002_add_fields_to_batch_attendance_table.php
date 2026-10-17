<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('batch_attendance', function (Blueprint $table) {
            $table->unsignedBigInteger('lesson_id')->nullable()->index();
            $table->unsignedBigInteger('course_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('vendor_id')->nullable()->index();
            $table->string('status', 20)->default('absent');
            $table->unsignedBigInteger('marked_by')->nullable();
            $table->text('note')->nullable();
            $table->unique(['lesson_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('batch_attendance', fn (Blueprint $table) => $table->dropColumn([
            'lesson_id', 'course_id', 'user_id', 'vendor_id', 'status', 'marked_by', 'note',
        ]));
    }
};
