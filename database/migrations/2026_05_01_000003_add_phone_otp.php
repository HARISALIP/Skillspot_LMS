<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add phone to users
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->unique()->after('email');
            $table->string('phone_verified_at')->nullable()->after('phone');
        });

        // OTP table — ready for future use
        Schema::create('otps', function (Blueprint $table) {
            $table->id();
            $table->string('identifier');          // email or phone number
            $table->enum('type', ['email','phone']);
            $table->string('otp', 10);
            $table->string('purpose', 30)->default('register'); // register|login|reset
            $table->boolean('used')->default(false);
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->index(['identifier','type','purpose']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone','phone_verified_at']);
        });
        Schema::dropIfExists('otps');
    }
};
