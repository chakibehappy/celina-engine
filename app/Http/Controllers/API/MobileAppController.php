<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\App\AppUser;
use App\Models\App\AppScreen;
use App\Models\App\AppNavigation;
use App\Models\App\SystemIcon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MobileAppController extends Controller
{
    /**
     * Authenticate AppUser and return token + app branding + unique app_id.
     */
   public function login(Request $request)
    {
        try {
            // 1. Manual Validation to ensure consistent JSON even on failure
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

            // 2. Find User
            $user = AppUser::with('app')->where('email', $request->email)->first();

            // 3. Check Credentials
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials.',
                    'errors'  => ['email' => ['Invalid email or password.']]
                ], 401);
            }

            // 4. Generate Token
            $token = $user->createToken($request->device_name)->plainTextToken;

            // 5. Success Response (The Perfect Envelope)
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
    /**
     * Fetch navigation tabs scoped strictly to the user's assigned App.
     */
    public function getNavigation(Request $request)
    {
        try {
            $user = $request->user();
            $platform = $request->header('X-Platform', 'kotlin-mobile');
            
            $navs = AppNavigation::with(['screen', 'icon']) 
                ->where('app_id', $user->app_id)
                ->orderBy('order')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Navigation loaded',
                'data'    => $navs->map(fn($nav) => [
                    'label'        => $nav->label,
                    'icon'         => $this->resolveIcon($nav->icon, $platform),
                    'route'        => $nav->screen->route,
                    'icon_size'    => (string)$nav->icon_size, // Cast to string for Map<String, String>
                    'font_size'    => (string)$nav->font_size,
                    'show_label'   => $nav->show_label ? 'true' : 'false',
                    'type'         => $nav->screen->type,
                    'content_data' => $nav->screen->type === 'custom' ? $nav->screen->content_data : ''
                ])
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch detailed screen data (Menus & Sub-modules) scoped to user's App.
     */
    public function getScreenData(Request $request, $route)
    {
        $user = $request->user();
        $platform = $request->header('X-Platform', 'flutter');

        // Maintained your deep eager loading to prevent N+1 performance issues
        $screen = AppScreen::with(['menus.icon', 'menus.subModules.icon'])
            ->where('app_id', $user->app_id)
            ->where('route', $route)
            ->first();

        if (!$screen) {
            return response()->json(['error' => 'Unauthorized or Not Found'], 403);
        }

        return response()->json([
            'success'      => true,
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
        ]);
    }

    /**
     * Maintained your optimized helper for platform-specific icons.
     */
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
}