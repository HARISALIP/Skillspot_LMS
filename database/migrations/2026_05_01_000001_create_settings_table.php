<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 60)->index();   // branding | mail | storage | payment | license | security
            $table->string('key', 120)->unique();
            $table->longText('value')->nullable();
            $table->string('type', 30)->default('text'); // text|textarea|boolean|password|json
            $table->string('label', 200)->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('settings'); }
};
