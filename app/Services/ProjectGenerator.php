<?php

namespace App\Services;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\File;
use PDO;

class ProjectGenerator
{
    public function create(string $name, string $basePath, array $manifest, bool $force = true): string
    {
        set_time_limit(0);
        $fullBasePath = realpath(base_path($basePath)) ?: base_path($basePath);
        $projectPath = rtrim($fullBasePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;

        File::ensureDirectoryExists($fullBasePath);
        if (File::exists($projectPath)) {
            if (!$force) throw new \Exception("Project already exists: {$name}");
            File::deleteDirectory($projectPath);
        }
        $this->saveManifest($projectPath, $manifest);
        // 1. Create Laravel Project (v12)
        $this->run([ PHP_BINARY, $this->composerPhar(), 'create-project',
            'laravel/laravel:^12.0', $projectPath, '--prefer-dist', '--no-interaction'
        ]);
        // 2. Setup .env base
        $env = $projectPath . DIRECTORY_SEPARATOR . '.env';
        if (File::exists($ex = $projectPath . DIRECTORY_SEPARATOR . '.env.example')) {
            File::copy($ex, $env);
            $key = 'base64:' . base64_encode(random_bytes(32));
            File::put($env, preg_replace('/^APP_KEY=.*$/m', "APP_KEY=$key", File::get($env)));
        }
        // 3. DATABASE (MYSQL ONLY)
        $this->setupDatabase($projectPath, $manifest);
        // 4. MIGRATIONS -> Updated to Direct SQL
        $this->clearLaravelRuntime($projectPath);
        $this->generateMigrations($projectPath, $manifest);
        // 5. FRONTEND
        $this->setupFrontend($projectPath, $manifest);
        // 6. BACKEND
        $this->generateModels($projectPath, $manifest);
        $this->createAuthController($projectPath);
        $this->setupAuthVue($projectPath);
        $this->seedDatabaseRecords($projectPath, $manifest);

        return $projectPath;
    }

    private function saveManifest(string $projectPath, array $manifest): void
    {
        $filePath = $projectPath . DIRECTORY_SEPARATOR . $manifest['project_id'] .'project.celina';
        File::put($filePath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function generateModels(string $projectPath, array $manifest): void
    {
        $appPath = $projectPath . '/app/Models';
        File::ensureDirectoryExists($appPath);
        foreach ($manifest['database'] as $table) {
            $tableName = $table['table'];
            $modelName = str_replace(' ', '', ucwords(str_replace('_', ' ', $tableName)));
            $filePath = $appPath . "/{$modelName}.php";
            $isUser = ($tableName === 'users');
            // Build template with HEREDOC to keep it clean and avoid white-space issues
            $template = "<?php

    namespace App\Models;

    " . ($isUser 
        ? "use Illuminate\Foundation\Auth\User as Authenticatable;\n" 
        : "use Illuminate\Database\Eloquent\Model;\n") . "

    class {$modelName} " . ($isUser ? "extends Authenticatable" : "extends Model") . "
    {
        protected \$table = '{$tableName}';
        protected \$guarded = [];
    }";

            File::put($filePath, $template);
        }
    }

    private function createAuthController(string $projectPath): void
    {
        $path = $projectPath . '/app/Http/Controllers/AuthController.php';
        $template = "<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request \$request)
    {
        \$request->validate([
            'username' => 'required|string',
            'password' => 'required|string'
        ]);

        if (Auth::attempt(['username' => \$request->username, 'password' => \$request->password])) {
            \$request->session()->regenerate();
            return redirect('/dashboard');
        }

        return back()->withErrors(['login' => 'Invalid credentials']);
    }
}
";
        file_put_contents($path, $template);
    }

    private function setupAuthVue(string $projectPath): void
    {
        $dir = $projectPath . '/resources/js/Pages/Auth';

        File::ensureDirectoryExists($dir);

        file_put_contents($dir . '/Login.vue',
    "<template>
    <div class='flex items-center justify-center h-screen bg-gray-100'>
    <form @submit.prevent='submit' class='bg-white p-6 rounded shadow w-80'>
        
        <h1 class='text-xl font-bold mb-4'>Login</h1>

        <input v-model='form.username' placeholder='Username' class='w-full border p-2 mb-2' />
        <input v-model='form.password' type='password' placeholder='Password' class='w-full border p-2 mb-2' />

        <button class='w-full bg-blue-500 text-white p-2'>Login</button>

        <p v-if='error' class='text-red-500 mt-2'>{{ error }}</p>
    </form>
    </div>
    </template>

    <script setup>
    import { reactive, ref } from 'vue'
    import { router } from '@inertiajs/vue3'

    const form = reactive({
    username: '',
    password: ''
    })

    const error = ref('')

    function submit() {
    router.post('/login', form, {
        onError: (e) => {
        error.value = e.login
        }
    })
    }
    </script>
    ");
    }

    private function seedDatabaseRecords(string $projectPath, array $manifest): void
    {
        $projectId = $manifest['project_id'];
        $dbName = str_replace('-', '_', $projectId);
        
        $pdo = new PDO(
            "mysql:host=127.0.0.1;dbname={$dbName}",
            "root",
            "",
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        foreach ($manifest['database'] as $table) {
            $tableName = $table['table'];
            $records = $table['records'] ?? [];

            // 1. Handle Users Table specifically if empty to ensure at least one admin exists
            if ($tableName === 'users' && empty($records)) {
                $hashed = password_hash('password', PASSWORD_BCRYPT);
                $pdo->exec("
                    INSERT INTO users (username, password, created_at, updated_at)
                    VALUES ('admin', '{$hashed}', NOW(), NOW())
                    ON DUPLICATE KEY UPDATE username=username
                ");
                continue;
            }

            // 2. Populate records from manifest data directly
            foreach ($records as $record) {
                $columns = array_keys($record);
                
                // Prepare values and handle password hashing for users table
                $values = [];
                foreach ($record as $key => $val) {
                    if ($tableName === 'users' && $key === 'password') {
                        // Only hash if it's not already a BCRYPT hash
                        $values[$key] = (strlen($val) == 60 && strpos($val, '$2y$') === 0) 
                            ? $val 
                            : password_hash($val, PASSWORD_BCRYPT);
                    } else {
                        $values[$key] = $val;
                    }
                }

                // Add timestamps if not present in manifest
                if (!in_array('created_at', $columns)) {
                    $columns[] = 'created_at';
                    $values['created_at'] = date('Y-m-d H:i:s');
                }
                if (!in_array('updated_at', $columns)) {
                    $columns[] = 'updated_at';
                    $values['updated_at'] = date('Y-m-d H:i:s');
                }

                $colNames = implode('`, `', $columns);
                $placeholders = implode(', ', array_fill(0, count($columns), '?'));
                
                $sql = "INSERT INTO `{$tableName}` (`{$colNames}`) VALUES ({$placeholders}) 
                        ON DUPLICATE KEY UPDATE `updated_at` = VALUES(`updated_at`)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute(array_values($values));
            }
        }
    }

    private function composerPhar(): string
    {
        return 'C:\\composer\\composer.phar';
    }

    /**
     * MYSQL SETUP (NO SQLITE ANYMORE)
     */
    private function setupDatabase(string $projectPath, array $manifest): void
    {
        $envFile = $projectPath . DIRECTORY_SEPARATOR . '.env';

        $projectId = $manifest['project_id'];
        $dbName = str_replace('-', '_', $projectId);
        $projectName = ucfirst(strtolower(str_replace('-', ' ', $projectId)));

        $envContent = File::get($envFile);

        // FORCE MYSQL CONFIG
        $envContent = preg_replace('/DB_CONNECTION=.*/', "DB_CONNECTION=mysql", $envContent);
        $envContent = preg_replace('/^#?\s?DB_HOST=.*$/m', "DB_HOST=127.0.0.1", $envContent);
        $envContent = preg_replace('/^#?\s?DB_PORT=.*$/m', "DB_PORT=3306", $envContent);
        $envContent = preg_replace('/^#?\s?DB_DATABASE=.*$/m', "DB_DATABASE={$dbName}", $envContent);
        $envContent = preg_replace('/^#?\s?DB_USERNAME=.*$/m', "DB_USERNAME=root", $envContent);
        $envContent = preg_replace('/^#?\s?DB_PASSWORD=.*$/m', "DB_PASSWORD=", $envContent);
          
        $envContent = preg_replace('/^APP_NAME=.*$/m', "APP_NAME=\"{$projectName}\"", $envContent);
        $envContent = preg_replace('/^SESSION_DRIVER=.*$/m', "SESSION_DRIVER=file", $envContent);
        $envContent = preg_replace('/^CACHE_STORE=.*$/m', "CACHE_STORE=file", $envContent);
        
        File::put($envFile, $envContent);

        // CREATE DATABASE AUTOMATICALLY
        $this->createMySqlDatabase($dbName);
    }

    /**
     * AUTO CREATE MYSQL DATABASE
     */
    private function createMySqlDatabase(string $dbName): void
    {
        try {
            $pdo = new PDO(
                "mysql:host=127.0.0.1;port=3306",
                "root",
                "",
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $pdo->exec("
                CREATE DATABASE IF NOT EXISTS `{$dbName}`
                CHARACTER SET utf8mb4
                COLLATE utf8mb4_unicode_ci
            ");

        } catch (\Throwable $e) {
            throw new \Exception("MySQL DB creation failed: " . $e->getMessage());
        }
    }

    /**
     * Easiest fix: Skip clearing runtime for a brand new project 
     * because the tables (cache, sessions, etc) don't exist yet.
     */
    private function clearLaravelRuntime(string $projectPath): void
    {
        // Removed artisan clear commands to prevent Table Not Found errors 
        // on fresh DBs that haven't run standard migrations yet.
    }

    private function cleanDefaultMigrations(string $projectPath): void
    {
        $migrationPath = $projectPath . '/database/migrations';
        File::cleanDirectory($migrationPath);
    }

    /**
     * Updated: Runs Direct SQL and writes files for future use.
     * Updated: Fixed SQL syntax for UNIQUE constraints and added type safety.
     */
    private function generateMigrations(string $projectPath, array $manifest): void
    {
        $this->cleanDefaultMigrations($projectPath);
        
        $dbName = str_replace('-', '_', $manifest['project_id']);
        $databaseSchema = $manifest['database'];

        $pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname={$dbName}", "root", "", [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

        foreach ($databaseSchema as $index => $table) {
            $tableName = $table['table'];
            
            $timestamp = date('Y_m_d_') . str_pad($index, 6, '0', STR_PAD_LEFT);
            $fileName = "{$timestamp}_create_{$tableName}_table.php";
            $path = $projectPath . "/database/migrations/{$fileName}";

            $columnsHtml = "";
            // We manually add the ID column here
            $sqlColumns = ["`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY"];

            foreach ($table['columns'] as $col) {
                $type = strtolower($col['type']);
                $name = $col['name'];

                // FIX 1: Skip 'id' if it exists in the manifest to avoid duplication
                if ($name === 'id') continue;

                // SQL Mapping
                $sqlType = match($type) {
                    'varchar', 'string' => "VARCHAR(255)",
                    'integer', 'int'    => "INT",
                    'bigint', 'increments' => "BIGINT",
                    'boolean'           => "TINYINT(1)",
                    'text'              => "TEXT",
                    'timestamp'         => "TIMESTAMP NULL",
                    default             => "VARCHAR(255)"
                };

                // FIX 2: Ensure proper UNIQUE syntax (Name -> Type -> Unique)
                $colSql = "`{$name}` {$sqlType}";
                if (!empty($col['unique'])) $colSql .= " UNIQUE";
                $sqlColumns[] = $colSql;

                // Migration Mapping
                $migrationType = ($type === 'varchar') ? 'string' : $type;
                if ($migrationType === 'increments') $migrationType = 'bigInteger';
                
                $line = "\$table->{$migrationType}('{$name}')";
                if (!empty($col['unique'])) $line .= "->unique()";
                if (!empty($col['constrained'])) $line .= "->constrained('{$col['constrained']}')";
                $columnsHtml .= "            {$line};\n";
            }

            $sqlColumns[] = "`created_at` TIMESTAMP NULL";
            $sqlColumns[] = "`updated_at` TIMESTAMP NULL";

            // Execute Raw SQL
            $pdo->exec("DROP TABLE IF EXISTS `{$tableName}`");
            $pdo->exec("CREATE TABLE `{$tableName}` (" . implode(", ", $sqlColumns) . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            // Write Migration File
            $template = "<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->id();
{$columnsHtml}            \$table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('{$tableName}'); }
};";
            File::put($path, $template);
        }

        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    private function setupFrontend(string $projectPath, array $manifest): void
    {
        $this->run([PHP_BINARY, $this->composerPhar(), 'require', 'inertiajs/inertia-laravel'], $projectPath);
        $this->run([PHP_BINARY, 'artisan', 'inertia:middleware'], $projectPath);
        $this->createInertiaMiddlewareFile($projectPath);
        
        $this->run(['cmd', '/c', 'npm.cmd install'], $projectPath);
        $this->run(['cmd', '/c', 'npm.cmd install vue@3 @inertiajs/vue3'], $projectPath);

        $this->run(['cmd', '/c', 'npm.cmd install tailwindcss @tailwindcss/vite'], $projectPath);
        $this->run(['cmd', '/c', 'npm.cmd install -D @vitejs/plugin-vue'], $projectPath);

        $this->patchViteConfig($projectPath);
        $this->setupTailwind($projectPath);
        $this->registerInertiaMiddleware($projectPath);
        $this->setupRootView($projectPath);
        $this->setupRoutes($projectPath, $manifest);
        $this->setupVueApp($projectPath);

        $this->createMainLayout($projectPath, $manifest);
        $this->createDataTableController($projectPath); 
        $this->createDataTableComponent($projectPath); 
        $this->compilePages($projectPath, $manifest);  // (replaces manual Home.vue)
    }

    private function createDataTableController(string $projectPath): void
    {
        $path = $projectPath . '/app/Http/Controllers/DataTableController.php';
        $template = "<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Schema;

    class DataTableController extends Controller
    {
        public function fetch(\$table) {
            if (!Schema::hasTable(\$table)) return response()->json(['error' => 'Not found'], 404);
            return response()->json(DB::table(\$table)->latest()->get());
        }

        public function store(Request \$request, \$table) {
            \$data = \$request->except(['id', 'created_at', 'updated_at']);
            \$data['created_at'] = now();
            \$data['updated_at'] = now();
            DB::table(\$table)->insert(\$data);
            return back();
        }

        public function update(Request \$request, \$table, \$id) {
            \$data = \$request->except(['id', 'created_at', 'updated_at']);
            \$data['updated_at'] = now();
            DB::table(\$table)->where('id', \$id)->update(\$data);
            return back();
        }

        public function destroy(\$table, \$id) {
            DB::table(\$table)->where('id', \$id)->delete();
            return back();
        }
    }
    ";
        File::put($path, $template);
    }

    private function createInertiaMiddlewareFile(string $projectPath): void
    {
        $path = $projectPath . '/app/Http/Middleware/HandleInertiaRequests.php';
        File::ensureDirectoryExists(dirname($path));

        $template = "<?php

    namespace App\Http\Middleware;

    use Illuminate\Http\Request;
    use Inertia\Middleware;

    class HandleInertiaRequests extends Middleware
    {
        protected \$rootView = 'app';

        public function share(Request \$request): array
        {
            return array_merge(parent::share(\$request), [
                'auth' => [
                    'user' => \$request->user(),
                ],
            ]);
        }
    }";
        File::put($path, $template);
    }

    private function patchViteConfig(string $path): void
    {
        $file = $path . '/vite.config.js';

        $content = "import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: { base: null, includeAbsolute: false },
            },
        }),
    ],
});";

        file_put_contents($file, $content);
    }

