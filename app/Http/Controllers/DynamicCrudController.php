<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\App;
use Illuminate\Support\Facades\DB;

class DynamicCrudController extends Controller
{
    private function setConnection($appId) {
        $app = App::findOrFail($appId);
        config(['database.connections.tenant.database' => $app->database_name]);
        DB::purge('tenant');
    }

    public function index($appId, $tableName)
    {
        $this->setConnection($appId);
        $data = DB::connection('tenant')->table($tableName)->get();
        return response()->json($data);
    }

    public function store(Request $request, $appId, $tableName)
    {
        $this->setConnection($appId);
        DB::connection('tenant')->table($tableName)->insert($request->except(['id', 'created_at', 'updated_at']));
        return response()->json(['success' => true]);
    }

    public function destroy($appId, $tableName, $id)
    {
        $this->setConnection($appId);
        DB::connection('tenant')->table($tableName)->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }
}