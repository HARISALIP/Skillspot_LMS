<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Section;
use App\Models\Upload;
use App\Models\User;
use App\Services\CourseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CourseMaterialsTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_creator_can_attach_materials_and_student_can_preview_them(): void
    {
        config(['app.key' => str_repeat('a', 32)]);
        Storage::fake('public');
        Role::findOrCreate('admin', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('admin');
        $student = User::factory()->create();
        $paper = $this->upload('question-paper.pdf', 'application/pdf');
        $image = $this->upload('diagram.png', 'image/png');
        $input = [
            'title' => 'Robotics', 'category' => 'Technology', 'level' => 'beginner',
            'language' => 'en', 'price' => 0, 'visibility' => 'public',
            'resources' => [
                ['title' => 'Sample paper', 'kind' => 'question_paper', 'upload_id' => $paper->id],
                ['title' => 'Circuit diagram', 'kind' => 'image', 'upload_id' => $image->id],
            ],
        ];

        $this->actingAs($teacher)->post(route('admin.courses.store'), $input)->assertRedirect();
        $course = Course::where('title', 'Robotics')->firstOrFail();
        $this->assertCount(2, $course->resources);
        $this->assertSame($course->id, (int) data_get($paper->fresh()->used_in, 'course_id'));
        $this->assertSame($course->id, (int) data_get($image->fresh()->used_in, 'course_id'));
        $section = Section::create(['course_id' => $course->id, 'title' => 'Start']);
        $section->lessons()->create(['title' => 'First lesson', 'type' => 'text', 'resources' => [
            ['title' => 'Lesson-only diagram', 'kind' => 'image', 'upload_id' => $image->id],
            ['title' => 'Sample paper', 'kind' => 'question_paper', 'upload_id' => $paper->id],
        ]]);
        $this->get(route('admin.courses.edit', $course))->assertOk()
            ->assertSee('Sample paper')->assertDontSee('Lesson-only diagram');
        CourseService::enrollFree($student, $course);
        $page = $this->actingAs($student)->get(route('student.learn', $course))->assertOk()
            ->assertSee('Sample paper')->assertSee('Circuit diagram')
            ->assertSee('materialViewer')->assertSee(route('media.course', [$course, $paper->uuid]))
            ->assertSee('id="pdfOpen" type="button"', false)
            ->assertSee('id="slideNext"', false)
            ->assertSee('data-download-url="'.route('media.course', [$course, $image->uuid]).'"', false);
        $this->assertSame(1, substr_count($page->getContent(), '<h3 class="font-bold text-gray-900 mt-1">Sample paper</h3>'));

        $paper->update(['is_public' => false]);
        $this->actingAs($teacher)->put(route('admin.courses.update', $course), $input)
            ->assertSessionHasErrors('resources.0.upload_id');
    }

    public function test_teacher_can_attach_slides_and_student_can_view_and_download_them(): void
    {
        config(['app.key' => str_repeat('a', 32)]);
        Storage::fake('public');
        Role::findOrCreate('admin', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('admin');
        $student = User::factory()->create();
        $course = Course::create(['title' => 'Programming', 'slug' => 'programming', 'category' => 'Coding', 'visibility' => 'public', 'is_published' => true, 'is_free' => true, 'primary_instructor_id' => $teacher->id, 'marketplace_status' => 'live', 'marketplace_visible' => true]);
        $section = Section::create(['course_id' => $course->id, 'title' => 'Start']);
        $deck = $this->upload('slides.pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation');
        $preview = $this->upload('slides-preview.pdf', 'application/pdf');
        $audio = $this->upload('narration.mp3', 'audio/mpeg');
        $image = $this->upload('diagram.png', 'image/png');
        $quiz = $this->upload('practice-quiz.pdf', 'application/pdf');

        $this->actingAs($teacher)->post(route('admin.courses.lessons.store', [$course, $section]), [
            'title' => 'First lesson', 'type' => 'text', 'content' => '<p>Welcome</p>',
            'resources' => [
                ['title' => 'Intro slides', 'kind' => 'slides', 'upload_id' => $deck->id, 'preview_upload_id' => $preview->id],
                ['title' => 'Narration', 'kind' => 'audio', 'upload_id' => $audio->id],
                ['title' => 'Diagram', 'kind' => 'image', 'upload_id' => $image->id],
                ['title' => 'Practice quiz', 'kind' => 'quiz_paper', 'upload_id' => $quiz->id],
            ],
        ])->assertRedirect();

        $lesson = $section->lessons()->firstOrFail();
        $this->assertSame($deck->id, $lesson->resources[0]['upload_id']);
        $this->assertSame($course->id, (int) data_get($deck->fresh()->used_in, 'course_id'));
        $this->assertSame($course->id, (int) data_get($preview->fresh()->used_in, 'course_id'));
        $this->get(route('media.course', [$course, $preview->uuid]))->assertOk();
        CourseService::enrollFree($student, $course);
        $this->actingAs($student)->get(route('student.learn', $course))->assertOk()
            ->assertSee('Intro slides')->assertSee('Narration')->assertSee('Diagram')->assertSee('Practice quiz')
            ->assertSee('<audio controls', false)->assertSee(route('media.course', [$course, $deck->uuid]));
        $this->get(route('student.get-lesson', [$course, $lesson]))->assertOk()->assertJsonPath('materials.0.preview_url', route('media.course', [$course, $preview->uuid]));
        $this->get(route('media.download', $deck->uuid))->assertNotFound();
    }

    public function test_slide_preview_is_required_and_private_file_cannot_be_attached(): void
    {
        config(['app.key' => str_repeat('a', 32)]);
        Role::findOrCreate('admin', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('admin');
        $course = Course::create(['title' => 'Programming', 'slug' => 'programming', 'category' => 'Coding', 'primary_instructor_id' => $teacher->id]);
        $section = Section::create(['course_id' => $course->id, 'title' => 'Start']);
        $deck = $this->upload('slides.pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation');
        $input = ['title' => 'First lesson', 'type' => 'text', 'resources' => [['title' => 'Slides', 'kind' => 'slides', 'upload_id' => $deck->id]]];

        $this->actingAs($teacher)->post(route('admin.courses.lessons.store', [$course, $section]), $input)->assertSessionHasErrors('resources.0.upload_id');
        $deck->update(['is_public' => false]);
        $this->get(route('media.download', $deck->uuid))->assertNotFound();
    }

    private function upload(string $name, string $mime): Upload
    {
        Storage::disk('public')->put('uploads/course-materials/'.$name, 'demo');
        return Upload::create([
            'original_name' => $name, 'file_name' => $name, 'mime_type' => $mime,
            'path' => 'uploads/course-materials/'.$name, 'disk' => 'public',
            'type' => Upload::typeFromMime($mime), 'is_public' => true,
        ]);
    }
}
