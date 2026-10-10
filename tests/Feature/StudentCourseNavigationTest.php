<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentCourseNavigationTest extends TestCase
{
    use RefreshDatabase;

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
            ->assertSeeText($course->title);

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
        Role::findOrCreate('teacher', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $course = Course::create(['title' => 'Robotics & IoT', 'slug' => 'robotics-iot', 'category' => 'Robotics & IoT']);
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
        $this->assertSame(3, $lesson->quiz->questions()->count());
        $this->get(route('admin.courses.lessons.edit', [$course, $section, $lesson]))
            ->assertOk()->assertSee('Question 1?');
    }
}
