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

    public function test_teacher_can_attach_slides_and_student_can_view_and_download_them(): void
    {
        config(['app.key' => str_repeat('a', 32)]);
        Storage::fake('public');
        Role::findOrCreate('teacher', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $student = User::factory()->create();
        $course = Course::create(['title' => 'Programming', 'slug' => 'programming', 'category' => 'Coding', 'visibility' => 'public', 'is_published' => true, 'is_free' => true]);
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
        CourseService::enrollFree($student, $course);
        $this->actingAs($student)->get(route('student.learn', $course))->assertOk()
            ->assertSee('Intro slides')->assertSee('Narration')->assertSee('Diagram')->assertSee('Practice quiz')
            ->assertSee('<audio controls', false)->assertSee(route('media.download', $deck->uuid));
        $this->get(route('student.get-lesson', [$course, $lesson]))->assertOk()->assertJsonPath('materials.0.preview_url', $preview->public_url);
        $this->get(route('media.download', $deck->uuid))->assertOk()->assertHeader('content-disposition', 'attachment; filename=slides.pptx');
    }

    public function test_slide_preview_is_required_and_private_file_cannot_be_attached(): void
    {
        config(['app.key' => str_repeat('a', 32)]);
        Role::findOrCreate('teacher', 'web');
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $course = Course::create(['title' => 'Programming', 'slug' => 'programming', 'category' => 'Coding']);
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