    private function setupTailwind(string $path): void
    {
        if (File::exists($path . '/tailwind.config.js')) unlink($path . '/tailwind.config.js');
        if (File::exists($path . '/postcss.config.js')) unlink($path . '/postcss.config.js');

        $cssContent = "@import 'tailwindcss';

@source '../views/**/*.blade.php';
@source '../js/**/*.js';
@source '../js/**/*.vue';";

        file_put_contents($path . '/resources/css/app.css', $cssContent);
    }

    private function registerInertiaMiddleware(string $path): void
    {
        $appFile = $path . '/bootstrap/app.php';

        if (!file_exists($appFile)) return;

        $content = file_get_contents($appFile);

        if (!str_contains($content, 'Middleware;')) {
            $content = str_replace('<?php', "<?php\n\nuse Illuminate\\Foundation\\Configuration\\Middleware;", $content);
        }

        if (!str_contains($content, 'HandleInertiaRequests')) {
            $search = '->withMiddleware(function (Middleware $middleware) {';

            $replace = $search . "
        \$middleware->web(append: [
            \\App\\Http\\Middleware\\HandleInertiaRequests::class,
        ]);";

            $content = str_replace($search, $replace, $content);
            file_put_contents($appFile, $content);
        }
    }

    private function setupRoutes(string $path, array $manifest): void
    {
        $pages = $manifest['dashboard']['pages'];
        $routeLines = "";

        foreach ($pages as $page) {
            $component = str_replace(' ', '', ucwords($page['name']));
            // Avoid duplicating the root /dashboard if it's the home page
            $routeLines .= "    Route::get('/{$page['slug']}', fn() => Inertia::render('{$component}'));\n";
        }

        $routeContent = "<?php
            use Illuminate\Support\Facades\Route;
            use Inertia\Inertia;
            use App\Http\Controllers\AuthController;
            use App\Http\Controllers\DataTableController;

            Route::get('/', [AuthController::class, 'showLogin']);
            Route::post('/login', [AuthController::class, 'login']);

            Route::middleware('auth')->group(function () {
                {$routeLines}
                
                // CUD Endpoints
                Route::get('/api/data/{table}', [DataTableController::class, 'fetch']);
                Route::post('/api/data/{table}', [DataTableController::class, 'store']);
                Route::put('/api/data/{table}/{id}', [DataTableController::class, 'update']);
                Route::delete('/api/data/{table}/{id}', [DataTableController::class, 'destroy']);
            });
        ";
        File::put($path . '/routes/web.php', $routeContent);
    }

