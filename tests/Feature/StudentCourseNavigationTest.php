<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentCourseNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_reports_all_certificates_without_repeating_stats(): void
    {
        $student = User::factory()->create();
        foreach (range(1, 6) as $number) {
            $course = Course::create(['title' => "Course {$number}", 'slug' => "course-{$number}", 'category' => 'Technology']);
            Certificate::create(['user_id' => $student->id, 'course_id' => $course->id, 'certificate_number' => "CERT-{$number}", 'issued_at' => now()]);
        }

        $this->actingAs($student)->get(route('student.dashboard'))
            ->assertOk()
            ->assertViewHas('certCount', 6)
            ->assertViewHas('certificates', fn ($certificates) => $certificates->count() === 5)
            ->assertSeeText('Active days (30d)')
            ->assertDontSeeText('Day Streak');
    }

    public function test_progress_page_uses_the_learners_real_enrollments(): void
    {
        $student = User::factory()->create();
        $course = Course::create(['title' => 'Progress course', 'category' => 'Technology']);
        $section = Section::create(['course_id' => $course->id, 'title' => 'Start']);
        $lesson = $section->lessons()->create(['title' => 'First step', 'type' => 'text']);
        Enrollment::create(['user_id' => $student->id, 'course_id' => $course->id,
            'status' => 'active', 'access_type' => 'lifetime', 'progress' => 50]);
        \DB::table('lesson_progress')->insert(['user_id' => $student->id, 'lesson_id' => $lesson->id,
            'progress' => 100, 'completed' => true, 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($student)->get(route('student.progress'))
            ->assertOk()
            ->assertSeeText('Progress course')
            ->assertSeeText('1/1 lessons complete')
            ->assertSeeText('50%')
            ->assertSee('role="progressbar"', false)
            ->assertSee(route('student.learn', $course))
            ->assertDontSeeText('coming soon');
    }

    public function test_live_classes_do_not_offer_empty_join_links(): void
    {
        $student = User::factory()->create();
        $course = Course::create(['title' => 'Scheduled course', 'slug' => 'scheduled-course']);
        $section = Section::create(['course_id' => $course->id, 'title' => 'Live sessions']);
        $section->lessons()->create([
            'title' => 'Waiting for host link',
            'type' => 'live',
            'live_scheduled_at' => now()->addHour(),
            'live_duration_min' => 60,
        ]);
        $section->lessons()->create([
            'title' => 'Ready session',
            'type' => 'live',
            'live_scheduled_at' => now()->addHours(2),
            'live_duration_min' => 60,
            'live_url' => 'https://meet.example.test/ready',
        ]);
        Enrollment::create(['user_id' => $student->id, 'course_id' => $course->id, 'status' => 'active']);

        $this->actingAs($student)->get(route('student.live'))
            ->assertOk()
            ->assertSeeText('Meeting link not added yet')
            ->assertSeeText('Link not added yet')
            ->assertDontSee('href=""', false)
            ->assertSee('data-copy-link="https://meet.example.test/ready"', false);
    }

    public function test_profile_form_controls_have_accessible_names(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->withSession(['success' => 'Saved'])->get(route('student.profile'))
            ->assertOk()
            ->assertSee('for="profile-name"', false)
            ->assertSee('for="profile-phone"', false)
            ->assertSee('for="cpwd"', false)
            ->assertSee('autocomplete="username"', false)
            ->assertSee('autocomplete="current-password"', false)
            ->assertSee('autocomplete="new-password"', false)
            ->assertSee('aria-label="Show current password"', false)
            ->assertSee('aria-label="Dismiss message"', false);

        $this->get(route('student.settings'))->assertRedirect(route('student.profile'));
        $this->get(route('student.saved'))->assertRedirect('/dashboard/browse');
        $this->get(route('student.profile'))->assertOk()->assertSee(route('studio.dashboard'));
    }

    public function test_browse_filters_are_named_and_price_sort_is_applied(): void
    {
        $student = User::factory()->create();
        foreach ([['Expensive course', 500], ['Affordable course', 100]] as [$title, $price]) {
            Course::create(['title' => $title, 'price' => $price, 'is_free' => false,
                'visibility' => 'public', 'is_published' => true,
                'marketplace_status' => 'live', 'marketplace_visible' => true]);
        }

        $this->actingAs($student)->get(route('student.browse', ['sort' => 'price_lo']))
            ->assertOk()
            ->assertSeeInOrder(['Affordable course', 'Expensive course'])
            ->assertSee('for="course-category"', false)
            ->assertSee('for="course-level"', false)
            ->assertSee('for="course-sort"', false);
    }

    public function test_editing_course_details_keeps_a_published_course_public(): void
    {
        config(['app.key' => str_repeat('a', 32)]);
        Role::findOrCreate('admin', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('admin');
        $course = Course::create([
            'title' => 'Web Development', 'slug' => 'web-development',
            'category' => 'Web Development', 'level' => 'beginner',
            'visibility' => 'public', 'is_published' => true,
            'primary_instructor_id' => $teacher->id, 'marketplace_status' => 'live', 'marketplace_visible' => true,
        ]);

        $this->actingAs($teacher)->put(route('admin.courses.update', $course), [
            'title' => 'Web Development', 'category' => 'Web Development',
            'level' => 'beginner', 'language' => 'en', 'price' => 0,
            'thumbnail' => 'https://example.com/cover.png',
        ])->assertRedirect();

        $course->refresh();
        $this->assertSame('public', $course->visibility);
        $this->assertTrue($course->is_published);
        $this->assertSame('https://example.com/cover.png', $course->thumbnail);
    }

    public function test_student_can_browse_open_and_start_a_free_course(): void
    {
        config(['app.key' => str_repeat('a', 32)]);
        $student = User::factory()->create();
        $course = Course::create([
            'title' => 'Web Development',
            'slug' => 'web-development',
            'category' => 'Web Development',
            'visibility' => 'public',
            'is_published' => true,
            'is_free' => true,
            'marketplace_status' => 'live', 'marketplace_visible' => true,
        ]);
        $section = Section::create(['course_id' => $course->id, 'title' => 'Getting Started']);
        $lesson = $section->lessons()->create([
            'title' => 'Build Your First Page',
            'type' => 'text',
            'content' => '<p>Hello, learner.</p>',
        ]);

        $this->actingAs($student)
            ->get(route('student.browse'))
            ->assertOk()
            ->assertSee('Find your next skill — 1 course available')
            ->assertSee(route('student.course-detail', $course->slug));

        $this->get(route('student.course-detail', $course->slug))
            ->assertOk()
            ->assertSeeText($course->title)
            ->assertSeeText('No payment required')
            ->assertDontSeeText('Secure payment via Razorpay')
            ->assertDontSeeText('money-back guarantee');

        $this->post(route('student.enroll-free', $course))
            ->assertRedirect(route('student.learn', $course));

        $this->get(route('student.learn', $course))
            ->assertOk()
            ->assertSeeText($course->title);

        $this->get(route('student.get-lesson', [$course, $lesson]))
            ->assertOk()
            ->assertJsonPath('content', '<p>Hello, learner.</p>');

        $this->post(route('student.save-progress', [$course, $lesson]), ['progress' => 100])
            ->assertOk()
            ->assertJsonPath('course_completed', true);

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
            'progress' => 100,
            'completed' => 1,
        ]);
        $this->assertDatabaseHas('certificates', [
            'user_id' => $student->id,
            'course_id' => $course->id,
            'vendor_id' => null,
        ]);
    }

    public function test_quiz_requires_a_passing_attempt_and_does_not_expose_answers(): void
    {
        config(['app.key' => str_repeat('a', 32)]);
        $student = User::factory()->create();
        $course = Course::create([
            'title' => 'Computer Programming', 'slug' => 'computer-programming',
            'category' => 'Computer Science & Coding', 'visibility' => 'public',
            'is_published' => true, 'is_free' => true,
            'marketplace_status' => 'live', 'marketplace_visible' => true,
        ]);
        $section = Section::create(['course_id' => $course->id, 'title' => 'Check your learning']);
        $lesson = $section->lessons()->create(['title' => 'Python checkpoint', 'type' => 'quiz']);
        $quiz = $lesson->quiz()->create(['title' => 'Python checkpoint', 'pass_score' => 70]);
        $questions = collect([
            ['question' => 'What repeats work?', 'options' => ['A loop', 'A string', 'A comment', 'A file'], 'correct_answer' => '0'],
            ['question' => 'What stores a value?', 'options' => ['A loop', 'A variable', 'A comment', 'A file'], 'correct_answer' => '1'],
            ['question' => 'What groups reusable code?', 'options' => ['A file', 'A list', 'A function', 'A string'], 'correct_answer' => '2'],
        ])->map(fn($data, $order) => $quiz->questions()->create($data + ['order' => $order]));

        $this->actingAs($student)->post(route('student.enroll-free', $course))->assertRedirect();
        $this->get(route('student.get-lesson', [$course, $lesson]))
            ->assertOk()->assertJsonPath('quiz.questions.0.question', 'What repeats work?')
            ->assertJsonMissingPath('quiz.questions.0.correct_answer');
        $this->post(route('student.save-progress', [$course, $lesson]), ['progress' => 100])->assertStatus(422);

        $wrong = $questions->mapWithKeys(fn($q) => [$q->id => 3])->all();
        $this->postJson(route('student.submit-quiz', [$course, $lesson]), ['answers' => $wrong])
            ->assertOk()->assertJsonPath('passed', false)->assertJsonPath('course_progress', 0);
        $this->assertDatabaseMissing('lesson_progress', ['user_id' => $student->id, 'lesson_id' => $lesson->id]);

        $correct = $questions->mapWithKeys(fn($q) => [$q->id => (int)$q->correct_answer])->all();
        $this->postJson(route('student.submit-quiz', [$course, $lesson]), ['answers' => $correct])
            ->assertOk()->assertJsonPath('passed', true)->assertJsonPath('course_progress', 100);
        $this->assertDatabaseHas('lesson_progress', ['user_id' => $student->id, 'lesson_id' => $lesson->id, 'completed' => 1]);
        $this->assertDatabaseCount('quiz_attempts', 2);
    }

    public function test_teacher_can_build_a_quiz_in_the_lesson_editor(): void
    {
        config(['app.key' => str_repeat('a', 32)]);
        Role::findOrCreate('admin', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('admin');
        $course = Course::create(['title' => 'Robotics & IoT', 'slug' => 'robotics-iot', 'category' => 'Robotics & IoT', 'primary_instructor_id' => $teacher->id]);
        $section = Section::create(['course_id' => $course->id, 'title' => 'Sensors']);
        $this->actingAs($teacher)->get(route('admin.courses.lessons.create', [$course, $section]))
            ->assertOk()->assertSee('Graded quiz');

        $questions = collect([1, 2, 3])->map(fn($i) => [
            'question' => "Question {$i}?",
            'options' => ['First', 'Second', 'Third', 'Fourth'],
            'correct_answer' => 1,
        ])->all();
        $this->post(route('admin.courses.lessons.store', [$course, $section]), [
            'title' => 'Sensor checkpoint', 'type' => 'quiz', 'duration' => 5,
            'quiz_title' => 'Sensor checkpoint', 'quiz_pass_score' => 70,
            'quiz_questions' => $questions,
        ])->assertRedirect(route('admin.courses.edit', $course));

        $lesson = $section->lessons()->firstOrFail();
        $this->assertSame(300, $lesson->duration);
        $this->assertSame(3, $lesson->quiz->questions()->count());
        $this->get(route('admin.courses.lessons.edit', [$course, $section, $lesson]))
            ->assertOk()->assertSee('Question 1?');
    }
}
