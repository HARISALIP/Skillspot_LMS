<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\PaymentOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaidCheckoutGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_both_legacy_order_routes_default_to_disabled(): void
    {
        $buyer = User::factory()->create();
        $course = Course::create([
            'title' => 'Paid course',
            'category' => 'Business',
            'price' => 100,
            'is_free' => false,
            'marketplace_status' => 'live',
            'marketplace_visible' => true,
        ]);

        $this->actingAs($buyer)->post(route('student.create-order', $course))->assertStatus(503);
        $this->post(route('payment.order'), ['course_id' => $course->id])->assertStatus(503);
        $this->assertSame(0, PaymentOrder::count());
    }
}
