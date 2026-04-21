<?php
// app/Http/Controllers/ProjectController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ProjectGenerator;
use App\Models\Project;
use App\Models\EditorActivity;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{

    public function createProject(Request $request)
    {
        $projectId = $request->input('project_id');
        $name = $request->input('name');
        $data = $request->input('data');
        $currentPage = $request->input('current_page', 'main');
        // Get the path from input, fallback to a default if empty
        $userPath = $request->input('full_path', 'C:\\Celina Engine Projects');

        // 1. Save record to Database
        $project = Project::updateOrCreate(
            ['project_id' => $projectId],
            ['name' => $name, 'data' => $data]
        );
        // Log Initial Activity
        $this->logActivity($projectId, $data, $currentPage);

        // 2. Physical File Generation
        // folderPath = user input + Project Name
        $folderPath = rtrim($userPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;
        $filePath = $folderPath . DIRECTORY_SEPARATOR . $projectId . ".celina";

        try {
            // Create directory recursively
            if (!File::exists($folderPath)) {
                File::makeDirectory($folderPath, 0755, true);
            }

            // Write the config file
            File::put($filePath, json_encode($data, JSON_PRETTY_PRINT));

            return response()->json([
                'status' => 'success', 
                'id' => $project->id,
                'physical_path' => $filePath
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Failed to create directory or file. Check permissions.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Manually set the active project (used when opening an existing file).
     */
    public function setActiveProject(Request $request)
    {
        $request->validate(['project_id' => 'required|string']);
        $projectId = $request->input('project_id');
        $project = Project::where('project_id', $projectId)->first();
        $data = $project ? $project->data : [];
        $this->logActivity($projectId, $data, 'main');
        return response()->json([
            'status' => 'success',
            'project_id' => $projectId
        ]);
    }

    public function store(Request $request) // save data for database creator
    {
        $project = Project::updateOrCreate(
            ['project_id' => $request->input('project_id')], // Find by your custom ID
            [
                'name' => $request->input('name', 'New Project'),
                'data' => $request->input('data')
            ]
        );
        return response()->json(['status' => 'success', 'id' => $project->id]);
    }

    public function saveDatabase(Request $request, string $project_id)
    {
        $project = Project::where('project_id', $project_id)->firstOrFail();
        $currentData = $project->data ?? [];
        $newData = $request->input('data');
        $mergedData = array_replace_recursive($currentData, $newData);
        $project->update([
            'data' => $mergedData
        ]);
        return response()->json(['status' => 'success', 'message' => 'Database updated']);
    }

    public function saveDashboard(Request $request, string $project_id)
    {
        $project = Project::where('project_id', $project_id)->firstOrFail();
        $currentData = $project->data ?? [];
        $newData = $request->input('data');
        $mergedData = array_replace_recursive($currentData, $newData);
        $project->update([
            'data' => $mergedData
        ]);
        return response()->json(['status' => 'success', 'message' => 'Dashboard updated']);
    }

    public function show(string $project_id)
    {
        $project = Project::where('project_id', $project_id)->first();
        if (!$project) {
            return response()->json(['error' => 'Project not found'], 404);
        }
        return response()->json($project);
    }

    private function logActivity($projectId, $data, $pageName = 'index')
    {
        DB::transaction(function () use ($projectId, $data, $pageName) {
            // 1. Deactivate all previous activities for this project
            EditorActivity::where('is_active', true)->update(['is_active' => false]);

            // 2. Create the new active record with the last open page
            EditorActivity::create([
                'project_id' => $projectId,
                'current_data' => $data,
                'last_open_page' => $pageName,
                'is_active' => true
            ]);
        });
    }

    public function getActiveProjectState()
    {
        $activity = EditorActivity::where('is_active', true)
            ->latest('updated_at') // Get the most recent active one
            ->first();

        if (!$activity) {
            return response()->json([
                'error' => 'No active project session found',
                'status' => 'idle'
            ], 404);
        }

        return response()->json([
            'project_id'   => $activity->project_id,
            'last_page'    => $activity->last_open_page,
            'data'         => $activity->current_data,
            'last_updated' => $activity->updated_at->toDateTimeString()
        ]);
    }
}