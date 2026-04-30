<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\App;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Database\Schema\Blueprint;

class TableArchitectController extends Controller
{
    public function createTable(Request $request)
    {
        $app = App::findOrFail($request->app_id);
        $dbName = $app->database_name;
        $tableName = Str::snake($request->table_name);
        $columns = $request->columns;

        // Use the db name directly in the Schema statement
        Schema::create("{$dbName}.{$tableName}", function (Blueprint $table) use ($columns) {
            $table->id();
            
            foreach ($columns as $col) {
                $name = Str::snake($col['name']);
                $type = $col['type'];
                
                // Map the dynamic column type directly
                $field = $table->$type($name);
                
                if (!empty($col['nullable'])) {
                    $field->nullable();
                }
            }
            
            $table->timestamps();
        });

        // Generate the Controller file to the project's generated folder
        $this->generateController($app, $tableName);

        return back()->with('success', "Synthesized {$tableName} in {$dbName} successfully.");
    }

    public function getAppTables($appId)
    {
        $app = App::findOrFail($appId);
        $dbName = $app->database_name;
        
        // Query the information schema for the specific app database
        $tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = ?", [$dbName]);
        
        return response()->json(array_column($tables, 'table_name'));
    }

    private function generateController($app, $tableName)
    {
        $folderName = 'CC' . Str::studly($app->slug);
        $path = app_path("Http/Controllers/App/{$folderName}");
        
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0775, true);
        }

        $controllerName = Str::studly($tableName) . "Controller";
        $template = "<?php\n\nnamespace App\Http\Controllers\App\\{$folderName};\n\n" .
                    "use App\Http\Controllers\Controller;\n\n" .
                    "class {$controllerName} extends Controller\n{\n" .
                    "    // CELINA-SYNTH Generated\n" .
                    "}";

        File::put("{$path}/{$controllerName}.php", $template);
    }
}