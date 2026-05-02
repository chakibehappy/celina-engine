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

    public function index(Request $request, $appId, $tableName)
    {
        $dbName = $this->getTargetDatabase($appId);
        $columns = Schema::connection('mysql')->getColumnListing("{$dbName}.{$tableName}");
        
        // Get the search query from the request
        $search = $request->query('search');

        $query = DB::table("{$dbName}.{$tableName}");

        // Apply Global Search across all columns
        if (!empty($search)) {
            $query->where(function($q) use ($columns, $search) {
                foreach ($columns as $column) {
                    $q->orWhere($column, 'LIKE', '%' . $search . '%');
                }
            });
        }

        $paginated = $query->orderBy('id', 'desc')->paginate(8);

        return response()->json([
            'columns' => $columns,
            'data' => $paginated->items(), 
            'pagination' => [
                'last_page' => $paginated->lastPage(),
                'current_page' => $paginated->currentPage(),
                'total' => $paginated->total()
            ]
        ]);
    }

    public function store(Request $request, $appId, $tableName)
    {
        $dbName = App::findOrFail($appId)->database_name;
        
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