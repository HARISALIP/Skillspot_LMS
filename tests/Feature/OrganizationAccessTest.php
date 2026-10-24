<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\OrganizationClass;
use App\Models\PaymentOrder;
use App\Models\Setting;
use App\Models\User;
use App\Models\Upload;
use App\Models\Vendor;
use App\Services\MarketplaceService;
use App\Services\OrganizationAccess;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Password;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class OrganizationAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_classes_are_isolated_but_marketplace_access_is_independent(): void
    {
        $owner = User::factory()->create();
        $vendor = Vendor::create(['user_id' => $owner->id, 'brand_name' => 'School A', 'status' => 'active']);
        $otherVendor = Vendor::create(['user_id' => User::factory()->create()->id, 'brand_name' => 'School B', 'status' => 'active']);
        $class10 = OrganizationClass::create(['vendor_id' => $vendor->id, 'name' => 'Class 10']);
        $class11 = OrganizationClass::create(['vendor_id' => $vendor->id, 'name' => 'Class 11']);
        $student10 = User::factory()->create();
        $student11 = User::factory()->create();
        $class11Only = User::factory()->create();
        $outsider = User::factory()->create();
        foreach ([$student10, $student11, $class11Only] as $student) {
            DB::table('student_vendor_access')->insert(['vendor_id' => $vendor->id, 'user_id' => $student->id, 'created_at' => now(), 'updated_at' => now()]);
        }
        $class10->students()->attach($student10);
        $class11->students()->attach($student10);
        $class11->students()->attach($student11);
        $class11->students()->attach($class11Only);
        $course = Course::create(['title' => 'Maths', 'vendor_id' => $vendor->id, 'organization_published' => true,
            'organization_audience' => 'classes', 'is_free' => true, 'registration_open' => true,
            'marketplace_status' => 'live', 'marketplace_visible' => true]);
        $course->organizationClasses()->attach($class10);

        $this->assertTrue(OrganizationAccess::eligible($student10, $course));
        $this->assertFalse(OrganizationAccess::eligible($student11, $course));
        $this->assertFalse(OrganizationAccess::eligible($outsider, $course));
        $this->actingAs($student11)->get(route('vendor.portal.courses.show', [$vendor->slug, $course]))->assertNotFound();
        $this->actingAs($student10)->post(route('vendor.portal.courses.enroll-free', [$vendor->slug, $course]))->assertRedirect();
        $this->assertTrue(OrganizationAccess::hasChannel($student10, $course, 'organization'));
        $this->assertFalse(OrganizationAccess::hasChannel($student10, $course, 'marketplace'));

        Enrollment::firstOrCreate(['user_id' => $student11->id, 'course_id' => $course->id], ['status' => 'active']);
        OrganizationAccess::grant($student11, $course, 'marketplace', 'free');
        $this->assertTrue(OrganizationAccess::hasChannel($student11, $course, 'marketplace'));
        $this->assertFalse(OrganizationAccess::hasChannel($student11, $course, 'organization'));
        Storage::fake('local');
        Storage::disk('local')->put('course-materials/private.txt', 'Class 10 material');
        $upload = Upload::create(['original_name' => 'private.txt', 'file_name' => 'private.txt', 'mime_type' => 'text/plain',
            'extension' => 'txt', 'size' => 17, 'disk' => 'local', 'path' => 'course-materials/private.txt',
            'type' => 'document', 'folder' => 'course-materials', 'is_public' => false, 'used_in' => ['course_id' => $course->id]]);
        $this->actingAs($student10)->get(route('media.course', [$course, $upload->uuid]))->assertOk();
        $this->actingAs($class11Only)->get(route('media.course', [$course, $upload->uuid]))->assertForbidden();
        $this->actingAs($student11)->get(route('media.course', [$course, $upload->uuid]))->assertOk();
        $this->actingAs($outsider)->get(route('media.course', [$course, $upload->uuid]))->assertForbidden();
        $course->allowedUsers()->attach($student11, ['added_by' => $owner->id]);
        $this->assertTrue(OrganizationAccess::eligible($student11, $course));
        OrganizationAccess::grant($student11, $course, 'organization', 'admin', null, $owner->id);
        OrganizationAccess::revokeManual($student11, $course);
        $this->assertTrue(OrganizationAccess::hasChannel($student11, $course, 'marketplace'));
        $this->assertFalse(OrganizationAccess::hasChannel($student11, $course, 'organization'));

        $otherCourse = Course::create(['title' => 'Other school', 'vendor_id' => $otherVendor->id, 'organization_published' => true]);
        $this->assertFalse(OrganizationAccess::eligible($student10, $otherCourse));
    }

    public function test_class_with_targeted_courses_returns_to_management_without_removing_assignments(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('vendor');
        $vendor = Vendor::create(['user_id' => $owner->id, 'brand_name' => 'School', 'status' => 'active']);
        $class = OrganizationClass::create(['vendor_id' => $vendor->id, 'name' => 'Class 10']);
        $course = Course::create(['title' => 'Class maths', 'vendor_id' => $vendor->id, 'organization_audience' => 'classes']);
        $course->organizationClasses()->attach($class);

        $this->actingAs($owner)->withSession(['active_vendor_id' => $vendor->id])
            ->from(route('vendor.organization.index'))
            ->delete(route('vendor.organization.classes.delete', $class))
            ->assertRedirect(route('vendor.organization.index'))
            ->assertSessionHas('error', 'Move courses to another class before removing this class. No assignments were changed.');

        $this->assertDatabaseHas('organization_classes', ['id' => $class->id]);
        $this->assertDatabaseHas('organization_class_courses', ['organization_class_id' => $class->id, 'course_id' => $course->id]);
    }

    public function test_organization_forms_report_validation_at_the_correct_fields(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('vendor');
        $vendor = Vendor::create(['user_id' => $owner->id, 'brand_name' => 'School', 'status' => 'active']);
        $this->actingAs($owner)->withSession(['active_vendor_id' => $vendor->id]);

        $this->post(route('vendor.organization.classes.store'), ['name' => ''])
            ->assertSessionHasErrorsIn('class', ['name']);
        $this->post(route('vendor.organization.staff.store'), ['name' => '', 'email' => 'invalid', 'role' => 'teacher'])
            ->assertSessionHasErrorsIn('staff', ['name', 'email']);
    }

    public function test_paid_organization_sale_uses_its_fee_and_refund_preserves_marketplace_grant(): void
    {
        $owner = User::factory()->create();
        $student = User::factory()->create();
        $otherClassStudent = User::factory()->create();
        $vendor = Vendor::create(['user_id' => $owner->id, 'brand_name' => 'Paid school', 'status' => 'active', 'organization_fee_percent' => 5]);
        $course = Course::create(['title' => 'Paid Maths', 'vendor_id' => $vendor->id, 'price' => 100, 'is_free' => false,
            'organization_published' => true, 'organization_audience' => 'classes', 'marketplace_status' => 'live', 'marketplace_visible' => true]);
        $class10 = OrganizationClass::create(['vendor_id' => $vendor->id, 'name' => 'Class 10']);
        $class11 = OrganizationClass::create(['vendor_id' => $vendor->id, 'name' => 'Class 11']);
        $course->organizationClasses()->attach($class10);
        $class10->students()->attach($student);
        $class11->students()->attach($otherClassStudent);
        DB::table('student_vendor_access')->insert(['vendor_id' => $vendor->id, 'user_id' => $student->id, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('student_vendor_access')->insert(['vendor_id' => $vendor->id, 'user_id' => $otherClassStudent->id, 'created_at' => now(), 'updated_at' => now()]);
        $order = PaymentOrder::create(['user_id' => $student->id, 'course_id' => $course->id, 'razorpay_order_id' => 'order_org',
            'amount' => 100, 'currency' => 'INR', 'mode' => 'test', 'status' => 'created', 'sale_channel' => 'organization']);
        $class10->students()->detach($student);
        $this->assertFalse(OrganizationAccess::eligible($student, $course));
        $payments = app(PaymentService::class);
        $payments->confirmCaptured($order, ['id' => 'pay_org', 'order_id' => 'order_org', 'status' => 'captured', 'amount' => 10000, 'currency' => 'INR']);
        $sale = MarketplaceService::recordPaidSale($order);
        $this->assertSame('organization', $sale->sale_channel);
        $this->assertEquals(95, $sale->seller_share);
        $this->assertEquals(5, $sale->platform_share);
        $this->assertFalse(OrganizationAccess::hasChannel($student, $course, 'organization'));
        $marketOrder = PaymentOrder::create(['user_id' => $otherClassStudent->id, 'course_id' => $course->id,
            'razorpay_order_id' => 'order_market', 'amount' => 100, 'currency' => 'INR', 'mode' => 'test', 'status' => 'created', 'sale_channel' => 'marketplace']);
        $payments->confirmCaptured($marketOrder, ['id' => 'pay_market', 'order_id' => 'order_market', 'status' => 'captured', 'amount' => 10000, 'currency' => 'INR']);
        $this->assertTrue(OrganizationAccess::hasChannel($otherClassStudent, $course, 'marketplace'));
        $this->assertFalse(OrganizationAccess::hasChannel($otherClassStudent, $course, 'organization'));
        $this->assertSame('marketplace', $marketOrder->marketplaceSale->sale_channel);
        OrganizationAccess::grant($student, $course, 'marketplace', 'free');
        $payments->confirmRefund($order, 'refund_org');
        $this->assertFalse(OrganizationAccess::hasChannel($student, $course, 'organization'));
        $this->assertTrue(OrganizationAccess::hasChannel($student, $course, 'marketplace'));
        $this->assertSame('active', Enrollment::where('user_id', $student->id)->where('course_id', $course->id)->value('status'));
    }

    public function test_csv_preview_catches_duplicates_and_confirm_assigns_multiple_classes(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $owner->assignRole('vendor');
        $vendor = Vendor::create(['user_id' => $owner->id, 'brand_name' => 'Import school', 'status' => 'active']);
        $existing = User::factory()->create(['email' => 'existing@example.test', 'portal_access' => 'skillspot_only']);
        $class10 = OrganizationClass::create(['vendor_id' => $vendor->id, 'name' => 'Class 10']);
        $class11 = OrganizationClass::create(['vendor_id' => $vendor->id, 'name' => 'Class 11']);
        $this->actingAs($owner)->withSession(['active_vendor_id' => $vendor->id]);

        $duplicate = "name,email,classes\nStudent,existing@example.test,Class 10\nAgain,existing@example.test,Class 11\n";
        $this->post(route('vendor.organization.students.preview'), ['file' => UploadedFile::fake()->createWithContent('students.csv', $duplicate)])
            ->assertRedirect()->assertSessionHas('import_preview', fn ($rows) => in_array('Duplicate email in file', $rows[1]['errors']));
        $this->assertFalse($existing->hasVendorAccess($vendor->id));

        $valid = "name,email,classes\nStudent,existing@example.test,Class 10;Class 11\n";
        $this->post(route('vendor.organization.students.preview'), ['file' => UploadedFile::fake()->createWithContent('students.csv', $valid)])
            ->assertRedirect()->assertSessionHas('import_preview', fn ($rows) => $rows[0]['errors'] === []);
        $this->post(route('vendor.organization.students.import'))->assertRedirect();
        $this->assertTrue($existing->fresh()->hasVendorAccess($vendor->id));
        $this->assertSame('both', $existing->fresh()->portal_access);
        $this->assertEqualsCanonicalizing([$class10->id, $class11->id], $existing->organizationClasses()->pluck('organization_classes.id')->all());
    }

    public function test_failed_new_student_invitation_is_reported_for_retry(): void
    {
        Storage::fake('local');
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'smtp.example.test', 'mail.from.address' => 'invites@example.test']);
        Setting::set('mail_invites_verified_at', now()->toIso8601String());
        $owner = User::factory()->create();
        $owner->assignRole('vendor');
        $vendor = Vendor::create(['user_id' => $owner->id, 'brand_name' => 'Invite school', 'status' => 'active']);
        $this->actingAs($owner)->withSession(['active_vendor_id' => $vendor->id]);
        $csv = "name,email,classes\nNew Learner,new-learner@example.test,\n";
        $this->post(route('vendor.organization.students.preview'), ['file' => UploadedFile::fake()->createWithContent('students.csv', $csv)])
            ->assertRedirect();
        Password::shouldReceive('sendResetLink')->once()->andThrow(new \RuntimeException('mail unavailable'));
        $this->post(route('vendor.organization.students.import'))->assertRedirect()
            ->assertSessionHas('import_failed', ['new-learner@example.test']);
        $student = User::where('email', 'new-learner@example.test')->firstOrFail();
        $this->assertTrue($student->hasVendorAccess($vendor->id));
        $this->get(route('vendor.organization.index'))->assertOk()->assertSee('Retry invitation');
    }

    public function test_excel_template_and_preview_accept_existing_student(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $owner->assignRole('vendor');
        $vendor = Vendor::create(['user_id' => $owner->id, 'brand_name' => 'Excel school', 'status' => 'active']);
        User::factory()->create(['email' => 'excel-student@example.test']);
        OrganizationClass::create(['vendor_id' => $vendor->id, 'name' => 'Class 10']);
        $this->actingAs($owner)->withSession(['active_vendor_id' => $vendor->id]);
        $this->get(route('vendor.organization.students.template', 'xlsx'))->assertOk();
        $sheet = new Spreadsheet();
        $sheet->getActiveSheet()->fromArray([['name', 'email', 'classes'], ['Excel Student', 'excel-student@example.test', 'Class 10']]);
        $path = tempnam(sys_get_temp_dir(), 'org-xlsx-');
        try {
            IOFactory::createWriter($sheet, 'Xlsx')->save($path);
            $file = new UploadedFile($path, 'students.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
            $this->post(route('vendor.organization.students.preview'), ['file' => $file])->assertRedirect()
                ->assertSessionHas('import_preview', fn ($rows) => $rows[0]['errors'] === [] && $rows[0]['existing']);
        } finally {
            $sheet->disconnectWorksheets();
            if (is_file($path)) unlink($path);
        }
    }

    public function test_platform_super_admin_creates_organization_with_first_admin_and_enters_it(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'smtp.example.test', 'mail.from.address' => 'invites@example.test']);
        Setting::set('mail_invites_verified_at', now()->toIso8601String());
        $platform = User::factory()->create();
        $platform->assignRole('super-admin');
        Password::shouldReceive('sendResetLink')->once()->andReturn(Password::RESET_LINK_SENT);
        $this->actingAs($platform)->post(route('admin.vendors.store'), [
            'brand_name' => 'New Academy', 'admin_name' => 'First Admin', 'admin_email' => 'first-admin@example.test',
            'status' => 'active', 'plan' => 'free',
        ])->assertRedirect();
        $vendor = Vendor::where('brand_name', 'New Academy')->firstOrFail();
        $admin = User::where('email', 'first-admin@example.test')->firstOrFail();
        $this->assertSame($admin->id, $vendor->user_id);
        $this->assertTrue($admin->hasRole('vendor'));
        $this->actingAs($platform)->post(route('admin.vendors.enter', $vendor))->assertRedirect(route('vendor.dashboard'))
            ->assertSessionHas('active_vendor_id', $vendor->id);
        $this->get(route('vendor.organization.index'))->assertOk();
    }

    public function test_teacher_cannot_assign_membership_or_grant_free_access(): void
    {
        $owner = User::factory()->create();
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $student = User::factory()->create();
        $vendor = Vendor::create(['user_id' => $owner->id, 'brand_name' => 'Role school', 'status' => 'active']);
        $vendor->teachers()->attach($teacher);
        $course = Course::create(['title' => 'Role course', 'vendor_id' => $vendor->id, 'organization_published' => true]);
        $this->actingAs($teacher)->withSession(['active_vendor_id' => $vendor->id])
            ->post(route('vendor.students.grant'), ['email' => $student->email, 'course_id' => $course->id])->assertForbidden();
        $this->assertFalse($student->hasVendorAccess($vendor->id));
        $this->assertDatabaseMissing('course_access_grants', ['user_id' => $student->id, 'course_id' => $course->id]);
    }
}
