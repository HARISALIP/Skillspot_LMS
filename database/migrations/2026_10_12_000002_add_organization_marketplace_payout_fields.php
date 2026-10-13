<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            if (!Schema::hasColumn('vendors', 'payout_account_name')) $table->string('payout_account_name')->nullable();
            if (!Schema::hasColumn('vendors', 'payout_account_number')) $table->text('payout_account_number')->nullable();
            if (!Schema::hasColumn('vendors', 'payout_ifsc')) $table->text('payout_ifsc')->nullable();
            if (!Schema::hasColumn('vendors', 'marketplace_verified_at')) $table->timestamp('marketplace_verified_at')->nullable();
        });
    }

    public function down(): void
    {
        // The first marketplace migration owns these fields on fresh installs.
    }
};