    private function setupRootView(string $path): void
    {
        if (file_exists($f = $path . '/resources/views/welcome.blade.php')) {
            unlink($f);
        }

        file_put_contents($path . '/resources/views/app.blade.php',
"<!DOCTYPE html>
<html>
<head>
<meta charset=\"utf-8\" />
<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\" />
@vite(['resources/css/app.css', 'resources/js/app.js'])
@inertiaHead
</head>
<body class=\"bg-gray-100 font-sans text-gray-900\">
@inertia
</body>
</html>");
    }

    private function setupVueApp(string $path): void
    {
        file_put_contents($path . '/resources/js/app.js',
"import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';

createInertiaApp({
  resolve: name => {
    const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
    return pages[`./Pages/\${name}.vue`];
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) }).use(plugin).mount(el);
  },
});
");

        File::ensureDirectoryExists($p = $path . '/resources/js/Pages');

        file_put_contents($p . '/Home.vue',
"<template>
<div class=\"p-10\">
  <h1 class=\"text-3xl font-bold text-blue-600\">CELINA Generated App</h1>
  <p class=\"mt-4 text-gray-600\">Ready.</p>
</div>
</template>");
    }

    private function createMainLayout(string $projectPath, array $manifest): void
    {
        $dir = $projectPath . '/resources/js/Layouts';
        File::ensureDirectoryExists($dir);

        // Dynamic Sidebar Links
        $navLinks = "";
        foreach ($manifest['dashboard']['pages'] as $page) {
            $slug = $page['slug'] ?? strtolower(str_replace(' ', '-', $page['name']));
            $navLinks .= "
            <Link 
                href='/{$slug}' 
                :class=\"[
                    'px-3 py-2 rounded-md text-[11px] font-bold transition-all flex items-center justify-between cursor-pointer',
                    \$page.url === '/{$slug}' ? 'bg-blue-100 text-blue-700' : 'text-gray-500 hover:bg-gray-200/50'
                ]\"
            >
                <span>" . strtoupper($page['name']) . "</span>
            </Link>";
        }

