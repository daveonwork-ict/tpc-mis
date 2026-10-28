<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Nwidart\Modules\Facades\Module;

class ModuleManageController extends Controller
{
    /**
     * Display a listing of all modules
     */
    public function index()
    {
        $moduleData = [];

        // Read all modules from all_modules.json
        $allModulesPath = base_path('all_modules.json');

        if (!File::exists($allModulesPath)) {
            return view('backend.modules.index', compact('moduleData'));
        }

        $allModules = json_decode(File::get($allModulesPath), true);

        if (!$allModules) {
            return view('backend.modules.index', compact('moduleData'));
        }

        // Read module statuses from modules_statuses.json
        $modulesStatusPath = base_path('modules_statuses.json');
        $modulesStatus = [];

        if (File::exists($modulesStatusPath)) {
            $modulesStatus = json_decode(File::get($modulesStatusPath), true) ?? [];
        }

        // Combine module info with status
        foreach ($allModules as $moduleInfo) {
            $moduleName = $moduleInfo['name'];
            $isEnabled = $modulesStatus[$moduleName] ?? false;

            // Check if module directory exists (means it's purchased/installed)
            $modulePath = base_path('Modules/' . $moduleName);
            $isPurchased = File::isDirectory($modulePath);

            $moduleData[] = [
                'name' => $moduleName,
                'alias' => $moduleInfo['alias'] ?? strtolower($moduleName),
                'description' => $moduleInfo['description'] ?? 'No description available',
                'is_default' => $moduleInfo['default'] ?? false,
                'thumbnail' => $moduleInfo['thumbnail'] ?? 'default-module.png',
                'version' => $moduleInfo['version'] ?? '1.0.0',
                'status' => $isEnabled ? 'active' : 'inactive',
                'is_enabled' => $isEnabled,
                'published_date' => $moduleInfo['published_date'] ?? 'January 14th, 2025',
                'verified' => $moduleInfo['verified'] ?? true,
                'purchased' => $isPurchased, // Based on directory existence
                'purchase_link' => $moduleInfo['purchase_link'] ?? '#',
            ];
        }

        return view('backend.modules.index', compact('moduleData'));
    }

    /**
     * Enable a module
     */
    public function enable($moduleName)
    {
        try {
            // Check if module is default
            $module = Module::find($moduleName);
            $modulePath = $module->getPath();
            $moduleJsonPath = $modulePath . '/module.json';

            if (File::exists($moduleJsonPath)) {
                $moduleInfo = json_decode(File::get($moduleJsonPath), true);
                if (isset($moduleInfo['default']) && $moduleInfo['default'] === true) {
                    return redirect()->route('modules.index')->with('danger', "Cannot enable $moduleName. This is a default module.");
                }
            }

            Module::enable($moduleName);

            // Update modules_statuses.json
            $this->updateModuleStatus($moduleName, true);

            return redirect()->route('modules.index')->with('success', "$moduleName module enabled successfully.");
        } catch (\Exception $e) {
            return redirect()->route('modules.index')->with('danger', "Failed to enable $moduleName module.");
        }
    }

    /**
     * Disable a module
     */
    public function disable($moduleName)
    {
        try {
            // Check if module is default
            $module = Module::find($moduleName);
            $modulePath = $module->getPath();
            $moduleJsonPath = $modulePath . '/module.json';

            if (File::exists($moduleJsonPath)) {
                $moduleInfo = json_decode(File::get($moduleJsonPath), true);
                if (isset($moduleInfo['default']) && $moduleInfo['default'] === true) {
                    return redirect()->route('modules.index')->with('danger', "Cannot disable $moduleName. This is a default module.");
                }
            }

            Module::disable($moduleName);

            // Update modules_statuses.json
            $this->updateModuleStatus($moduleName, false);

            return redirect()->route('modules.index')->with('success', "$moduleName module disabled successfully.");
        } catch (\Exception $e) {
            return redirect()->route('modules.index')->with('danger', "Failed to disable $moduleName module.");
        }
    }

