<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Section;
use App\Models\User;
use App\Services\CourseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QuizLibraryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->create();
        $user->assignRole('admin');
        return $user;
    }

    public function test_admin_can_create_a_reusable_quiz_with_frappe_style_settings(): void
    {
        $admin = $this->admin();
        $payload = [
            'title' => 'Marketing knowledge check', 'pass_score' => 70, 'time_limit' => 15, 'max_attempts' => 2,
            'shuffle_questions' => 1, 'limit_questions_to' => 1, 'enable_negative_marking' => 1, 'marks_to_cut' => 1,
            'enable_scheduling' => 1, 'schedule_start' => now()->subHour()->format('Y-m-d H:i:s'),
            'questions' => [[
                'question' => 'Which channel builds awareness?', 'type' => 'single', 'marks' => 2,
                'options' => ['Search', 'Social'], 'explanations' => ['Paid discovery', 'Best for engagement'], 'correct_answer' => 1,
            ], [
                'question' => 'Write the campaign goal', 'type' => 'user_input', 'marks' => 1,
                'accepted_answers' => ['increase qualified leads'],
            ]],
        ];

        $this->actingAs($admin)->post(route('admin.quizzes.store'), $payload)->assertRedirect();
        $quiz = Quiz::where('title', 'Marketing knowledge check')->firstOrFail();
        $this->assertNull($quiz->lesson_id);
        $this->assertSame($admin->id, $quiz->owner_id);
        $this->assertTrue($quiz->shuffle_questions);
        $this->assertSame(2, $quiz->questions()->where('question_type', 'single')->value('marks'));
        $this->assertSame(['Paid discovery', 'Best for engagement'], $quiz->questions()->where('question_type', 'single')->value('explanations'));
        $this->assertSame(['increase qualified leads'], $quiz->questions()->where('question_type', 'user_input')->value('accepted_answers'));
    }

    public function test_title_first_creation_makes_an_empty_quiz_for_the_question_editor(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->postJson(route('admin.quizzes.store'), [
            'title' => 'Untitled knowledge check', 'pass_score' => 100,
        ])->assertCreated()->assertJsonPath('edit_url', route('admin.quizzes.edit', 1));

        $quiz = Quiz::where('title', 'Untitled knowledge check')->firstOrFail();
        $this->assertSame(0, $quiz->questions()->count());
    }

    public function test_quiz_editor_matches_the_frappe_creation_workspace_and_lists_bank_questions(): void
    {
        $admin = $this->admin();
        $quiz = Quiz::create(['owner_id' => $admin->id, 'title' => 'Campaign quiz', 'pass_score' => 70]);
        $source = Quiz::create(['owner_id' => $admin->id, 'title' => 'Question source', 'pass_score' => 70]);
        $source->questions()->create([
            'question' => 'Which channel builds awareness?', 'question_type' => 'single', 'marks' => 2,
            'options' => ['Search', 'Social'], 'correct_answer' => '1',
        ]);

        $this->actingAs($admin)->get(route('admin.quizzes.edit', $quiz))
            ->assertOk()
            ->assertSee('Search questions')
            ->assertSee('New question')
            ->assertSee('Question bank')
            ->assertSee('Save quiz')
            ->assertDontSee('Name the quiz first')
            ->assertSee('Which channel builds awareness?')
            ->assertSee('Show Submission History')
            ->assertSee('Enable Scheduling');
    }

    public function test_quiz_question_preserves_safe_rich_content(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.quizzes.store'), [
            'title' => 'Rich question quiz', 'pass_score' => 70,
            'questions' => [[
                'question' => '<h2>Campaign objective</h2><p><strong>Choose</strong> the right goal.</p><script>alert(1)</script>',
                'type' => 'open', 'marks' => 1,
            ]],
        ])->assertRedirect();

        $question = Quiz::where('title', 'Rich question quiz')->firstOrFail()->questions()->sole();
        $this->assertStringContainsString('<h2>Campaign objective</h2>', $question->question);
        $this->assertStringContainsString('<strong>Choose</strong>', $question->question);
        $this->assertStringNotContainsString('<script>', $question->question);
    }

    public function test_choice_question_ignores_fields_from_other_question_types(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.quizzes.store'), [
            'title' => 'Choice question', 'pass_score' => 70,
            'questions' => [[
                'question' => '<p>Which channel?</p>', 'type' => 'single', 'marks' => 1,
                'options' => ['Search', 'Social'], 'correct_answer' => 0,
                'accepted_answers' => [['not applicable']],
            ]],
        ])->assertRedirect();

        $question = Quiz::where('title', 'Choice question')->firstOrFail()->questions()->sole();
        $this->assertNull($question->accepted_answers);
        $this->assertSame(['Search', 'Social'], $question->options);
    }

    public function test_existing_quiz_saves_through_the_json_update_endpoint(): void
    {
        $admin = $this->admin();
        $quiz = Quiz::create(['owner_id' => $admin->id, 'title' => 'Before autosave', 'pass_score' => 70]);

        $this->actingAs($admin)->putJson(route('admin.quizzes.update', $quiz), [
            'title' => 'After autosave', 'pass_score' => 85, 'show_answers' => true,
        ])->assertOk()->assertJsonStructure(['saved_at']);

        $this->assertDatabaseHas('quizzes', ['id' => $quiz->id, 'title' => 'After autosave', 'pass_score' => 85, 'show_answers' => true]);
    }

    public function test_quiz_list_links_to_the_frappe_style_questions_and_submissions_pages(): void
    {
        $admin = $this->admin();
        $quiz = Quiz::create(['owner_id' => $admin->id, 'title' => 'Row hitbox quiz', 'pass_score' => 70]);

        $this->actingAs($admin)->get(route('admin.quizzes'))
            ->assertOk()
            ->assertSee('Total Marks')
            ->assertSee('data-edit-url="'.route('admin.quizzes.edit', $quiz).'"', false)
            ->assertSee(route('admin.quizzes.questions'), false)
            ->assertSee(route('admin.quizzes.submissions'), false);
        $this->get(route('admin.quizzes.questions'))->assertOk()->assertSee('No questions found.');
        $this->get(route('admin.quizzes.submissions'))->assertOk()->assertSee('No submissions found.');
    }

    public function test_course_lesson_can_select_a_saved_quiz_without_cloning_it(): void
    {
        $admin = $this->admin();
        $course = Course::create(['title' => 'Digital Marketing', 'primary_instructor_id' => $admin->id]);
        $section = Section::create(['course_id' => $course->id, 'title' => 'Campaigns']);
        $quiz = Quiz::create(['owner_id' => $admin->id, 'title' => 'Campaign quiz', 'pass_score' => 70]);
        $quiz->questions()->create(['question' => 'Pick a channel', 'question_type' => 'single', 'marks' => 1, 'options' => ['Search', 'Social'], 'correct_answer' => '0']);

        $this->actingAs($admin)->post(route('admin.courses.lessons.store', [$course, $section]), [
            'title' => 'Campaign assessment', 'type' => 'quiz', 'quiz_id' => $quiz->id,
        ])->assertRedirect(route('admin.courses.edit', $course));

        $lesson = Lesson::where('title', 'Campaign assessment')->firstOrFail();
        $this->assertSame($quiz->id, $lesson->quiz_id);
        $this->assertSame($quiz->id, $lesson->selectedQuiz->id);
    }

    public function test_selected_quiz_is_delivered_and_graded_inside_a_text_lesson(): void
    {
        $owner = $this->admin();
        $student = User::factory()->create();
        $course = Course::create(['title' => 'Selected quiz course', 'visibility' => 'public', 'is_published' => true,
            'is_free' => true, 'marketplace_status' => 'live', 'marketplace_visible' => true]);
        $section = Section::create(['course_id' => $course->id, 'title' => 'Chapter']);
        $quiz = Quiz::create(['owner_id' => $owner->id, 'title' => 'Reusable quiz', 'pass_score' => 70]);
        $question = $quiz->questions()->create(['question' => 'Name the funnel stage', 'question_type' => 'user_input', 'marks' => 1,
            'options' => [], 'correct_answer' => '', 'accepted_answers' => ['awareness']]);
        $lesson = $section->lessons()->create(['title' => 'Read and answer', 'type' => 'text', 'quiz_id' => $quiz->id]);
        CourseService::enrollFree($student, $course);

        $this->actingAs($student)->get(route('student.get-lesson', [$course, $lesson]))
            ->assertOk()->assertJsonPath('quiz.title', 'Reusable quiz')->assertJsonPath('quiz.questions.0.type', 'user_input');
        $this->postJson(route('student.submit-quiz', [$course, $lesson]), ['answers' => [$question->id => 'Awareness']])
            ->assertOk()->assertJsonPath('passed', true);
    }
}
