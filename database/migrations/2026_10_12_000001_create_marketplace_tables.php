<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->foreignId('primary_instructor_id')->nullable()->after('vendor_id')->constrained('users')->nullOnDelete();
            $table->string('marketplace_status', 24)->default('draft')->after('is_published')->index();
            $table->boolean('marketplace_visible')->default(false)->after('marketplace_status');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_feedback')->nullable();
        });

        // Existing public courses remain accessible; newly created courses start as drafts.
        DB::table('courses')->where('is_published', true)->update([
            'marketplace_status' => 'live', 'marketplace_visible' => true,
        ]);

        Schema::create('instructor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status', 24)->default('pending')->index();
            $table->string('legal_name')->nullable();
            $table->text('bio')->nullable();
            $table->string('payout_account_name')->nullable();
            $table->text('payout_account_number')->nullable();
            $table->text('payout_ifsc')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_note')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('course_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 24)->default('submitted');
            $table->text('feedback')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('commission_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('instructor_percent', 5, 2);
            $table->foreignId('set_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique('instructor_id');
            $table->unique('course_id');
        });

        Schema::create('marketplace_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->decimal('gross_paid', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('processor_fee', 12, 2)->default(0);
            $table->decimal('split_basis', 12, 2);
            $table->decimal('instructor_percent', 5, 2);
            $table->decimal('platform_percent', 5, 2);
            $table->decimal('seller_share', 12, 2);
            $table->decimal('platform_share', 12, 2);
            $table->string('status', 24)->default('pending')->index();
            $table->timestamp('hold_until')->index();
            $table->timestamp('available_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('earning_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_sale_id')->constrained()->cascadeOnDelete();
            $table->string('payee_type', 24);
            $table->foreignId('payee_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->string('entry_type', 32);
            $table->decimal('amount', 12, 2);
            $table->timestamp('effective_at');
            $table->timestamps();
        });

        Schema::create('payout_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->string('status', 24)->default('draft')->index();
            $table->string('reference')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payout_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payout_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('marketplace_sale_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('reference')->nullable();
            $table->timestamps();
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('revocation_reason')->nullable();
        });
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('payout_account_name')->nullable();
            $table->text('payout_account_number')->nullable();
            $table->text('payout_ifsc')->nullable();
            $table->timestamp('marketplace_verified_at')->nullable();
        });
        Schema::create('certificate_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certificate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 32);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_items');
        Schema::dropIfExists('payout_batches');
        Schema::dropIfExists('earning_ledger_entries');
        Schema::dropIfExists('marketplace_sales');
        Schema::dropIfExists('commission_overrides');
        Schema::dropIfExists('course_reviews');
        Schema::dropIfExists('instructor_profiles');
        Schema::dropIfExists('certificate_audits');
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('revoked_by');
            $table->dropColumn(['revoked_at', 'revocation_reason']);
        });
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['payout_account_name', 'payout_account_number', 'payout_ifsc', 'marketplace_verified_at']);
        });
        Schema::table('courses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('primary_instructor_id');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['marketplace_status', 'marketplace_visible', 'submitted_at', 'reviewed_at', 'review_feedback']);
        });
    }
};
