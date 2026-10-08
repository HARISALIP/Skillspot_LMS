<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('live_class_session_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_class_id')->constrained('live_classes')->cascadeOnDelete();
            $table->foreignId('live_class_session_id')->constrained('live_class_sessions')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->enum('file_type', ['recording', 'notes', 'resource']);
            $table->string('title', 255)->nullable();
            $table->string('disk', 20)->default('r2'); // r2 or local
            $table->string('path', 500);               // storage path
            $table->string('mime_type', 100)->nullable();
            $table->bigInteger('file_size')->nullable(); // bytes
            $table->boolean('is_visible')->default(true); // teacher can hide
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_class_session_files');
    }
};
