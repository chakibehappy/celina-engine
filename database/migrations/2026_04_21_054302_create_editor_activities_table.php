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
        Schema::create('editor_activities', function (Blueprint $table) {
            $table->id();
            $table->string('project_id');
            $table->json('current_data');
            $table->string('last_open_page')->nullable(); // e.g., "dashboard", "db_manager", "settings"
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['project_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('editor_activities');
    }
};
