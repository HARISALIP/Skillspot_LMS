<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('brand_name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->string('domain')->nullable()->unique();
            $table->text('description')->nullable();
            $table->enum('status', ['pending','active','suspended'])->default('pending');
            $table->string('license_key')->nullable()->unique();
            $table->string('plan')->default('free');
            $table->timestamp('license_expires_at')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('vendors'); }
};
