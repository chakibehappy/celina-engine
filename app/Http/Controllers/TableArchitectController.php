<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\App\App;
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

        // 1. Execute Migration directly in target DB
        Schema::create("{$dbName}.{$tableName}", function (Blueprint $table) use ($columns) {
            $table->id();
            foreach ($columns as $col) {
                $name = Str::snake($col['name']);
                $type = $col['type'];
                $field = $table->$type($name);
                if (!empty($col['nullable'])) { $field->nullable(); }
            }
            $table->timestamps();
        });

        // 2. Generate the Controller with $fillable property
        $fillableColumns = array_map(fn($c) => Str::snake($c['name']), $columns);
        $this->generateController($app, $tableName, $fillableColumns);

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

    private function generateController($app, $tableName, $fillable)
    {
        $folderName = 'CC' . Str::studly($app->slug);
        $path = app_path("Http/Controllers/App/{$folderName}");
        
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0775, true);
        }

        // Format fillable array for the PHP template
        $fillableString = "['" . implode("', '", $fillable) . "']";
        $controllerName = Str::studly($tableName) . "Controller";

        $template = "<?php\n\nnamespace App\Http\Controllers\App\\{$folderName};\n\n" .
                    "use App\Http\Controllers\Controller;\n\n" .
                    "class {$controllerName} extends Controller\n{\n" .
                    "    /**\n" .
                    "     * CELINA-SYNTH Generated Fillable Attributes\n" .
                    "     */\n" .
                    "    protected \$fillable = {$fillableString};\n\n" .
                    "    // Add synthesis logic below\n" .
                    "}";

        File::put("{$path}/{$controllerName}.php", $template);
    }
}