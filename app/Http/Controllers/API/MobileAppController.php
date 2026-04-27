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

use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

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
    // public function getNavigation(Request $request)
    // {
    //     try {
    //         $user = $request->user();
            
    //         $navs = AppNavigation::with(['screen', 'icon']) 
    //             ->where('app_id', $user->app_id)
    //             ->orderBy('order')
    //             ->get();
    //         // should be on test controller :
    //         //     return response()->json([
    //         //     [
    //         //         'label'      => 'Home',
    //         //         'icon'       => 'home',
    //         //         'route'      => 'home_screen',
    //         //         'icon_size'  => '24',
    //         //         'show_label' => 'false',
    //         //         'content_data' => '' 
    //         //     ],
    //         //     [
    //         //         'label'      => 'Chat',
    //         //         'icon'       => 'chat_bubble',
    //         //         'route'      => 'chat_screen',
    //         //         'icon_size'  => '24',
    //         //         'show_label' => 'false',
    //         //         'content_data' => ''
    //         //     ],
    //         //     [
    //         //         'label'      => 'Account',
    //         //         'icon'       => 'person',
    //         //         'route'      => 'account_screen',
    //         //         'icon_size'  => '24',
    //         //         'show_label' => 'false',
    //         //         'content_data' => ''
    //         //     ],
    //         //     [
    //         //         'label'      => 'Live',
    //         //         'icon'       => 'edit_note',
    //         //         'route'      => 'custom_webview_screen',
    //         //         'icon_size'  => '24',
    //         //         'show_label' => 'false',
    //         //         'content_data' => $html 
    //         //     ],
    //         // ]);
    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Navigation loaded',
    //             'data'    => $navs->map(fn($nav) => [
    //                 'label'        => $nav->label,
    //                 'icon'         => $this->resolveIcon($nav->icon, $platform),
    //                 'route'        => $nav->screen->route,
    //                 'icon_size'    => (string)$nav->icon_size, // Cast to string for Map<String, String>
    //                 'font_size'    => (string)$nav->font_size,
    //                 'show_label'   => $nav->show_label ? 'true' : 'false',
    //                 'type'         => $nav->screen->type,
    //                 'content_data' => $nav->screen->type === 'custom' ? $nav->screen->content_data : ''
    //             ])
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    //     }
    // }

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
                    'label'        => $nav->label,
                    'icon'         => $iconData['value'], // This sends "home", "person", etc.
                    'route'        => $nav->screen->route ?? 'home_screen',
                    'icon_size'    => (string)($nav->icon_size ?? '24'),
                    'show_label'   => $nav->show_label ? 'true' : 'false',
                    'content_data' => ($nav->screen && $nav->screen->type === 'custom') 
                                    ? $nav->screen->content_data 
                                    : ''
                ];
            });

            return response()->json($formattedNavs);

        } catch (\Exception $e) {
            // This is what caught the "Call to undefined relationship" error earlier
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
            'data'    => [ // <--- THE MISSING ENVELOPE
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

    public function googleLogin(Request $request) 
    {
        try {
            // 1. Verify the JWT from the Kotlin app
            $googleUser = Socialite::driver('google')->stateless()->userFromToken($request->token);

            // 2. Find or Create based ONLY on email for now
            $user = AppUser::with('app')->updateOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name'     => $googleUser->getName(),
                    'app_id'   => 1, // Defaulting to your main Celina App
                    'password' => Hash::make(Str::random(24)), 
                    'app_role_id' => 2,
                    // 'google_id' => $googleUser->getId(), // Commented out for later
                ]
            );

            // 3. Issue the Sanctum token
            $token = $user->createToken($request->device_name)->plainTextToken;

            // 4. Return the standard "Celina Envelope"
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
}