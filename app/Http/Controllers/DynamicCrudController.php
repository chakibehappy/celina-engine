<?php
// app/Http/Controllers/DynamicCrudController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\App\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DynamicCrudController extends Controller
{
    private function getTargetDatabase($appId)
    {
        return App::findOrFail($appId)->database_name;
    }

    public function index($appId, $tableName)
    {
        $dbName = $this->getTargetDatabase($appId);
        
        // Force Schema to look at the target synthesized database for headers
        $columns = Schema::connection('mysql')->getColumnListing("{$dbName}.{$tableName}");
        
        $data = DB::table("{$dbName}.{$tableName}")
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'columns' => $columns,
            'data' => $data
        ]);
    }

    public function store(Request $request, $appId, $tableName)
    {
        $dbName = App::findOrFail($appId)->database_name;
        
        // Manual insert to bypass Model requirements for synthesized tables
        DB::table("{$dbName}.{$tableName}")->insert(
            $request->except(['id', 'created_at', 'updated_at']) + [
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        return response()->json(['success' => true]);
    }

    public function update(Request $request, $appId, $tableName, $id)
    {
        $dbName = $this->getTargetDatabase($appId);
        $data = $request->except(['id', 'created_at', 'updated_at']);
        $data['updated_at'] = now();

        DB::table("{$dbName}.{$tableName}")->where('id', $id)->update($data);
        return response()->json(['success' => true]);
    }

    public function destroy($appId, $tableName, $id)
    {
        $dbName = $this->getTargetDatabase($appId);
        DB::table("{$dbName}.{$tableName}")->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }
}