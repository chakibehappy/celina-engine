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
                
                // Map the dynamic column type directly
                $field = $table->$type($name);
                
                if (!empty($col['nullable'])) { 
                    $field->nullable(); 
                }
            }
            $table->timestamps();
        });

        // 2. Generate the Model with connection, table, and fillable properties
        $fillableColumns = array_map(fn($c) => Str::snake($c['name']), $columns);
        $this->generateModel($app, $tableName, $fillableColumns);

        return back()->with('success', "Synthesized {$tableName} in {$dbName} successfully.");
    }

    private function generateModel($app, $tableName, $fillable)
    {
        $folderName = 'CC' . Str::studly($app->slug);
        $path = app_path("Models/App/{$folderName}");
        
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 0775, true);
        }

        $modelName = Str::studly($tableName);
        $fillableString = "['" . implode("', '", $fillable) . "']";
        $dbName = $app->database_name;

        // Template includes dynamic connection and table naming
        $template = "<?php\n\nnamespace App\Models\App\\{$folderName};\n\n" .
                    "use Illuminate\Database\Eloquent\Model;\n\n" .
                    "class {$modelName} extends Model\n{\n" .
                    "    /**\n" .
                    "     * The connection name for the model.\n" .
                    "     */\n" .
                    "    protected \$connection = 'mysql'; // Or your dynamic connection key\n\n" .
                    "    /**\n" .
                    "     * The table associated with the model.\n" .
                    "     */\n" .
                    "    protected \$table = '{$dbName}.{$tableName}';\n\n" .
                    "    /**\n" .
                    "     * CELINA-SYNTH Generated Fillable Attributes\n" .
                    "     */\n" .
                    "    protected \$fillable = {$fillableString};\n" .
                    "}";

        File::put("{$path}/{$modelName}.php", $template);
    }

    public function getAppTables($appId)
    {
        $app = App::findOrFail($appId);
        $dbName = $app->database_name;
        
        $tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = ?", [$dbName]);
        
        return response()->json(array_column($tables, 'table_name'));
    }
}