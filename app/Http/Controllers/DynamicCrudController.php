<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\App\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DynamicCrudController extends Controller
{
    private function getTableQuery($appId, $tableName)
    {
        $app = App::findOrFail($appId);
        $fullPath = "{$app->database_name}.{$tableName}";
        return DB::table($fullPath);
    }

    public function index($appId, $tableName)
    {
        // Get columns to build the dynamic table headers on frontend
        $app = App::findOrFail($appId);
        $columns = Schema::getColumnListing($tableName); // Note: Ensure connection handles cross-db
        
        $data = $this->getTableQuery($appId, $tableName)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'columns' => $columns,
            'data' => $data
        ]);
    }

    public function store(Request $request, $appId, $tableName)
    {
        // Filter out ID and timestamps for the insert
        $data = $request->except(['id', 'created_at', 'updated_at']);
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $this->getTableQuery($appId, $tableName)->insert($data);

        return response()->json(['success' => true]);
    }

    public function destroy($appId, $tableName, $id)
    {
        $this->getTableQuery($appId, $tableName)->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }
}