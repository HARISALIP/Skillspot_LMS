<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('tagline')->nullable()->after('description');
            $table->string('primary_color', 20)->default('#2563eb')->after('logo');
            $table->string('accent_color', 20)->default('#7c3aed')->after('primary_color');
            $table->string('banner_image')->nullable()->after('logo');
            $table->string('favicon')->nullable()->after('banner_image');
            $table->string('email')->nullable()->after('tagline');
            $table->string('phone')->nullable()->after('email');
            $table->string('website')->nullable()->after('phone');
            $table->json('social_links')->nullable()->after('settings');
        });
    }
    public function down(): void {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['tagline','primary_color','accent_color','banner_image','favicon','email','phone','website','social_links']);
        });
    }
};
