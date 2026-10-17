<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->string('question_type', 20)->default('single');
            $table->json('correct_answers')->nullable();
        });
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->string('status', 20)->default('graded');
            $table->json('manual_results')->nullable();
            $table->text('feedback')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('graded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', fn (Blueprint $table) => $table->dropColumn(['status', 'manual_results', 'feedback', 'graded_by', 'graded_at']));
        Schema::table('quiz_questions', fn (Blueprint $table) => $table->dropColumn(['question_type', 'correct_answers']));
    }
};
