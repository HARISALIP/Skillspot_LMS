<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        // Rename enum value 'skillspot_only' → 'skillspot_only' in users.portal_access
        DB::statement("ALTER TABLE users MODIFY portal_access ENUM('skillspot_only','vendor_only','both') DEFAULT 'skillspot_only'");
        DB::table('users')->where('portal_access', 'skillspot_only')->update(['portal_access' => 'skillspot_only']);
    }

    public function down(): void {
        DB::statement("ALTER TABLE users MODIFY portal_access ENUM('skillspot_only','vendor_only','both') DEFAULT 'skillspot_only'");
        DB::table('users')->where('portal_access', 'skillspot_only')->update(['portal_access' => 'skillspot_only']);
    }
};
