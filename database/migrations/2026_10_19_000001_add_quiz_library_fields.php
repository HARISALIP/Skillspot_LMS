<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->foreignId('lesson_id')->nullable()->change();
            $table->unsignedInteger('max_attempts')->nullable()->after('time_limit');
            $table->boolean('show_answers')->default(false);
            $table->boolean('show_submission_history')->default(false);
            $table->boolean('shuffle_questions')->default(false);
            $table->unsignedInteger('limit_questions_to')->nullable();
            $table->boolean('enable_negative_marking')->default(false);
            $table->unsignedInteger('marks_to_cut')->nullable();
            $table->boolean('enable_proctoring')->default(false);
            $table->unsignedInteger('max_violations')->nullable();
            $table->boolean('enable_scheduling')->default(false);
            $table->timestamp('schedule_start')->nullable();
            $table->timestamp('schedule_end')->nullable();
        });
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->unsignedInteger('marks')->default(1)->after('question_type');
            $table->json('accepted_answers')->nullable()->after('correct_answers');
            $table->json('explanations')->nullable()->after('accepted_answers');
        });
        Schema::table('lessons', function (Blueprint $table) {
            $table->foreignId('quiz_id')->nullable()->after('section_id')->constrained('quizzes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lessons', fn (Blueprint $table) => $table->dropConstrainedForeignId('quiz_id'));
        Schema::table('quiz_questions', fn (Blueprint $table) => $table->dropColumn(['marks', 'accepted_answers', 'explanations']));
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_id');
            $table->dropColumn(['max_attempts', 'show_answers', 'show_submission_history', 'shuffle_questions', 'limit_questions_to', 'enable_negative_marking', 'marks_to_cut', 'enable_proctoring', 'max_violations', 'enable_scheduling', 'schedule_start', 'schedule_end']);
            $table->foreignId('lesson_id')->nullable(false)->change();
        });
    }
};
