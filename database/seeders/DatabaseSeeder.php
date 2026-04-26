<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // 1. Create the App Tenant
        $appId = DB::table('apps')->insertGetId([
            'name' => 'Celina CRM',
            'slug' => 'celina-crm',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Create the App-Specific Role
        $roleId = DB::table('app_roles')->insertGetId([
            'app_id' => $appId,
            'name' => 'Superuser',
            'created_at' => now(),
        ]);

        // 3. Create the App-Specific User
        DB::table('app_users')->insert([
            'app_id' => $appId,
            'app_role_id' => $roleId,
            'name' => 'Admin User',
            'email' => 'admin@celina.com',
            'password' => Hash::make('password'),
            'created_at' => now(),
        ]);

        // 4. System Icons Dictionary
        // We use an associative array to capture the IDs for later use in FKs
        $iconData = [
            'home'   => ['tag' => 'ICON_HOME', 'kotlin' => 'ic_home', 'flutter' => 'Icons.home', 'expo' => 'home', 'url' => 'home'],
            'live'   => ['tag' => 'ICON_LIVE', 'kotlin' => 'ic_bolt', 'flutter' => 'Icons.bolt', 'expo' => 'flash', 'url' => 'bolt'],
            'folder' => ['tag' => 'ICON_FOLDER', 'kotlin' => 'ic_folder', 'flutter' => 'Icons.folder', 'expo' => 'folder', 'url' => 'folder'],
            'person' => ['tag' => 'ICON_PERSON', 'kotlin' => 'ic_person', 'flutter' => 'Icons.person', 'expo' => 'person', 'url' => 'person'],
        ];

        $icons = [];
        foreach ($iconData as $key => $val) {
            $icons[$key] = DB::table('system_icons')->insertGetId([
                'tag' => $val['tag'],
                'type' => 'system',
                'url' => $val['url'],
                'kotlin_value' => $val['kotlin'],
                'flutter_value' => $val['flutter'],
                'expo_value' => $val['expo'],
                'created_at' => now(),
            ]);
        }

        // 5. Register Screens
        $customHtml = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
            <style>
                body { font-family: sans-serif; margin: 0; padding: 20px; background-color: #F8F9FE; }
                .card { background: white; border-radius: 20px; padding: 25px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
                .icon-box { font-size: 40px; margin-bottom: 10px; }
                h1 { font-size: 22px; color: #6C5CE7; }
            </style>
        </head>
        <body>
            <div class="card">
                <div class="icon-box">🚀</div>
                <h1>Engine Activated</h1>
                <p>Hybrid Mode: <b>Active</b></p>
            </div>
        </body>
        </html>';

        $homeScreenId = DB::table('app_screens')->insertGetId([
            'app_id' => $appId,
            'route' => 'home_screen',
            'title' => 'Dashboard',
            'type' => 'standard',
            'created_at' => now(),
        ]);

        $liveScreenId = DB::table('app_screens')->insertGetId([
            'app_id' => $appId,
            'route' => 'custom_webview_screen',
            'title' => 'Live Game',
            'type' => 'custom',
            'content_data' => $customHtml,
            'created_at' => now(),
        ]);

        // 6. Navigation (The Bottom Bar)
        DB::table('app_navigations')->insert([
            [
                'app_id'         => $appId,
                'screen_id'      => $homeScreenId,
                'label'          => 'Home',
                'system_icon_id' => $icons['home'], // Link to the ID
                'icon_size'      => 26,
                'font_size'      => 12,
                'show_label'     => true,
                'order'          => 1,
                'created_at'     => now(),
            ],
            [
                'app_id'         => $appId,
                'screen_id'      => $liveScreenId,
                'label'          => 'Live',
                'system_icon_id' => $icons['live'], // Link to the ID
                'icon_size'      => 26,
                'font_size'      => 12,
                'show_label'     => true,
                'order'          => 2,
                'created_at'     => now(),
            ],
        ]);

        // 7. Menus & Modules
        // Note: Check your app_menus table. 
        // If app_menus also uses system_icon_id, update it below.
        // Assuming app_menus still uses icon_id as a string per your snippet:
        $projectId = DB::table('app_menus')->insertGetId([
            'app_id' => $appId, 
            'label' => 'Projects', 
            'icon_id' => 'folder', // Change this to system_icon_id if you updated the table
            'order' => 1,
            'created_at' => now(),
        ]);

        DB::table('screen_menus')->insert([
            'screen_id' => $homeScreenId,
            'menu_id' => $projectId,
            'order' => 1,
            'created_at' => now(),
        ]);

        DB::table('app_sub_modules')->insert([
            'menu_id' => $projectId,
            'label' => 'All Projects',
            'description' => 'Directory of projects',
            'system_icon_id' => $icons['folder'], // Linked properly
            'table_name' => 'projects',
            'created_at' => now(),
        ]);
    }
}