        $template = "
    <template>
        <div class='w-full h-screen bg-gray-50 flex overflow-hidden font-sans antialiased text-gray-800'>
            <aside class='w-60 bg-white border-r border-gray-200 p-5 flex flex-col'>
                <div class='flex items-center space-x-2 mb-8 px-2'>
                    <div class='w-7 h-7 bg-blue-600 rounded-lg flex items-center justify-center text-white font-bold text-[10px]'>C</div>
                    <span class='font-bold text-sm tracking-tight text-gray-900 uppercase'>CELINA_PRO</span>
                </div>
                
                <nav class='space-y-1 flex-grow overflow-y-auto pr-2 custom-scroll'>
                    {$navLinks}
                </nav>

                <div class='mt-auto pt-4 border-t border-gray-100'>
                    <Link href='/logout' method='post' as='button' class='text-[10px] font-bold text-gray-400 hover:text-red-500 uppercase tracking-tighter'>Logout Session</Link>
                </div>
            </aside>

            <main class='flex-grow h-full bg-white relative flex flex-col overflow-hidden'>
                <header class='h-14 border-b border-gray-100 flex items-center px-8 justify-between shrink-0 bg-white'>
                    <h2 class='text-[11px] font-bold uppercase tracking-widest text-gray-400'>{{ title }}</h2>
                    <div class='flex items-center space-x-4'>
                        <span class='text-[9px] font-bold px-2 py-1 bg-gray-100 text-gray-400 rounded uppercase'>Internal Build</span>
                    </div>
                </header>
                
