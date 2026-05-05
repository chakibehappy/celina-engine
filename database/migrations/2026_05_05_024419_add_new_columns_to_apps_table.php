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
        Schema::table('apps', function (Blueprint $table) {
            // The "Next Step" after Splash (ID of a dynamic screen or route)
            $table->string('starting_screen')->nullable()->after('slug');

            // Controls the entry logic: standard, dynamic, or none (bypassed)
            $table->enum('login_type', ['standard', 'dynamic', 'none'])
                  ->default('standard')
                  ->after('starting_screen');

            // Defines the UI Bone: standard menu, dynamic manifest, or none (fullscreen)
            $table->enum('navigation_type', ['standard', 'dynamic', 'none'])
                  ->default('dynamic')
                  ->after('login_type');
        });

        // Cleanup: Dropping the old hardcoded structure tables
        Schema::dropIfExists('table_menus');
        Schema::dropIfExists('submodules');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->dropColumn(['starting_screen', 'login_type', 'navigation_type']);
        });

        // Note: down() won't easily restore the dropped tables 
        // unless you have their original schemas handy.
    }
};