<?php

namespace App\Services;

use App\Models\CommissionOverride;
use App\Models\Course;
use App\Models\EarningLedgerEntry;
use App\Models\MarketplaceSale;
use App\Models\PaymentOrder;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class MarketplaceService
{
    public static function splitFor(Course $course): array
    {
        $percentage = CommissionOverride::where('course_id', $course->id)->value('instructor_percent');
        if ($percentage === null && !$course->vendor_id && $course->primary_instructor_id) {
            $percentage = CommissionOverride::where('instructor_id', $course->primary_instructor_id)->value('instructor_percent');
        }
        $sellerPercent = round((float) ($percentage ?? Setting::get('marketplace_default_instructor_percent', 70)), 2);
        if ($sellerPercent < 0 || $sellerPercent > 100) throw new \DomainException('Instructor percentage must be between 0 and 100.');

        return ['seller_percent' => $sellerPercent, 'platform_percent' => round(100 - $sellerPercent, 2)];
    }

    public static function recordPaidSale(PaymentOrder $order): MarketplaceSale
    {
        return DB::transaction(function () use ($order) {
            $existing = MarketplaceSale::where('payment_order_id', $order->id)->first();
            if ($existing) return $existing;

            $course = $order->course()->firstOrFail();
            $split = self::splitFor($course);
            $basis = round((float) $order->amount, 2); // Orders already store the amount after any course discount and before tax.
            $sellerShare = round($basis * $split['seller_percent'] / 100, 2);
            $platformShare = round($basis - $sellerShare, 2);
            $organizationId = $course->vendor_id ?: null;
            $sellerUserId = $organizationId ? null : $course->primary_instructor_id;

            $sale = MarketplaceSale::create([
                'payment_order_id' => $order->id,
                'buyer_id' => $order->user_id,
                'course_id' => $course->id,
                'seller_user_id' => $sellerUserId,
                'organization_id' => $organizationId,
                'gross_paid' => $basis,
                'split_basis' => $basis,
                'instructor_percent' => $split['seller_percent'],
                'platform_percent' => $split['platform_percent'],
                'seller_share' => $sellerShare,
                'platform_share' => $platformShare,
                'status' => 'pending',
                'hold_until' => now()->addDays(7),
            ]);

            EarningLedgerEntry::create([
                'marketplace_sale_id' => $sale->id,
                'payee_type' => $organizationId ? 'organization' : 'instructor',
                'payee_user_id' => $sellerUserId,
                'organization_id' => $organizationId,
                'entry_type' => 'seller_earning',
                'amount' => $sellerShare,
                'effective_at' => now(),
            ]);
            EarningLedgerEntry::create([
                'marketplace_sale_id' => $sale->id,
                'payee_type' => 'platform',
                'entry_type' => 'platform_share',
                'amount' => $platformShare,
                'effective_at' => now(),
            ]);
            return $sale;
        });
    }

    public static function releaseAvailableEarnings(): int
    {
        return MarketplaceSale::where('status', 'pending')->where('hold_until', '<=', now())
            ->update(['status' => 'available', 'available_at' => now()]);
    }

    public static function reverseSale(PaymentOrder $order): void
    {
        DB::transaction(function () use ($order) {
            $sale = MarketplaceSale::where('payment_order_id', $order->id)->lockForUpdate()->first();
            if (!$sale || in_array($sale->status, ['reversed', 'paid'], true)) return;
            $sale->update(['status' => 'reversed', 'refunded_at' => now()]);
            EarningLedgerEntry::create([
                'marketplace_sale_id' => $sale->id,
                'payee_type' => $sale->organization_id ? 'organization' : 'instructor',
                'payee_user_id' => $sale->seller_user_id,
                'organization_id' => $sale->organization_id,
                'entry_type' => 'refund_reversal',
                'amount' => -1 * (float) $sale->seller_share,
                'effective_at' => now(),
            ]);
        });
    }
}