                <div class='flex-grow overflow-y-auto p-8 custom-scroll'>
                    <div class='max-w-6xl mx-auto'>
                        <slot />
                    </div>
                </div>
            </main>
        </div>
    </template>

    <script setup>
    import { Link } from '@inertiajs/vue3';
    defineProps(['title']);
    </script>

    <style scoped>
    .custom-scroll::-webkit-scrollbar { width: 3px; }
    .custom-scroll::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
    </style>";

        File::put($dir . '/MainLayout.vue', $template);
    }

    private function createDataTableComponent(string $projectPath): void
    {
        $dir = $projectPath . '/resources/js/Components';
        File::ensureDirectoryExists($dir);

        $template = "
    <template>
        <div class='w-full p-2'>
            <div class='flex justify-between items-center mb-2'>
                <h3 class='text-[12px] font-bold uppercase text-gray-700 tracking-tight'>
                    {{ title }}
                </h3>
                <button 
                    @click='openCreateModal' 
                    class='bg-[#ff4400] text-white text-[10px] px-3 py-1 font-bold uppercase rounded hover:opacity-90 transition-all shadow-sm'
                >
                    + Add New
                </button>
            </div>

            <div class='overflow-x-auto bg-white border border-gray-200 rounded shadow-sm'>
                <table class='w-full text-left border-collapse'>
                    <thead>
                        <tr class='bg-gray-50 border-b border-gray-200'>
                            <th v-for='col in columns' :key='col.key' class='p-3 text-[10px] font-bold uppercase text-gray-600 tracking-wider'>
                                {{ col.label }}
                            </th>
                            <th class='p-3 text-[10px] font-bold uppercase text-gray-600 tracking-wider text-right'>Actions</th>
                        </tr>
                    </thead>
                    <tbody class='divide-y divide-gray-100'>
                        <tr v-for='item in items' :key='item.id' class='hover:bg-gray-50 transition-colors'>
                            <td v-for='col in columns' :key='col.key' class='p-3 text-[11px] text-gray-500 font-mono'>
                                {{ item[col.key] }}
                            </td>
                            <td class='p-3 text-right space-x-2'>
                                <button @click='openEditModal(item)' class='text-blue-500 hover:text-blue-700 text-[10px] font-bold uppercase'>Edit</button>
                                <button @click='deleteItem(item.id)' class='text-red-400 hover:text-red-600 text-[10px] font-bold uppercase'>Del</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <div v-if='items.length === 0' class='p-8 text-center text-gray-400 text-[10px] uppercase tracking-widest italic'>
                    No records found
                </div>
            </div>

            <div v-if='showModal' class='fixed inset-0 bg-black/60 backdrop-blur-sm z-[9999] flex items-center justify-center p-4'>
                <div class='bg-white w-full max-w-md rounded-lg shadow-2xl overflow-hidden'>
                    <div class='p-4 border-b bg-gray-50 flex justify-between items-center'>
                        <span class='text-[12px] font-black uppercase text-gray-800'>{{ isEditing ? 'Edit' : 'Create' }} record</span>
                        <button @click='closeModal' class='text-gray-400 hover:text-black'>✕</button>
                    </div>
                    
                    <form @submit.prevent='save' class='p-6 space-y-4 max-h-[60vh] overflow-y-auto'>
                        <div v-for='col in columns' :key='col.key'>
                            <template v-if=\"col.key !== 'id' && col.key !== 'created_at' && col.key !== 'updated_at'\">
                                <label class='block text-[10px] font-bold uppercase text-gray-500 mb-1'>{{ col.label }}</label>
                                <input 
                                    v-model='form[col.key]' 
                                    type='text' 
                                    class='w-full border rounded px-3 py-2 text-sm focus:ring-2 focus:ring-[#ff4400]/20 focus:border-[#ff4400] outline-none transition-all'
                                />
                            </template>
                        </div>

                        <div class='pt-4 border-t flex justify-end gap-2'>
                            <button type='button' @click='closeModal' class='px-4 py-2 text-[10px] font-bold uppercase text-gray-600'>Cancel</button>
                            <button type='submit' class='px-4 py-2 bg-[#ff4400] text-white text-[10px] font-bold uppercase rounded shadow-lg shadow-[#ff4400]/20'>
                                {{ isEditing ? 'Update Data' : 'Save Data' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </template>

    <script setup>
    import { ref, reactive, onMounted } from 'vue';
    import axios from 'axios';
    import { router } from '@inertiajs/vue3';

    const props = defineProps({
        title: String,
        endpoint: String,
        columns: Array
    });

    const items = ref([]);
    const showModal = ref(false);
    const isEditing = ref(false);
    const currentId = ref(null);
    const form = reactive({});

    const refresh = async () => {
        const res = await axios.get(props.endpoint);
        items.value = res.data;
    };

    const openCreateModal = () => {
        isEditing.value = false;
        currentId.value = null;
        props.columns.forEach(c => form[c.key] = '');
        showModal.value = true;
    };

    const openEditModal = (item) => {
        isEditing.value = true;
        currentId.value = item.id;
        props.columns.forEach(c => form[c.key] = item[c.key]);
        showModal.value = true;
    };

    const closeModal = () => {
        showModal.value = false;
    };

    const save = () => {
        const options = {
            onSuccess: () => { closeModal(); refresh(); },
        };

        if (isEditing.value) {
            router.put(`\${props.endpoint}/\${currentId.value}`, form, options);
        } else {
            router.post(props.endpoint, form, options);
        }
    };

    const deleteItem = (id) => {
        if (confirm('Are you sure you want to delete this record?')) {
            router.delete(`\${props.endpoint}/\${id}`, { onSuccess: refresh });
        }
    };

    onMounted(refresh);
    </script>";

        File::put($dir . '/DataTable.vue', $template);
    }

    private function compilePages(string $projectPath, array $manifest): void
    {
        $pagesDir = $projectPath . '/resources/js/Pages';
        File::ensureDirectoryExists($pagesDir);

        foreach ($manifest['dashboard']['pages'] as $page) {
            $componentName = str_replace(' ', '', ucwords($page['name']));
            
            // Use the new recursive compiler here
            $elementsHtml = $this->compileElements($page['elements']);

            // Check if we need to import DataTable
            $hasDataTable = str_contains($elementsHtml, '<DataTable');

            $script = "<script setup>\n";
            $script .= "import MainLayout from '@/Layouts/MainLayout.vue';\n";
            if ($hasDataTable) $script .= "import DataTable from '@/Components/DataTable.vue';\n";
            $script .= "</script>";

            $vueContent = "
    <template>
        <MainLayout title=\"{$page['name']}\">
            <div class='space-y-6'>
                {$elementsHtml}
            </div>
        </MainLayout>
    </template>

    {$script}";

            File::put("{$pagesDir}/{$componentName}.vue", $vueContent);
        }
    }

    private function compileElements(array $elements): string
    {
        $html = "";

        foreach ($elements as $el) {
            $type = $el['type'];
            $props = $el['props'] ?? [];
            $children = $el['children'] ?? [];
            
            // Recursive Call: Process children first
            $childrenHtml = !empty($children) ? $this->compileElements($children) : "";

            $html .= match ($type) {
                'container-v' => "<div class='flex flex-col' style='{$this->formatStyle($props['style'] ?? [])}'>{$childrenHtml}</div>",
                
                'container-h' => "<div class='flex flex-row' style='{$this->formatStyle($props['style'] ?? [])}'>{$childrenHtml}</div>",
                
                'text' => "<h1 class='text-2xl font-bold mb-4' style='{$this->formatStyle($props['style'] ?? [])}'>" . ($props['content'] ?? '') . "</h1>",
                
                'data-table' => sprintf(
                    "<div class='mb-8'><DataTable title='%s' endpoint='/api/data/%s' :columns=\"%s\" style='%s' /></div>",
                    strtoupper($props['source_table'] ?? 'Data'),
                    $props['source_table'] ?? '',
                    str_replace('"', "'", json_encode($this->mapColumns($props['columns'] ?? []))),
                    $this->formatStyle($props['style'] ?? [])
                ),
                
                default => "<div>{$childrenHtml}</div>",
            };
        }

        return $html;
    }

    private function formatStyle(array $style): string
    {
        return collect($style)->map(fn($v, $k) => "$k: $v")->implode('; ');
    }

    private function mapColumns(array $cols): array
    {
        return array_map(fn($c) => [
            'key' => $c, 
            'label' => strtoupper(str_replace('_', ' ', $c))
        ], $cols);
    }

    private function run(array $command, ?string $cwd = null): void
    {
        $composerHome = storage_path('app/composer');
        File::ensureDirectoryExists($composerHome);

        $process = (new Process($command, $cwd))->setTimeout(1200);

        $process->setEnv(array_merge($_ENV, $_SERVER, [
            'PATH' => dirname(PHP_BINARY) . ';' . getenv('PATH'),
            'SYSTEMROOT' => getenv('SYSTEMROOT'),
            'COMPOSER_HOME' => $composerHome,
            'APPDATA' => $composerHome,
            'TMP' => storage_path('framework/cache'),
            'COMPOSER_NO_AUDIT' => '1',
            'COMPOSER_NO_SECURITY_BLOCKING' => '1'
        ]));

        $process->run(fn($t, $o) => \Log::info($o));

        if (!$process->isSuccessful()) {
            throw new \Exception(
                "Process Error:\n" .
                $process->getOutput() .
                "\n" .
                $process->getErrorOutput()
            );
        }
    }
}