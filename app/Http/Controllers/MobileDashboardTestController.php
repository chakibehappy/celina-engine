<?php

namespace App\Http\Controllers;

use App\Models\App\AppNavigation;
use App\Models\App\AppScreen;
use App\Models\App\App;
use App\Models\App\AppUser;
use App\Models\App\AppRole;
use App\Models\App\AppMenu;
use App\Models\App\AppSubModule;
use App\Models\App\SystemIcon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Schema;

class MobileDashboardTestController extends Controller
{
    public function index()
    {
        $getSchema = function($tableName) {
            return collect(Schema::getColumnListing($tableName))
                ->reject(fn($col) => in_array($col, ['created_at', 'updated_at', 'deleted_at']))
                ->map(function($col) use ($tableName) {
                    $dbType = Schema::getColumnType($tableName, $col);
                    
                    $uiType = match(true) {
                        str_contains($col, 'email') => 'email',
                        str_contains($col, 'password') => 'password',
                        str_contains($col, '_id') => 'select',
                        in_array($dbType, ['text', 'mediumtext', 'longtext']) => 'textarea',
                        in_array($dbType, ['boolean', 'tinyint']) => 'checkbox',
                        default => 'text'
                    };

                    $optionsKey = null;
                    
                    if (str_ends_with($col, '_id')) {
                        // If it's specifically app_id, we want 'apps'
                        if ($col === 'app_id') {   $optionsKey = 'apps'; } 
                        else if ($col === 'system_icon_id') {  $optionsKey = 'system_icons'; } 
                        else {
                            // For app_role_id -> roles, menu_id -> menus, etc.
                            $optionsKey = str_replace(['app_', '_id'], ['', 's'], $col);
                        }
                    }

                    return [
                        'key'     => $col,
                        'label'   => ucwords(str_replace('_', ' ', $col)),
                        'type'    => $uiType,
                        'options' => $optionsKey
                    ];
                })->values();
        };

        return Inertia::render('MobileDashboard/Index', [
            // Your data props
            'apps'        => App::all(),
            'roles'       => AppRole::all(),
            'users'       => AppUser::with('role')->get(),
            'screens'     => AppScreen::all(),
            'navigations' => AppNavigation::with('screen')->orderBy('order')->get(),
            'menus'       => AppMenu::all(),
            'subModules'  => AppSubModule::with('menu')->get(),
            'system_icons'=> SystemIcon::all(),
            
            // Auto-generated schemas for the frontend
            'schemas' => [
                'app'           => $getSchema('apps'), 
                'role'          => $getSchema('app_roles'),
                'user'          => $getSchema('app_users'),
                'menu'          => $getSchema('app_menus'),
                'submodule'     => $getSchema('app_sub_modules'),
                'screen'        => $getSchema('app_screens'),
                'navigation'    => $getSchema('app_navigations'),
                'system_icon'   => $getSchema('system_icons'),
            ],
        ]);
    }

    // --- NAVIGATION CRUD ---
    public function storeNavigation(Request $request)
    {
        $data = $request->validate([
            'app_id' => 'required|exists:apps,id',
            'screen_id' => 'required|exists:app_screens,id',
            'label' => 'required|string',
            'system_icon_id' => 'required|exists:system_icons,id',
            'order' => 'required|integer',
        ]);
        AppNavigation::create($data);
        return back();
    }

    public function destroyNavigation($id)
    {
        AppNavigation::findOrFail($id)->delete();
        return back();
    }

    // --- SCREEN CRUD ---
    public function storeScreen(Request $request)
    {
        $data = $request->validate([
            'app_id' => 'required|exists:apps,id',
            'route' => 'required|string|unique:app_screens,route',
            'title' => 'required|string',
            'type' => 'required|string', // standard or custom
            'content_data' => 'nullable|string',
        ]);
        AppScreen::create($data);
        return back();
    }

    public function destroyScreen($id)
    {
        // Careful: Foreign keys on migrations (onDelete cascade) will handle the rest
        AppScreen::findOrFail($id)->delete();
        return back();
    }

    public function updateNavigation(Request $request, $id)
    {
        $nav = AppNavigation::findOrFail($id);
        $nav->update($request->validate([
            'label' => 'required|string',
            'icon_size' => 'required|integer',
            'show_label' => 'required|boolean',
        ]));
        return back()->with('success', 'Navigation updated!');
    }

    public function updateScreenContent(Request $request, $id)
    {
        $screen = AppScreen::findOrFail($id);
        $screen->update($request->validate([
            'title' => 'required|string',
            'type' => 'required|string',
            'content_data' => 'nullable|string',
        ]));
        return back()->with('success', 'Screen content updated!');
    }

   // --- GENERIC DATA CRUD ---
    public function storeData(Request $request, $type)
    {
        $model = $this->getModelByType($type);
        $data = $request->all();
        // Auto-inject app_id if missing
        if (!isset($data['app_id']) && App::exists()) { 
            $data['app_id'] = App::first()->id; 
        }
        $model::create($data);
        return back()->with('success', ucfirst($type) . ' created!');
    }

    public function updateData(Request $request, $type, $id)
    {
        $model = $this->getModelByType($type);
        $item = $model::findOrFail($id);
        $item->update($request->all());
        return back()->with('success', ucfirst($type) . ' updated!');
    }

    public function deleteData($type, $id)
    {
        $model = $this->getModelByType($type);
        $model::findOrFail($id)->delete();
        return back()->with('success', ucfirst($type) . ' deleted!');
    }

    private function getModelByType($type)
    {
        return match($type) {
            'user'       => AppUser::class,
            'menu'       => AppMenu::class,
            'submodule'  => AppSubModule::class,
            'role'       => AppRole::class,
            'screen'     => AppScreen::class,
            'navigation' => AppNavigation::class,
            'app'        => App::class,
            'system_icon' => SystemIcon::class,
            default => abort(404),
        };
    }
}