    /**
     * Toggle module status
     */
    public function toggleStatus($moduleName)
    {
        try {
            // Check if module directory exists
            $modulePath = base_path('Modules/' . $moduleName);
            if (!File::isDirectory($modulePath)) {
                return response()->json([
                    'success' => false,
                    'message' => "Module directory not found. Please install the module first."
                ], 404);
            }

            $module = Module::find($moduleName);
            if (!$module) {
                return response()->json([
                    'success' => false,
                    'message' => "Module not found."
                ], 404);
            }

            $moduleJsonPath = $modulePath . '/module.json';

            // Check if module is default
            if (File::exists($moduleJsonPath)) {
                $moduleInfo = json_decode(File::get($moduleJsonPath), true);
                if (isset($moduleInfo['default']) && $moduleInfo['default'] === true) {
                    return response()->json([
                        'success' => false,
                        'message' => "Cannot toggle $moduleName. This is a default module and cannot be disabled."
                    ], 403);
                }
            }

            $isCurrentlyEnabled = $module->isEnabled();

            if ($isCurrentlyEnabled) {
                // DISABLE MODULE
                Module::disable($moduleName);
                $status = 'disabled';
                $isEnabled = false;
                $message = "$moduleName module disabled successfully.";
            } else {
                // ENABLE MODULE

                // Check if module was previously enabled (to determine if we should seed)
                $wasPreviouslyEnabled = $this->wasModulePreviouslyEnabled($moduleName);

                // Enable the module
                \Artisan::call('module:enable', ['module' => $moduleName]);
                \Artisan::call('module:publish', ['module' => $moduleName]);

                // Run migrations
                $migrationResult = $this->runModuleMigrations($moduleName, $wasPreviouslyEnabled);

                // Run seeders only if module is being enabled for the first time
                if (!$wasPreviouslyEnabled) {
                    $this->runModuleSeeders($moduleName);
                    $message = "$moduleName module enabled successfully. Migrations and seeders executed.";
                } else {
                    if ($migrationResult['new_migrations'] > 0) {
                        $message = "$moduleName module enabled successfully. {$migrationResult['new_migrations']} new migration(s) executed.";
                    } else {
                        $message = "$moduleName module enabled successfully. No new migrations to run.";
                    }
                }

                $status = 'enabled';
                $isEnabled = true;
            }

            // Update modules_statuses.json
            $this->updateModuleStatus($moduleName, $isEnabled);

            return response()->json([
                'success' => true,
                'message' => $message,
                'status' => $isEnabled ? 'active' : 'inactive'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Failed to toggle $moduleName module: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update module status in modules_statuses.json
     */
    private function updateModuleStatus($moduleName, $isEnabled)
    {
        $modulesStatusPath = base_path('modules_statuses.json');

        if (File::exists($modulesStatusPath)) {
            $modulesStatus = json_decode(File::get($modulesStatusPath), true);
            $modulesStatus[$moduleName] = $isEnabled;
            File::put($modulesStatusPath, json_encode($modulesStatus, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }

    /**
     * Upload and install a new module
     */
    public function upload(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'purchase_code' => 'required|string|min:24|max:255',
                'module_zip' => 'required|file|mimes:zip|max:102400', // Max 100MB
            ]);

            $purchaseCode = $request->input('purchase_code');
            $zipFile = $request->file('module_zip');

            // Get module name from ZIP filename (e.g., Forums.zip -> Forums)
            $zipFileName = pathinfo($zipFile->getClientOriginalName(), PATHINFO_FILENAME);

            // Read all_modules.json to validate module name
            $allModulesPath = base_path('all_modules.json');
            if (!File::exists($allModulesPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Module catalog not found'
                ], 404);
            }

            $allModules = json_decode(File::get($allModulesPath), true);
            $moduleExists = false;
            $moduleIndex = null;

            foreach ($allModules as $index => $module) {
                if (strcasecmp($module['name'], $zipFileName) === 0) {
                    $moduleExists = true;
                    $moduleIndex = $index;
                    break;
                }
            }

            if (!$moduleExists) {
                return response()->json([
                    'success' => false,
                    'message' => "Invalid module. '{$zipFileName}' is not in the module catalog."
                ], 400);
            }

            // Create temporary directory for extraction
            $tempPath = storage_path('app/temp_module_' . time());
            File::makeDirectory($tempPath, 0755, true);

            // Extract ZIP file
            $zip = new \ZipArchive;
            if ($zip->open($zipFile->getRealPath()) === TRUE) {
                $zip->extractTo($tempPath);
                $zip->close();
            } else {
                File::deleteDirectory($tempPath);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to extract ZIP file'
                ], 500);
            }

            // Find the module directory in extracted files
            $extractedDirs = File::directories($tempPath);
            if (empty($extractedDirs)) {
                File::deleteDirectory($tempPath);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid module structure. No directory found in ZIP.'
                ], 400);
            }

            $extractedModulePath = $extractedDirs[0];
            $extractedModuleName = basename($extractedModulePath);

            // Validate module.json exists
            $moduleJsonPath = $extractedModulePath . '/module.json';
            if (!File::exists($moduleJsonPath)) {
                File::deleteDirectory($tempPath);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid module. module.json not found.'
                ], 400);
            }

            // Read module.json from extracted module
            $extractedModuleInfo = json_decode(File::get($moduleJsonPath), true);
            if (!$extractedModuleInfo) {
                File::deleteDirectory($tempPath);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid module.json format'
                ], 400);
            }

            // Validate module name matches
            if (strcasecmp($extractedModuleInfo['name'], $zipFileName) !== 0) {
                File::deleteDirectory($tempPath);
                return response()->json([
                    'success' => false,
                    'message' => "Module name mismatch. Expected '{$zipFileName}', found '{$extractedModuleInfo['name']}'"
                ], 400);
            }

            // Validate module structure - check required files and directories
            $validationErrors = $this->validateModuleStructure($extractedModulePath, $extractedModuleName);

            if (!empty($validationErrors)) {
                File::deleteDirectory($tempPath);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid module structure: ' . implode(', ', $validationErrors)
                ], 400);
            }

            // Move module to Modules directory
            $finalModulePath = base_path('Modules/' . $extractedModuleName);

            // Remove existing module if it exists
            if (File::exists($finalModulePath)) {
                File::deleteDirectory($finalModulePath);
            }

            File::moveDirectory($extractedModulePath, $finalModulePath);
            File::deleteDirectory($tempPath);

            // Update all_modules.json with new version info
            if (isset($extractedModuleInfo['version'])) {
                $allModules[$moduleIndex]['version'] = $extractedModuleInfo['version'];
            }
            if (isset($extractedModuleInfo['description'])) {
                $allModules[$moduleIndex]['description'] = $extractedModuleInfo['description'];
            }
            if (isset($extractedModuleInfo['thumbnail'])) {
                $allModules[$moduleIndex]['thumbnail'] = $extractedModuleInfo['thumbnail'];
            }

            File::put($allModulesPath, json_encode($allModules, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            // Update modules_statuses.json
            $modulesStatusPath = base_path('modules_statuses.json');
            $modulesStatus = [];

            if (File::exists($modulesStatusPath)) {
                $modulesStatus = json_decode(File::get($modulesStatusPath), true) ?? [];
            }

            // Add module to status file if not exists (disabled by default)
            if (!isset($modulesStatus[$extractedModuleName])) {
                $modulesStatus[$extractedModuleName] = false;
            }

            File::put($modulesStatusPath, json_encode($modulesStatus, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return response()->json([
                'success' => true,
                'message' => "{$extractedModuleName} module uploaded and installed successfully!"
            ]);
        } catch (\Exception $e) {
            // Clean up on error
            if (isset($tempPath) && File::exists($tempPath)) {
                File::deleteDirectory($tempPath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate module structure
     * Check for required files and directories
     */
    private function validateModuleStructure($modulePath, $moduleName)
    {
        $errors = [];

        // Required files
        $requiredFiles = [
            'module.json' => 'module.json',
            'ServiceProvider' => "Providers/{$moduleName}ServiceProvider.php",
            'Config' => 'Config/config.php',
        ];

        // Required directories
        $requiredDirectories = [
            'Providers' => 'Providers',
            'Config' => 'Config',
            'Database' => 'Database',
            'Database/Migrations' => 'Database/Migrations',
        ];

        // Check required files
        foreach ($requiredFiles as $name => $file) {
            $filePath = $modulePath . '/' . $file;
            if (!File::exists($filePath)) {
                $errors[] = "Missing required file: {$file}";
            } else {
                // Additional validation for specific files
                if ($name === 'ServiceProvider') {
                    // Check if ServiceProvider file contains the class
                    $content = File::get($filePath);
                    if (strpos($content, "class {$moduleName}ServiceProvider") === false) {
                        $errors[] = "ServiceProvider class not found in {$file}";
                    }
                }

                if ($name === 'Config') {
                    // Check if config.php returns an array
                    $content = File::get($filePath);
                    if (strpos($content, 'return [') === false && strpos($content, 'return array(') === false) {
                        $errors[] = "Config file must return an array";
                    }
                }
            }
        }

        // Check required directories
        foreach ($requiredDirectories as $name => $directory) {
            $dirPath = $modulePath . '/' . $directory;
            if (!File::isDirectory($dirPath)) {
                $errors[] = "Missing required directory: {$directory}";
            }
        }

        // Check if Database/Migrations directory has at least one migration file (optional warning)
        $migrationsPath = $modulePath . '/Database/Migrations';
        if (File::isDirectory($migrationsPath)) {
            $migrations = File::files($migrationsPath);
            if (empty($migrations)) {
                // This is just a warning, not an error
                // $errors[] = "No migration files found in Database/Migrations";
            }
        }

        return $errors;
    }

    /**
     * Check if module was previously enabled
     * This helps determine if we should run seeders
     */
    private function wasModulePreviouslyEnabled($moduleName)
    {
        // Check if any migration from this module exists in the migrations table
        try {
            $migrationsPath = base_path("Modules/{$moduleName}/Database/Migrations");

            if (!File::isDirectory($migrationsPath)) {
                return false;
            }

            // Get all migration files from the module
            $migrationFiles = File::files($migrationsPath);

            if (empty($migrationFiles)) {
                return false;
            }

            // Check if any of these migrations exist in the database
            foreach ($migrationFiles as $file) {
                $migrationName = pathinfo($file->getFilename(), PATHINFO_FILENAME);

                $exists = DB::table('migrations')
                    ->where('migration', $migrationName)
                    ->exists();

                if ($exists) {
                    return true; // At least one migration was run before
                }
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Run module migrations
     * Only runs new migrations that haven't been executed yet
     */
    private function runModuleMigrations($moduleName, $wasPreviouslyEnabled)
    {
        $result = [
            'new_migrations' => 0,
            'skipped_migrations' => 0
        ];

        try {
            $migrationsPath = base_path("Modules/{$moduleName}/Database/Migrations");

            if (!File::isDirectory($migrationsPath)) {
                return $result;
            }

            // Get all migration files
            $migrationFiles = File::files($migrationsPath);

            if (empty($migrationFiles)) {
                return $result;
            }

            // Get list of already run migrations
            $ranMigrations = DB::table('migrations')
                ->pluck('migration')
                ->toArray();

            foreach ($migrationFiles as $file) {
                $migrationName = pathinfo($file->getFilename(), PATHINFO_FILENAME);

                if (in_array($migrationName, $ranMigrations)) {
                    $result['skipped_migrations']++;
                } else {
                    $result['new_migrations']++;
                }
            }

            // Run migrations for the specific module
            // This will automatically skip already-run migrations
            Artisan::call('module:migrate', ['module' => $moduleName]);

            return $result;
        } catch (\Exception $e) {
            \Log::error("Migration error for {$moduleName}: " . $e->getMessage());
            return $result;
        }
    }

    /**
     * Run module seeders
     * Only called for first-time module enablement
     */
    private function runModuleSeeders($moduleName)
    {
        try {
            // Check if seeder exists
            $seederPath = base_path("Modules/{$moduleName}/Database/Seeders");

            if (!File::isDirectory($seederPath)) {
                return;
            }

            // Run module seed command
            Artisan::call('module:seed', ['module' => $moduleName]);
        } catch (\Exception $e) {
            \Log::error("Seeder error for {$moduleName}: " . $e->getMessage());
        }
    }
}
