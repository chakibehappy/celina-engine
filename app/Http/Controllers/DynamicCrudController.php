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

        // Define search/sort parameters from request
        $search = $request->query('search', '');
        $sortCol = $request->query('sort', 'created_at'); // Default sort
        $sortDir = $request->query('direction', 'desc');
        $perPage = $request->query('per_page', 15);

        $query = DB::table("{$dbName}.{$tableName}")
            // Dynamic Search: check all columns for the search string
            ->when($search, function ($q) use ($search, $columns) {
                $q->where(function ($sub) use ($search, $columns) {
                    foreach ($columns as $column) {
                        $sub->orWhere($column, 'LIKE', "%{$search}%");
                    }
                });
            })
            // Dynamic Sort
            ->orderBy($sortCol, $sortDir);

        $paginated = $query->paginate($perPage);

        return response::json([
            'columns' => $columns,
            'data' => $paginated->items(), // The current page records
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'total' => $paginated->total(),
                'prev' => $paginated->previousPageUrl(),
                'next' => $paginated->nextPageUrl(),
            ],
            'meta' => [
                'sort' => $sortCol,
                'direction' => $sortDir
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