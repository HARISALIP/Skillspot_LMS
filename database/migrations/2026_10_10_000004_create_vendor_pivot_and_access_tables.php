<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. student_vendor_access
        if (!Schema::hasTable('student_vendor_access')) {
            Schema::create('student_vendor_access', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
                $table->string('granted_by')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();
                $table->unique(['user_id', 'vendor_id']);
            });
        }

        // 2. vendor_users
        if (!Schema::hasTable('vendor_users')) {
            Schema::create('vendor_users', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('role', 50)->default('member');
                $table->timestamps();
                $table->unique(['vendor_id', 'user_id']);
            });
        }

        // 3. vendor_teachers
        if (!Schema::hasTable('vendor_teachers')) {
            Schema::create('vendor_teachers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['vendor_id', 'user_id']);
            });
        }

        // 4. vendor_preallowed_contacts
        if (!Schema::hasTable('vendor_preallowed_contacts')) {
            Schema::create('vendor_preallowed_contacts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
                $table->string('contact');
                $table->string('type', 20)->default('email');
                $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }

        // 5. course_allowed_users
        if (!Schema::hasTable('course_allowed_users')) {
            Schema::create('course_allowed_users', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
                $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['course_id', 'user_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_allowed_users');
        Schema::dropIfExists('vendor_preallowed_contacts');
        Schema::dropIfExists('vendor_teachers');
        Schema::dropIfExists('vendor_users');
        Schema::dropIfExists('student_vendor_access');
    }
};
