<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\InstructorProfile;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\User;
use App\Services\OrganizationAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeachingToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_assignment_is_graded_into_progress_and_visible_in_preview_without_writes(): void
    {
        $creator = User::factory()->create();
        InstructorProfile::create(['user_id' => $creator->id, 'status' => 'approved']);
        $student = User::factory()->create();
        $other = User::factory()->create();
        $course = Course::create(['title' => 'Graded learning', 'primary_instructor_id' => $creator->id,
            'enable_certification' => false, 'marketplace_status' => 'live', 'marketplace_visible' => true]);
        $section = Section::create(['course_id' => $course->id, 'title' => 'Chapter']);
        Enrollment::create(['user_id' => $student->id, 'course_id' => $course->id, 'status' => 'active']);
        OrganizationAccess::grant($student, $course, 'marketplace', 'test');

        $this->actingAs($creator)->post(route('courses.teaching.assignments.store', [$course, $section]),
            ['title' => 'Write a summary', 'instructions' => 'Summarise the chapter.', 'pass_score' => 70])->assertRedirect();
        $lesson = Lesson::where('section_id', $section->id)->firstOrFail();
        $assignment = $lesson->assignment;
        $this->actingAs($creator)->get(route('instructor.courses.preview.lesson', [$course, $lesson]))
            ->assertOk()->assertJsonPath('assignment.instructions', 'Summarise the chapter.');
        $this->assertSame(0, DB::table('lesson_progress')->count());

        $this->actingAs($other)->postJson(route('student.assignment.submit', [$course, $assignment]), ['answer' => 'Stolen'])->assertForbidden();
        $this->actingAs($student)->postJson(route('student.assignment.submit', [$course, $assignment]), ['answer' => 'My own work'])->assertOk();
        $submission = $assignment->submissions()->firstOrFail();
        $this->assertSame($student->id, $submission->user_id);
        $this->actingAs($student)->postJson(route('student.save-progress', [$course, $lesson]), ['progress' => 100])->assertStatus(422);
        $this->actingAs($student)->postJson(route('video.progress', $lesson), ['progress' => 100])->assertStatus(422);
        $this->assertSame(0, DB::table('lesson_progress')->count());

        $this->actingAs($creator)->patch(route('courses.teaching.submissions.grade', [$course, $submission]),
            ['score' => 85, 'feedback' => 'Good work'])->assertRedirect();
        $this->assertSame(85, $submission->fresh()->score);
        $this->assertSame(1, DB::table('lesson_progress')->where('user_id', $student->id)->where('completed', true)->count());
        $this->actingAs($student)->get(route('student.get-lesson', [$course, $lesson]))
            ->assertOk()->assertJsonPath('assignment.submission.feedback', 'Good work');
    }

    public function test_announcement_is_visible_to_learner_and_creator_preview_but_not_an_unenrolled_user(): void
    {
        $creator = User::factory()->create();
        InstructorProfile::create(['user_id' => $creator->id, 'status' => 'pending']);
        $student = User::factory()->create();
        $other = User::factory()->create();
        $course = Course::create(['title' => 'Class', 'primary_instructor_id' => $creator->id]);
        Enrollment::create(['user_id' => $student->id, 'course_id' => $course->id, 'status' => 'active']);
        OrganizationAccess::grant($student, $course, 'marketplace', 'test');
        $this->actingAs($creator)->post(route('courses.teaching.announcements.store', $course),
            ['title' => 'Schedule', 'body' => 'Class starts tomorrow.'])->assertRedirect();
        $this->actingAs($creator)->get(route('instructor.courses.preview', $course))->assertSee('Class starts tomorrow.');
        $this->actingAs($student)->get(route('student.learn', $course))->assertSee('Class starts tomorrow.');
        $this->actingAs($other)->get(route('student.learn', $course))->assertDontSee('Class starts tomorrow.');
    }

    public function test_mixed_quiz_waits_for_manual_open_answer_grading_before_progress(): void
    {
        $creator = User::factory()->create();
        InstructorProfile::create(['user_id' => $creator->id, 'status' => 'approved']);
        $student = User::factory()->create();
        $course = Course::create(['title' => 'Quiz class', 'primary_instructor_id' => $creator->id,
            'enable_certification' => false, 'marketplace_status' => 'live', 'marketplace_visible' => true]);
        $section = Section::create(['course_id' => $course->id, 'title' => 'Chapter']);
        Enrollment::create(['user_id' => $student->id, 'course_id' => $course->id, 'status' => 'active']);
        OrganizationAccess::grant($student, $course, 'marketplace', 'test');
        $this->actingAs($creator)->post(route('courses.teaching.quizzes.store', [$course,$section]), [
            'quiz_title' => 'Final quiz', 'quiz_pass_score' => 70, 'question' => 'Pick one',
            'question_type' => 'single', 'options' => "A\nB", 'correct_answer' => 2,
        ])->assertRedirect();
        $lesson = Lesson::where('type', 'quiz')->firstOrFail();
        $quiz = $lesson->quiz;
        $this->actingAs($creator)->post(route('courses.teaching.quizzes.questions.store', [$course,$quiz]), [
            'question' => 'Pick two', 'question_type' => 'multiple', 'options' => "A\nB\nC", 'correct_answers' => '1, 3',
        ])->assertRedirect();
        $this->actingAs($creator)->post(route('courses.teaching.quizzes.questions.store', [$course,$quiz]), [
            'question' => 'Explain', 'question_type' => 'open',
        ])->assertRedirect();
        $questions = $quiz->questions()->orderBy('order')->get();
        $this->assertCount(3, $questions);
        $this->actingAs($creator)->get(route('instructor.courses.preview.lesson', [$course,$lesson]))
            ->assertOk()->assertJsonPath('quiz.questions.2.type', 'open');
        $this->assertDatabaseCount('quiz_attempts', 0);

        $answers = [$questions[0]->id => 1, $questions[1]->id => [2, 0], $questions[2]->id => 'My explanation'];
        $this->actingAs($student)->postJson(route('student.submit-quiz', [$course,$lesson]), ['answers' => $answers])
            ->assertOk()->assertJsonPath('pending', true);
        $this->assertSame(0, DB::table('lesson_progress')->count());
        $attempt = $quiz->attempts()->firstOrFail();
        $this->assertSame('pending', $attempt->status);
        $this->actingAs($creator)->patch(route('courses.teaching.quizzes.attempts.grade', [$course,$attempt]), [
            'open_results' => [$questions[2]->id => 1], 'feedback' => 'Good answer',
        ])->assertRedirect();
        $this->assertSame(100, $attempt->fresh()->score);
        $this->assertTrue($attempt->fresh()->passed);
        $this->assertSame(1, DB::table('lesson_progress')->where('user_id', $student->id)->where('completed', true)->count());
    }
}
