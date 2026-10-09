<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        // Add portal_access column if it doesn't exist, then ensure correct enum values
        if (!Schema::hasColumn('users', 'portal_access')) {
            Schema::table('users', function (Blueprint $table) {
                $table->enum('portal_access', ['skillspot_only', 'vendor_only', 'both'])
                      ->default('skillspot_only')
                      ->after('remember_token');
            });
        } else {
            DB::statement("ALTER TABLE users MODIFY portal_access ENUM('skillspot_only','vendor_only','both') DEFAULT 'skillspot_only'");
            DB::table('users')->where('portal_access', 'itforge_only')->update(['portal_access' => 'skillspot_only']);
        }
    }

    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('portal_access');
        });
    }
};
