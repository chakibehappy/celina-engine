<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        // 1. Core Auth & Identity
        Schema::create('apps', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('app_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('app_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_id')->constrained()->onDelete('cascade');
            $table->foreignId('app_role_id')->constrained('app_roles');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        // 2. The Visual Dictionary
        Schema::create('system_icons', function (Blueprint $table) {
            $table->id();
            $table->string('tag')->unique(); // e.g., ICON_DASHBOARD
            $table->enum('type', ['system', 'custom'])->default('system'); 
            $table->text('url')->nullable(); // Default/Web value (Material Symbol name or URL)
            // Platform-specific overrides
            $table->string('kotlin_value')->nullable(); // Icons.Default.Dashboard
            $table->string('flutter_value')->nullable(); // Icons.dashboard_outlined
            $table->string('expo_value')->nullable();    // material-community/view-dashboard
            $table->timestamps();
        });

        // 3. UI Structure
        Schema::create('app_screens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_id')->constrained();
            $table->string('route'); 
            $table->string('title');
            $table->string('type')->default('standard'); 
            $table->longText('content_data')->nullable(); 
            $table->timestamps();
        });

        Schema::create('app_navigations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_id')->constrained()->onDelete('cascade');
            $table->foreignId('screen_id')->constrained('app_screens')->onDelete('cascade'); // The Fix
            $table->string('label');
            $table->foreignId('system_icon_id')->constrained('system_icons');
            $table->integer('icon_size')->default(24);
            $table->integer('font_size')->default(12);
            $table->boolean('show_label')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down() {
        // Drop pivot tables and dependent tables first to avoid FK errors
        Schema::dropIfExists('app_navigations');
        Schema::dropIfExists('app_screens');
        Schema::dropIfExists('system_icons');
        Schema::dropIfExists('app_users');
        Schema::dropIfExists('app_roles');
        Schema::dropIfExists('apps');
    }
};