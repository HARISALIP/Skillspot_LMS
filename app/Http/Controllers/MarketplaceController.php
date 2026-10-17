<?php

namespace App\Http\Controllers;

use App\Models\CommissionOverride;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\EarningLedgerEntry;
use App\Models\InstructorProfile;
use App\Models\MarketplaceSale;
use App\Models\PayoutBatch;
use App\Models\PayoutItem;
use App\Models\User;
use App\Models\Vendor;
use App\Services\MarketplaceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarketplaceController extends Controller
{
    public function browse(Request $request)
    {
        $query = Course::marketplaceLive()->with(['primaryInstructor', 'vendor'])->withCount('enrollments');

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(fn ($courses) => $courses->where('title', 'like', "%{$search}%")->orWhere('short_introduction', 'like', "%{$search}%"));
        }
        if ($category = $request->string('category')->trim()->value()) $query->where('category', $category);

        $courses = $query->latest()->paginate(12)->withQueryString();
        $categories = Course::marketplaceLive()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category');

        return view('marketplace.browse', compact('courses', 'categories'));
    }

    public function applyForm()
    {
        return view('marketplace.apply', ['profile' => auth()->user()->instructorProfile]);
    }

    public function apply(Request $request)
    {
        abort_unless(!in_array($request->user()->instructorProfile?->status, ['approved', 'suspended'], true), 403);
        $data = $request->validate([
            'legal_name' => ['required', 'string', 'max:255'],
            'bio' => ['required', 'string', 'min:40', 'max:5000'],
        ]);
        $profile = InstructorProfile::updateOrCreate(['user_id' => $request->user()->id], $data + [
            'status' => 'pending', 'reviewed_by' => null, 'review_note' => null, 'approved_at' => null,
        ]);
        return redirect()->route('studio.dashboard')->with('success', $profile->wasRecentlyCreated ? 'Instructor application submitted for review.' : 'Instructor application updated and resubmitted for review.');
    }

    public function updatePayout(Request $request)
    {
        $profile = $request->user()->instructorProfile;
        abort_unless($profile?->status === 'approved', 403);
        $data = $request->validate([
            'payout_account_name' => ['required', 'string', 'max:255'],
            'payout_account_number' => ['required', 'string', 'min:6', 'max:80'],
            'payout_ifsc' => ['required', 'string', 'min:6', 'max:30'],
        ]);
        $profile->update($data);
        return back()->with('success', 'Payout details saved securely.');
    }

    public function dashboard()
    {
        $user = auth()->user();
        MarketplaceService::releaseAvailableEarnings();
        $profile = $user->instructorProfile;
        $courses = Course::where('primary_instructor_id', $user->id)->latest()->get();
        $sales = MarketplaceSale::where('seller_user_id', $user->id)->with('course')->latest()->get();
        $balances = [
            'pending' => $sales->where('status', 'pending')->sum('seller_share'),
            'available' => $sales->where('status', 'available')->sum('seller_share'),
            'paid' => $sales->where('status', 'paid')->sum('seller_share'),
            'ledger' => EarningLedgerEntry::where('payee_type', 'instructor')->where('payee_user_id', $user->id)->sum('amount'),
        ];
        return view('marketplace.dashboard', compact('profile', 'courses', 'sales', 'balances'));
    }

    public function organizationDashboard()
    {
        $user = auth()->user();
        abort_unless($user->hasRole(['vendor', 'admin', 'super-admin']), 403);
        $vendorId = session('active_vendor_id');
        $vendors = Vendor::query();
        if ($vendorId) $vendors->whereKey($vendorId);
        if (!$user->hasRole(['admin', 'super-admin'])) {
            $vendors->whereHas('managers', fn ($managers) => $managers->where('users.id', $user->id));
        }
        $vendor = $vendors->firstOrFail();
        MarketplaceService::releaseAvailableEarnings();
        $sales = MarketplaceSale::where('organization_id', $vendor->id)->with('course')->latest()->get();
        $balances = [
            'pending' => $sales->where('status', 'pending')->sum('seller_share'),
            'available' => $sales->where('status', 'available')->sum('seller_share'),
            'paid' => $sales->where('status', 'paid')->sum('seller_share'),
        ];
        $courses = Course::where('vendor_id', $vendor->id)->latest()->get();
        return view('marketplace.organization-dashboard', compact('vendor', 'courses', 'sales', 'balances'));
    }

    public function submitCourse(Request $request, Course $course)
    {
        $this->authorizeCourseOwner($request->user(), $course);
        abort_unless(in_array($course->marketplace_status, ['draft', 'needs_changes', 'rejected'], true), 422, 'This course is not awaiting its first review.');
        abort_unless($request->user()->hasRole(['admin', 'super-admin']) || ($course->vendor_id ? $request->user()->hasRole(['vendor', 'teacher']) : $request->user()->instructorProfile?->status === 'approved'), 403, 'An approved creator profile is required before submitting a course.');
        $problems = $this->courseQualityProblems($course);
        if ($problems) return back()->withErrors(['marketplace' => implode(' ', $problems)]);
        $course->update(['marketplace_status' => 'submitted', 'marketplace_visible' => false, 'is_published' => false, 'visibility' => 'draft', 'submitted_at' => now(), 'review_feedback' => null]);
        CourseReview::create(['course_id' => $course->id, 'submitted_by' => $request->user()->id, 'status' => 'submitted', 'submitted_at' => now()]);
        return back()->with('success', 'Course submitted for marketplace review.');
    }

    public function applications()
    {
        $this->admin();
        return view('marketplace.applications', ['profiles' => InstructorProfile::with('user')->latest()->paginate(20)]);
    }

    public function reviewApplication(Request $request, InstructorProfile $profile)
    {
        $this->admin();
        $data = $request->validate(['status' => ['required', 'in:approved,rejected,suspended'], 'review_note' => ['nullable', 'string', 'max:3000']]);
        DB::transaction(function () use ($profile, $data, $request) {
            $profile->update($data + ['reviewed_by' => $request->user()->id, 'approved_at' => $data['status'] === 'approved' ? now() : null]);
            if ($data['status'] === 'suspended') {
                Course::where('primary_instructor_id', $profile->user_id)->whereNull('vendor_id')
                    ->where('marketplace_status', 'live')->get()->each(function (Course $course) use ($request, $data) {
                        $course->update(['marketplace_status' => 'suspended', 'marketplace_visible' => false,
                            'is_published' => false, 'visibility' => 'draft', 'reviewed_at' => now(),
                            'reviewed_by' => $request->user()->id, 'review_feedback' => $data['review_note'] ?? 'Creator suspended']);
                        CourseReview::create(['course_id' => $course->id, 'submitted_by' => $course->primary_instructor_id,
                            'reviewed_by' => $request->user()->id, 'status' => 'suspended',
                            'feedback' => $data['review_note'] ?? 'Creator suspended',
                            'submitted_at' => $course->submitted_at ?? now(), 'reviewed_at' => now()]);
                    });
            }
        });
        return back()->with('success', 'Instructor application updated.');
    }

    public function reviewQueue()
    {
        $this->admin();
        $courses = Course::whereIn('marketplace_status', ['submitted', 'needs_changes'])
            ->orWhere(fn ($query) => $query->where('marketplace_status', 'live')->whereNull('reviewed_at'))
            ->with(['primaryInstructor', 'vendor', 'sections.lessons'])->latest('submitted_at')->paginate(20);
        $recentChanges = \App\Models\CourseChangeAudit::with(['course', 'actor'])->latest('created_at')->limit(30)->get();
        return view('marketplace.review-queue', compact('courses', 'recentChanges'));
    }

    public function reviewCourse(Request $request, Course $course)
    {
        $this->admin();
        $data = $request->validate(['status' => ['required', 'in:live,needs_changes,rejected,suspended,archived'], 'feedback' => ['nullable', 'string', 'max:5000']]);
        $live = $data['status'] === 'live';
        abort_if($live && !$course->isMarketplaceLive() && $course->marketplace_status !== 'submitted', 422, 'The creator must submit this course for review first.');
        $course->update([
            'marketplace_status' => $data['status'], 'marketplace_visible' => $live,
            'is_published' => $live, 'visibility' => $live ? 'public' : 'draft',
            'reviewed_at' => now(), 'reviewed_by' => $request->user()->id, 'review_feedback' => $data['feedback'] ?? null,
        ]);
        CourseReview::create(['course_id' => $course->id, 'submitted_by' => $course->primary_instructor_id ?: $request->user()->id, 'reviewed_by' => $request->user()->id, 'status' => $data['status'], 'feedback' => $data['feedback'] ?? null, 'submitted_at' => $course->submitted_at ?: now(), 'reviewed_at' => now()]);
        return back()->with('success', 'Course review recorded.');
    }

    public function publishAdminCourse(Request $request, Course $course)
    {
        $this->admin();
        abort_unless($course->primary_instructor_id === $request->user()->id && !$course->vendor_id, 403);
        abort_if($course->isMarketplaceLive(), 422, 'This course is already live.');
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:2000']]);
        DB::transaction(function () use ($course, $request, $data) {
            $course->update(['marketplace_status' => 'live', 'marketplace_visible' => true,
                'visibility' => 'public', 'is_published' => true, 'reviewed_at' => now(),
                'reviewed_by' => $request->user()->id, 'review_feedback' => $data['reason']]);
            CourseReview::create(['course_id' => $course->id, 'submitted_by' => $request->user()->id,
                'reviewed_by' => $request->user()->id, 'status' => 'admin_override',
                'feedback' => $data['reason'], 'submitted_at' => now(), 'reviewed_at' => now()]);
        });
        return back()->with('success', 'Admin-authored course published with an audit record.');
    }

    public function commissions()
    {
        $this->admin();
        return view('marketplace.commissions', [
            'courses' => Course::with('primaryInstructor')->orderBy('title')->get(),
            'instructors' => InstructorProfile::where('status', 'approved')->with('user')->get(),
            'overrides' => CommissionOverride::all()->keyBy(fn ($row) => $row->course_id ? "course:{$row->course_id}" : "instructor:{$row->instructor_id}"),
        ]);
    }

    public function saveCommission(Request $request)
    {
        $this->admin();
        $data = $request->validate(['scope' => ['required', 'in:default,instructor,course'], 'id' => ['nullable', 'integer'], 'instructor_percent' => ['required', 'numeric', 'min:0', 'max:100']]);
        if ($data['scope'] === 'default') {
            \App\Models\Setting::set('marketplace_default_instructor_percent', $data['instructor_percent'], 'marketplace', 'number', 'Default instructor share');
        } else {
            $request->validate(['id' => $data['scope'] === 'course' ? ['required', 'exists:courses,id'] : ['required', 'exists:instructor_profiles,user_id']]);
            $key = $data['scope'] === 'course' ? ['course_id' => $data['id']] : ['instructor_id' => $data['id']];
            CommissionOverride::updateOrCreate($key, $key + ['instructor_percent' => $data['instructor_percent'], 'set_by' => $request->user()->id]);
        }
        return back()->with('success', 'Commission saved. Platform share is calculated automatically as the remainder.');
    }

    public function payouts()
    {
        $this->admin();
        MarketplaceService::releaseAvailableEarnings();
        $sales = MarketplaceSale::where('status', 'available')->with(['course', 'seller', 'organization'])->latest()->get();
        $batches = PayoutBatch::with('items.sale.course')->latest()->paginate(15);
        $negativeBalances = EarningLedgerEntry::selectRaw('payee_type, payee_user_id, organization_id, SUM(amount) as balance')
            ->whereIn('payee_type', ['instructor', 'organization'])
            ->groupBy('payee_type', 'payee_user_id', 'organization_id')
            ->havingRaw('SUM(amount) < 0')->get();
        return view('marketplace.payouts', compact('sales', 'batches', 'negativeBalances'));
    }

    public function createPayout(Request $request)
    {
        $this->admin();
        $data = $request->validate(['sale_ids' => ['required', 'array', 'min:1'], 'sale_ids.*' => ['integer', 'exists:marketplace_sales,id'], 'note' => ['nullable', 'string', 'max:1000']]);
        $sales = MarketplaceSale::whereIn('id', $data['sale_ids'])->where('status', 'available')->get();
        abort_unless($sales->count() === count($data['sale_ids']), 422, 'Only currently available earnings can be added to a payout.');
        abort_unless($sales->pluck('seller_user_id')->unique()->count() === 1 && $sales->pluck('organization_id')->unique()->count() === 1, 422, 'A payout batch can pay one seller only.');
        if ($sellerId = $sales->first()->seller_user_id) {
            $profile = InstructorProfile::where('user_id', $sellerId)->first();
            abort_unless($profile?->payout_account_name && $profile?->payout_account_number && $profile?->payout_ifsc, 422, 'The creator must add payout details before a payout can be prepared.');
        } else {
            $organization = Vendor::findOrFail($sales->first()->organization_id);
            abort_unless($organization->marketplace_verified_at && $organization->payout_account_name
                && $organization->payout_account_number && $organization->payout_ifsc, 422,
                'Verify the organization and its bank details before preparing a payout.');
        }
        $ledgerBalance = EarningLedgerEntry::where('payee_type', $sales->first()->organization_id ? 'organization' : 'instructor')
            ->when($sales->first()->organization_id, fn ($query) => $query->where('organization_id', $sales->first()->organization_id))
            ->when($sellerId ?? null, fn ($query) => $query->where('payee_user_id', $sellerId))->sum('amount');
        $pendingShare = MarketplaceSale::where('status', 'pending')
            ->when($sales->first()->organization_id, fn ($query) => $query->where('organization_id', $sales->first()->organization_id))
            ->when($sellerId ?? null, fn ($query) => $query->where('seller_user_id', $sellerId))->sum('seller_share');
        abort_unless($ledgerBalance - $pendingShare >= $sales->sum('seller_share'), 422, 'Outstanding refund debt must be reconciled before this full payout can be prepared.');
        $batch = DB::transaction(function () use ($sales, $data, $request) {
            $batch = PayoutBatch::create(['seller_user_id' => $sales->first()->seller_user_id, 'organization_id' => $sales->first()->organization_id, 'note' => $data['note'] ?? null, 'created_by' => $request->user()->id]);
            foreach ($sales as $sale) PayoutItem::create(['payout_batch_id' => $batch->id, 'marketplace_sale_id' => $sale->id, 'amount' => $sale->seller_share]);
            return $batch;
        });
        return back()->with('success', "Payout batch #{$batch->id} created. Record the bank transfer before marking it paid.");
    }

    public function markPayoutPaid(Request $request, PayoutBatch $batch)
    {
        $this->admin();
        $data = $request->validate(['reference' => ['required', 'string', 'max:255'], 'transfer_confirmed' => ['accepted']]);
        DB::transaction(function () use ($batch, $data, $request) {
            abort_unless($batch->status === 'draft', 422, 'This payout batch has already been processed.');
            $items = $batch->items()->with('sale')->lockForUpdate()->get();
            abort_unless($items->every(fn ($item) => $item->sale->status === 'available'), 422, 'One or more earnings are no longer available.');
            foreach ($items as $item) {
                $item->sale->update(['status' => 'paid', 'paid_at' => now()]);
                $item->update(['reference' => $data['reference']]);
                EarningLedgerEntry::create(['marketplace_sale_id' => $item->sale->id, 'payee_type' => $item->sale->organization_id ? 'organization' : 'instructor', 'payee_user_id' => $item->sale->seller_user_id, 'organization_id' => $item->sale->organization_id, 'entry_type' => 'payout', 'amount' => -1 * (float) $item->amount, 'effective_at' => now()]);
            }
            $batch->update(['status' => 'paid', 'reference' => $data['reference'], 'paid_by' => $request->user()->id, 'paid_at' => now()]);
        });
        return back()->with('success', 'Payout marked paid and recorded in the audit ledger.');
    }

    private function authorizeCourseOwner(User $user, Course $course): void
    {
        if ($user->hasRole(['admin', 'super-admin'])) return;
        abort_unless(
            $course->primary_instructor_id === $user->id
            || ($course->vendor_id && $course->teachers()->where('users.id', $user->id)->exists())
            || ($course->vendor_id && $course->vendor?->managers()->where('users.id', $user->id)->exists()),
            403
        );
    }

    private function courseQualityProblems(Course $course): array
    {
        $course->loadMissing('sections.lessons');
        $lessons = $course->sections->flatMap->lessons;
        $outcomes = (array) $course->outcomes;
        $requirements = (array) $course->requirements;
        $problems = [];
        if (!filled($course->short_introduction) || !filled($course->description)) $problems[] = 'Add an introduction and description.';
        if ($course->price <= 0 && !$course->is_free) $problems[] = 'Set a valid marketplace price or mark the course free.';
        if ($lessons->isEmpty()) $problems[] = 'Add at least one lesson.';
        return $problems;
    }

    private function admin(): void
    {
        abort_unless(auth()->user()->hasRole(['admin', 'super-admin']), 403);
    }
}
