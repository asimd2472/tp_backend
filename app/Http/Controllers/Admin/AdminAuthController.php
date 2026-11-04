<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check() && Auth::user()->isAdmin()) {
            $landingUrl = $this->adminLandingUrl(Auth::user());

            abort_if($landingUrl === null, 403, 'No admin menu permissions have been assigned to this account.');

            return redirect()->to($landingUrl);
        }

        return view('admin.login');
    }

    public function store(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $throttleKey = Str::lower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return response()->json([
                'message' => 'Too many sign-in attempts. Please try again in '.RateLimiter::availableIn($throttleKey).' seconds.',
            ], 429);
        }

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password) || ! $user->isAdmin()) {
            RateLimiter::hit($throttleKey, 60);

            return response()->json([
                'message' => 'These credentials could not be verified for an administrator account.',
            ], 422);
        }

        $landingUrl = $this->adminLandingUrl($user);

        if ($landingUrl === null) {
            return response()->json([
                'message' => 'No admin menu permissions have been assigned to this account.',
            ], 403);
        }

        Auth::login($user, $request->boolean('remember'));

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Signed in successfully.',
            'redirect' => $landingUrl,
        ]);
    }

    private function adminLandingUrl(User $user): ?string
    {
        $menuRoutes = [
            'view_dashboard' => 'admin.dashboard',
            'manage_roles' => 'admin.roles.index',
            'manage_products' => 'admin.products.index',
            'manage_french_door' => 'admin.products.french-door.index',
            'manage_windows' => 'admin.windows.index',
            'manage_blog' => 'admin.blog.index',
            'manage_dealers' => 'admin.dealers.index',
        ];

        foreach ($menuRoutes as $permission => $route) {
            if ($user->can($permission)) {
                return route($route);
            }
        }

        return null;
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
