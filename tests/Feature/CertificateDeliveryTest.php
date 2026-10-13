<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_learner_can_view_their_designed_certificate_but_not_another_learners(): void
    {
        $owner = User::factory()->create(['name' => 'Jane Learner']);
        $other = User::factory()->create();
        $course = Course::create(['title' => 'Modern Web Development', 'category' => 'Web Development']);
        $certificate = Certificate::create([
            'user_id' => $owner->id,
            'course_id' => $course->id,
            'certificate_number' => 'ITF-TEST-2026',
            'issued_at' => now(),
        ]);
        Setting::set('certificate_title', 'Achievement Certificate', 'certificates');
        Setting::set('certificate_completion_text', 'has completed {{course_title}}', 'certificates');

        $this->actingAs($owner)->get(route('student.certs.show', $certificate))
            ->assertOk()
            ->assertSeeText('Achievement Certificate')
            ->assertSeeText('Jane Learner')
            ->assertSeeText('has completed Modern Web Development')
            ->assertSeeText('ITF-TEST-2026');

        $this->actingAs($other)->get(route('student.certs.show', $certificate))->assertForbidden();
    }
}
