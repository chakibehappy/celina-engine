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
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required',
        ]);

        // Eager load the app relationship
        $user = AppUser::with('app')->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        $token = $user->createToken($request->device_name)->plainTextToken;

        // Consistent CelinaResponse structure
        return response()->json([
            'success' => true,
            'message' => 'Handshake Successful', // Optional: Good for debugging
            'data' => [
                'token'   => $token,
                'app_context' => [
                    'app_id'   => $user->app_id,
                    'app_name' => $user->app->name,
                    'slug'     => $user->app->slug,
                ],
                'user' => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                ]
            ]
        ]);
    }

    /**
     * Fetch navigation tabs scoped strictly to the user's assigned App.
     */
    public function getNavigation(Request $request)
    {
        $user = $request->user();
        $platform = $request->header('X-Platform', 'flutter');
        
        $navs = AppNavigation::with(['screen', 'icon']) 
            ->where('app_id', $user->app_id) // Filter by the specific app context
            ->orderBy('order')
            ->get();

        return response()->json([
            'app_id' => $user->app_id, // Metadata for mobile verification
            'data'   => $navs->map(fn($nav) => [
                'label'        => $nav->label,
                'icon'         => $this->resolveIcon($nav->icon, $platform),
                'route'        => $nav->screen->route,
                'icon_size'    => (float)$nav->icon_size,
                'font_size'    => (float)$nav->font_size,
                'show_label'   => (bool)$nav->show_label,
                'type'         => $nav->screen->type,
                'content_data' => $nav->screen->type === 'custom' ? $nav->screen->content_data : ''
            ])
        ]);
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