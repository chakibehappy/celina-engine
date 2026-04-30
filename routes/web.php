<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;

use App\Http\Controllers\MobileDashboardTestController;
use App\Http\Controllers\TableArchitectController;
use App\Http\Controllers\DynamicCrudController;
use App\Http\Controllers\Auth\GoogleController;

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
    Route::get('/', [MobileDashboardTestController::class, 'index'])->name('test-dashboard.index');
    
    // --- Specific Routes (Nav & Screen) ---
    Route::post('/nav', [MobileDashboardTestController::class, 'storeNavigation'])->name('test-dashboard.nav.store');
    Route::post('/screen', [MobileDashboardTestController::class, 'storeScreen'])->name('test-dashboard.screen.store');
    
    Route::put('/nav/{id}', [MobileDashboardTestController::class, 'updateNavigation'])->name('test-dashboard.nav.update');
    Route::put('/screen/{id}', [MobileDashboardTestController::class, 'updateScreenContent'])->name('test-dashboard.screen.update');
    
    Route::delete('/nav/{id}', [MobileDashboardTestController::class, 'destroyNavigation'])->name('test-dashboard.nav.destroy');
    Route::delete('/screen/{id}', [MobileDashboardTestController::class, 'destroyScreen'])->name('test-dashboard.screen.destroy');

    // --- Generic Data Routes ---
    Route::post('/{type}', [MobileDashboardTestController::class, 'storeData'])->name('test-dashboard.store');
    Route::put('/{type}/{id}', [MobileDashboardTestController::class, 'updateData'])->name('test-dashboard.update');
    Route::delete('/{type}/{id}', function($type, $id) {
        $controller = app(MobileDashboardTestController::class);
        return $controller->deleteData($type, $id); 
    })->name('test-dashboard.delete');

    // -- GENERIC TABLE --
    Route::prefix('celina-synth')->group(function () {
        // Meta-Architecture
        Route::post('/create-table', [TableArchitectController::class, 'createTable'])->name('architect.create-table');
        Route::get('/get-tables/{appId}', [TableArchitectController::class, 'getAppTables'])->name('architect.get-tables');

        // Dynamic CRUD (The "Ghost" Routes)
        Route::get('/data/{appId}/{tableName}', [DynamicCrudController::class, 'index'])->name('dynamic.index');
        Route::post('/data/{appId}/{tableName}', [DynamicCrudController::class, 'store'])->name('dynamic.store');
        Route::put('/data/{appId}/{tableName}/{id}', [DynamicCrudController::class, 'update'])->name('dynamic.update');
        Route::delete('/data/{appId}/{tableName}/{id}', [DynamicCrudController::class, 'destroy'])->name('dynamic.delete');
    });
});

// for register in Google Cloud Console
Route::get('/auth/google/callback', [GoogleController::class, 'handleCallback'])->name('auth.google.callback');