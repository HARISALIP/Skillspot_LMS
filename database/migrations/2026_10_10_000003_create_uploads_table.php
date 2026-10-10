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
        if (!Schema::hasTable('uploads')) {
            Schema::create('uploads', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('original_name');
                $table->string('file_name');
                $table->string('mime_type')->nullable();
                $table->string('extension', 20)->nullable();
                $table->unsignedBigInteger('size')->default(0);
                $table->string('disk')->default('public');
                $table->text('path');
                $table->text('url')->nullable();
                $table->string('type', 30)->default('other');
                $table->string('folder', 60)->default('general');
                $table->boolean('is_public')->default(true);
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->json('used_in')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uploads');
    }
};
