<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->string('razorpay_refund_id')->nullable()->unique();
            $table->timestamp('refund_requested_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payment_orders', fn (Blueprint $table) => $table->dropColumn(['razorpay_refund_id', 'refund_requested_at']));
    }
};
