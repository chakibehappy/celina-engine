<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;

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