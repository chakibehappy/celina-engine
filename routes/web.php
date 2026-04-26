<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;

use App\Http\Controllers\MobileDashboardTestController;

Route::get('/', function () {
    return Inertia::render('Home');
});

Route::post('/projects', [ProjectController::class, 'store']);
Route::post('/create-project', [ProjectController::class, 'createProject']);
Route::post('/projects-dashboard/{project_id}', [ProjectController::class, 'saveDashboard']);
Route::post('/projects-database/{project_id}', [ProjectController::class, 'saveDatabase']);
Route::get('/projects/{project_id}', [ProjectController::class, 'show']);
Route::get('/get-active-project', [ProjectController::class, 'getActiveProjectState']);
Route::post('/set-active-project', [ProjectController::class, 'setActiveProject']);

Route::get('/test-create', function (\App\Services\ProjectGenerator $gen) {
    // 1. Grab your specific test project
    $project = \App\Models\Project::where('project_id', 'indo-logistics-001')->firstOrFail();

    // 2. Pass the manifest data to the create method
    // Your 'data' column has the 'manifest' key inside it
    return $gen->create(
        $project->name, 
        '../generated', 
        $project->data['manifest'] 
    );
});


Route::prefix('test-dashboard')->group(function () {
    Route::get('/', [MobileDashboardTestController::class, 'index'])->name('test.dashboard');
    
    // --- Specific Routes (Nav & Screen) ---
    Route::post('/nav', [MobileDashboardTestController::class, 'storeNavigation']);
    Route::post('/screen', [MobileDashboardTestController::class, 'storeScreen']);
    
    Route::put('/nav/{id}', [MobileDashboardTestController::class, 'updateNavigation']);
    Route::put('/screen/{id}', [MobileDashboardTestController::class, 'updateScreenContent']);
    
    Route::delete('/nav/{id}', [MobileDashboardTestController::class, 'destroyNavigation']);
    Route::delete('/screen/{id}', [MobileDashboardTestController::class, 'destroyScreen']);

    // --- Generic Data Routes (User, Menu, Submodule, Role) ---
    // These match the openModal('user') calls in your Vue frontend
    Route::post('/{type}', [MobileDashboardTestController::class, 'storeData']);
    Route::put('/{type}/{id}', [MobileDashboardTestController::class, 'updateData']);
    
    // This allows deleteData('user', id) to work for all generic types
    Route::delete('/{type}/{id}', function($type, $id) {
        // We can reuse the controller's logic or add a destroyData method
        // For now, let's just point it to a new method or handle it simply:
        $controller = app(MobileDashboardTestController::class);
        return $controller->deleteData($type, $id); 
    });
});