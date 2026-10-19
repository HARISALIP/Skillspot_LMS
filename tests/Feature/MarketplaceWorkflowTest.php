<?php

namespace Tests\Feature;

use App\Models\CommissionOverride;
use App\Models\Course;
use App\Models\InstructorProfile;
use App\Models\MarketplaceSale;
use App\Models\PaymentOrder;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vendor;
use App\Services\MarketplaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MarketplaceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_commission_precedence_and_sale_snapshot_are_immutable(): void
    {
        $instructor = User::factory()->create();
        $buyer = User::factory()->create();
        $course = Course::create(['title' => 'Marketplace Course', 'category' => 'Business', 'primary_instructor_id' => $instructor->id, 'marketplace_status' => 'live', 'marketplace_visible' => true]);
        Setting::set('marketplace_default_instructor_percent', '70', 'marketplace');
        CommissionOverride::create(['instructor_id' => $instructor->id, 'instructor_percent' => 80]);
        CommissionOverride::create(['course_id' => $course->id, 'instructor_percent' => 95]);
        $order = PaymentOrder::create(['user_id' => $buyer->id, 'course_id' => $course->id, 'razorpay_order_id' => 'order_marketplace_1', 'amount' => 1000, 'status' => 'paid', 'paid_at' => now()]);

        $sale = MarketplaceService::recordPaidSale($order);
        $this->assertSame('95.00', $sale->instructor_percent);
        $this->assertSame('5.00', $sale->platform_percent);
        $this->assertSame('950.00', $sale->seller_share);
        $this->assertSame('50.00', $sale->platform_share);
        $this->assertSame($sale->id, MarketplaceService::recordPaidSale($order)->id);
        $this->assertDatabaseCount('marketplace_sales', 1);
        $this->assertDatabaseCount('earning_ledger_entries', 2);
    }

    public function test_pending_earning_releases_after_hold_and_refund_reverses_it_before_payout(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $course = Course::create(['title' => 'Refundable Course', 'category' => 'Business', 'primary_instructor_id' => $seller->id, 'marketplace_status' => 'live', 'marketplace_visible' => true]);
        $order = PaymentOrder::create(['user_id' => $buyer->id, 'course_id' => $course->id, 'razorpay_order_id' => 'order_marketplace_2', 'amount' => 500, 'status' => 'paid', 'paid_at' => now()]);
        $sale = MarketplaceService::recordPaidSale($order);
        $sale->update(['hold_until' => now()->subSecond()]);
        MarketplaceService::releaseAvailableEarnings();
        $this->assertSame('available', $sale->fresh()->status);
        MarketplaceService::reverseSale($order);
        $this->assertSame('reversed', $sale->fresh()->status);
        $this->assertDatabaseHas('earning_ledger_entries', ['marketplace_sale_id' => $sale->id, 'entry_type' => 'refund_reversal']);
    }

    public function test_admin_approval_keeps_one_learner_account_with_creator_capability(): void
    {
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('student', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $applicant = User::factory()->create();
        $applicant->assignRole('student');
        $profile = InstructorProfile::create(['user_id' => $applicant->id, 'status' => 'pending', 'legal_name' => 'Applicant', 'bio' => str_repeat('bio ', 20), 'payout_account_name' => 'Applicant', 'payout_account_number' => '12345678', 'payout_ifsc' => 'TEST0001']);

        $this->actingAs($admin)->patch(route('admin.marketplace.instructors.review', $profile), ['status' => 'approved'])
            ->assertRedirect();

        $this->assertSame('approved', $profile->fresh()->status);
        $this->assertFalse($applicant->fresh()->hasRole('teacher'));
        $this->assertTrue($applicant->fresh()->hasRole('student'));
        $this->actingAs($applicant)->get(route('studio.dashboard'))->assertOk();
        $this->actingAs($applicant)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_admin_review_cannot_publish_an_unsubmitted_creator_draft(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $creator = User::factory()->create();
        $course = Course::create(['title' => 'Unsubmitted draft', 'primary_instructor_id' => $creator->id]);

        $this->actingAs($admin)->patch(route('admin.marketplace.reviews.update', $course), ['status' => 'live'])
            ->assertUnprocessable();
        $this->assertSame('draft', $course->fresh()->marketplace_status);
    }

    public function test_admin_can_publish_a_submitted_organization_course_before_payout_verification(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $owner = User::factory()->create();
        $creator = User::factory()->create();
        $vendor = Vendor::create(['user_id' => $owner->id, 'brand_name' => 'Pending Payout College']);
        $course = Course::create([
            'title' => 'Submitted Organization Course',
            'vendor_id' => $vendor->id,
            'primary_instructor_id' => $creator->id,
            'marketplace_status' => 'submitted',
        ]);

        $this->actingAs($admin)->patch(route('admin.marketplace.reviews.update', $course), [
            'status' => 'live',
            'feedback' => 'Content review passed.',
        ])->assertRedirect();

        $this->assertNull($vendor->fresh()->marketplace_verified_at);
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'marketplace_status' => 'live',
            'marketplace_visible' => true,
            'is_published' => true,
            'visibility' => 'public',
        ]);
    }

    public function test_organization_payout_requires_verified_bank_details(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $owner = User::factory()->create();
        $buyer = User::factory()->create();
        $vendor = Vendor::create(['user_id' => $owner->id, 'brand_name' => 'Payout College']);
        $course = Course::create(['title' => 'Paid course', 'vendor_id' => $vendor->id]);
        $order = PaymentOrder::create(['user_id' => $buyer->id, 'course_id' => $course->id,
            'razorpay_order_id' => 'order_org_payout', 'amount' => 100, 'status' => 'paid']);
        $sale = MarketplaceService::recordPaidSale($order);
        $sale->update(['status' => 'available']);

        $this->actingAs($admin)->post(route('admin.marketplace.payouts.create'), ['sale_ids' => [$sale->id]])
            ->assertUnprocessable();
        $this->assertDatabaseCount('payout_batches', 0);

        $vendor->update(['status' => 'active', 'payout_account_name' => 'Payout College',
            'payout_account_number' => '12345678', 'payout_ifsc' => 'TEST0001', 'marketplace_verified_at' => now()]);
        $this->actingAs($admin)->post(route('admin.marketplace.payouts.create'), ['sale_ids' => [$sale->id]])
            ->assertRedirect();
        $this->assertDatabaseCount('payout_batches', 1);
    }

    public function test_public_marketplace_exposes_only_live_courses(): void
    {
        Course::create(['title' => 'Visible Marketplace Course', 'category' => 'Business', 'marketplace_status' => 'live', 'marketplace_visible' => true, 'is_published' => true, 'visibility' => 'public']);
        Course::create(['title' => 'Unpublished Approved Course', 'category' => 'Business', 'marketplace_status' => 'live', 'marketplace_visible' => false, 'is_published' => false, 'visibility' => 'draft']);
        Course::create(['title' => 'Private Draft Course', 'category' => 'Business', 'marketplace_status' => 'draft', 'marketplace_visible' => false]);

        $this->get(route('marketplace.browse'))
            ->assertOk()
            ->assertSeeText('All Courses')
            ->assertSee('class="course-grid"', false)
            ->assertSee('Visible Marketplace Course')
            ->assertDontSee('Unpublished Approved Course')
            ->assertDontSee('Private Draft Course');
    }

    public function test_organization_sales_use_the_course_or_platform_split_not_an_individual_override(): void
    {
        $owner = User::factory()->create();
        $instructor = User::factory()->create();
        $buyer = User::factory()->create();
        $vendor = Vendor::create(['user_id' => $owner->id, 'brand_name' => 'Example College']);
        $course = Course::create(['title' => 'Organization Course', 'vendor_id' => $vendor->id, 'primary_instructor_id' => $instructor->id, 'marketplace_status' => 'live', 'marketplace_visible' => true]);
        Setting::set('marketplace_default_instructor_percent', '70', 'marketplace');
        CommissionOverride::create(['instructor_id' => $instructor->id, 'instructor_percent' => 90]);
        $order = PaymentOrder::create(['user_id' => $buyer->id, 'course_id' => $course->id, 'razorpay_order_id' => 'order_marketplace_organization', 'amount' => 1000, 'status' => 'paid', 'paid_at' => now()]);

        $sale = MarketplaceService::recordPaidSale($order);

        $this->assertSame($vendor->id, $sale->organization_id);
        $this->assertSame('70.00', $sale->instructor_percent);
        $this->assertNull($sale->seller_user_id);
    }

    public function test_non_admin_cannot_manage_organizations(): void
    {
        Role::findOrCreate('student', 'web');
        $user = User::factory()->create();
        $user->assignRole('student');

        $this->actingAs($user)->get(route('admin.vendors'))->assertForbidden();
    }
}
