<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            // Kept separate from content so old Blade lessons stay readable until edited.
            $table->json('block_document')->nullable()->after('content');
            $table->json('instructor_notes_document')->nullable()->after('block_document');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['block_document', 'instructor_notes_document']);
        });
    }
};
