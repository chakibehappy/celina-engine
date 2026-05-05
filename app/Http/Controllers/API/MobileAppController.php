<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\App\App;
use App\Models\App\AppUser;
use App\Models\App\AppScreen;
use App\Models\App\AppNavigation;
use App\Models\App\SystemIcon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class MobileAppController extends Controller
{
    public function getAppInfo(Request $request, $app_id)
    {
        $app = App::where('id', $app_id)
                ->orderBy('order')
                ->get();
        if (!$app){
            return response()->json([
                'success' => false,
                'message' => 'No App found',
                'errors'  => null
            ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'App data loaded',
            'data'    => $app
        ]);
    }



    /**
     * Authenticate AppUser and return token + app branding + unique app_id.
     */
   public function login(Request $request)
    {
        try {
            // Manual Validation to ensure consistent JSON even on failure
            $validator = \Validator::make($request->all(), [
                'email'       => 'required|email',
                'password'    => 'required',
                'device_name' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors()
                ], 422);
            }

            // Find User
            $user = AppUser::with('app')->where('email', $request->email)->first();

            // Check Credentials
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials.',
                    'errors'  => ['email' => ['Invalid email or password.']]
                ], 401);
            }

            // Generate Token
            $token = $user->createToken($request->device_name)->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Handshake Successful',
                'data' => [
                    'token'       => $token,
                    'app_context' => [
                        'app_id'   => $user->app_id,
                        'app_name' => $user->app->name ?? 'Default App',
                        'slug'     => $user->app->slug ?? 'default',
                    ],
                    'user' => [
                        'id'    => $user->id,
                        'name'  => $user->name,
                        'email' => $user->email,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            // Catch-all for database errors or logic crashes
            return response()->json([
                'success' => false,
                'message' => 'Server Error: ' . $e->getMessage(),
                'data'    => null
            ], 500);
        }
    }
    
    public function getNavigation(Request $request)
    {
        try {
            $user = $request->user();
            $platform = 'kotlin'; // Hardcoded for this endpoint/request

            $navs = AppNavigation::with(['screen', 'icon']) 
                ->where('app_id', $user->app_id)
                ->orderBy('order')
                ->get();

            $formattedNavs = $navs->map(function($nav) use ($platform) {
                // Use your existing resolveIcon helper
                $iconData = $this->resolveIcon($nav->icon, $platform);
                
                return [
                    'screen_id'    => (string)$nav->screen_id,
                    'label'        => $nav->label,
                    'icon'         => $iconData['value'], // This sends "home", "person", etc.
                    'route'        => $nav->screen->route ?? 'home_screen',
                    'icon_size'    => (string)($nav->icon_size ?? '24'),
                    'show_label'   => $nav->show_label ? 'true' : 'false',
                    'content_data' => ($nav->screen && $nav->screen->type === 'custom') 
                                    ? $nav->screen->content_data : ''
                ];
            });

            return response()->json($formattedNavs);

        } catch (\Exception $e) {
            return response()->json([
                [
                    'label' => 'Error', 
                    'icon' => 'warning', 
                    'route' => 'error', 
                    'content_data' => $e->getMessage()
                ]
            ], 500);
        }
    }

    public function getScreenContentData(Request $request, $screen_id)
    {
        try {
            $screen = AppScreen::where('id', $screen_id)
                ->where('app_id', $request->user()->app_id)
                ->first();

            if (!$screen) {
                return response()->json(['error' => 'Screen not found'], 404);
            }
            return response()->json($screen->content_data);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch detailed screen data (Menus & Sub-modules) scoped to user's App.
     */
    public function getScreenData(Request $request, $route)
    {
        $user = $request->user();

        $screen = AppScreen::with(['menus.icon', 'menus.subModules.icon'])
            ->where('app_id', $user->app_id)
            ->where('route', $route)
            ->first();

        if (!$screen) {
            return response()->json([
                'success' => false, 
                'message' => 'Unauthorized or Not Found'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Screen data loaded',
            'data'    => [ 
                'app_id'       => $user->app_id,
                'title'        => $screen->title,
                'type'         => $screen->type,
                'content_data' => $screen->content_data,
                'menus'        => $screen->menus->map(fn($menu) => [
                    'label' => $menu->label,
                    'icon'  => $this->resolveIcon($menu->icon, $platform),
                    'sub_modules' => $menu->subModules->map(fn($sub) => [
                        'label' => $sub->label,
                        'desc'  => $sub->description,
                        'icon'  => $this->resolveIcon($sub->icon, $platform),
                        'count' => $sub->count,
                        'table' => $sub->table_name,
                        'color' => $sub->color
                    ])
                ])
            ]
        ]);
    }

    private function resolveIcon($icon, $platform = 'flutter')
    {
        if (!$icon) {
            return ['type' => 'system', 'value' => 'help'];
        }

        $nativeValue = match($platform) {
            'expo'   => $icon->expo_value,
            'kotlin' => $icon->kotlin_value,
            'flutter'=> $icon->flutter_value,
            default  => null,
        };

        if ($icon->url && !$nativeValue) {
            return [
                'type'  => 'custom',
                'value' => $icon->url
            ];
        }

        return [
            'type'  => 'system',
            'value' => $nativeValue ?? 'help'
        ];
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true]);
    }

    public function googleLogin(Request $request) 
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->userFromToken($request->token);
            $user = AppUser::with('app')->updateOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name'     => $googleUser->getName(),
                    'app_id'   => 2, // Defaulting to your main Celina App
                    'password' => Hash::make(Str::random(24)), 
                    'app_role_id' => 2,
                    // 'google_id' => $googleUser->getId(), // Commented out for later
                ]
            );

            $token = $user->createToken($request->device_name)->plainTextToken;
            // Return the standard "Celina Envelope"
            return response()->json([
                'success' => true,
                'message' => 'Google Handshake Successful',
                'data' => [
                    'token'       => $token,
                    'app_context' => [
                        'app_id'   => $user->app_id,
                        'app_name' => $user->app->name ?? 'Default App',
                        'slug'     => $user->app->slug ?? 'default',
                    ],
                    'user' => [
                        'id'    => $user->id,
                        'name'  => $user->name,
                        'email' => $user->email,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Google Auth Failed: ' . $e->getMessage(),
            ], 401);
        }
    }


    private function getTargetDatabase($appId)
    {
        return App::findOrFail($appId)->database_name;
    }

    public function createData(Request $request, $tableName)
    {
        try {
            $user = $request->user();
            if (!$user) return response()->json(['success' => false], 401);

            $appId = $user->app_id;
            $dbName = App::findOrFail($appId)->database_name;

            $data = $request->except(['id', 'created_at', 'updated_at']);

            // Handle File Upload
            if ($request->hasFile('image')) {
                $path = env('CELINA_UPLOAD_PATH', 'uploads/general');
                // Store file in 'public' disk so it's accessible via URL
                $file = $request->file('image');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $finalPath = $file->storeAs($path, $fileName, 'public');
                // Set the path to be saved in DB
                $data['image'] = asset('storage/' . $finalPath);
            }
            // Insert into dynamic DB
            DB::table("{$dbName}.{$tableName}")->insert(
                $data + [
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
            return response()->json(['success' => true, 'path' => $data['image'] ?? null]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function readData(Request $request, $tableName, $id = null)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }

            $appId = $user->app_id;
            $dbName = $this->getTargetDatabase($appId);

            if ($id) {
                $data = DB::table("{$dbName}.{$tableName}")->where('id', $id)->get();
                if ($data->isEmpty()) {
                    return response()->json(['success' => false, 'message' => 'Record not found'], 404);
                }
            } else {
                $data = DB::table("{$dbName}.{$tableName}")->orderBy('updated_at', 'desc')->get();
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateData(Request $request, $tableName, $id)
    {
        try {
            $user = $request->user();
            if (!$user) return response()->json(['success' => false], 401);

            $appId = $user->app_id;
            $dbName = App::findOrFail($appId)->database_name;

            // 1. Grab the existing record to check for old images
            $existingRecord = DB::table("{$dbName}.{$tableName}")->where('id', $id)->first();
            if (!$existingRecord) {
                return response()->json(['success' => false, 'message' => 'Record not found'], 404);
            }

            // 2. Prepare data, stripping internal/spoof fields
            $data = $request->except(['id', 'created_at', 'updated_at']);
            $data['updated_at'] = now();

            // 3. Handle New Image Upload
            if ($request->hasFile('image')) {
                // Delete old physical file if it exists
                if (!empty($existingRecord->image)) {
                    // Convert URL back to relative path for Storage::delete
                    $oldPath = str_replace(asset('storage/'), '', $existingRecord->image);
                    \Storage::disk('public')->delete($oldPath);
                }

                // Store the new one
                $path = env('CELINA_UPLOAD_PATH', 'uploads/general');
                $file = $request->file('image');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $finalPath = $file->storeAs($path, $fileName, 'public');
                
                $data['image'] = asset('storage/' . $finalPath);
            }

            // 4. Update the database
            $affected = DB::table("{$dbName}.{$tableName}")
                ->where('id', $id)
                ->update($data);

            return response()->json([
                'success' => true, 
                'message' => 'Data successfully updated',
                'image_url' => $data['image'] ?? null
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteData(Request $request, $tableName, $id)
    {
         try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }
            $appId = $user->app_id;
            $dbName = $this->getTargetDatabase($appId);
            DB::table("{$dbName}.{$tableName}")->where('id', $id)->delete();
            return response()->json(['success' => true]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Database or table not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}