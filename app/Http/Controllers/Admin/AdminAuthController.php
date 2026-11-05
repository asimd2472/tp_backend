<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

class AdminAuthController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (Auth::check() && Auth::user()->isAdmin()) {
            if (! Auth::user()->two_factor_confirmed_at || ! (bool) $request->session()->get('admin_two_factor_verified')) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return view('admin.login');
            }

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

        $request->session()->put([
            'admin_two_factor.user_id' => $user->id,
            'admin_two_factor.remember' => $request->boolean('remember'),
            'admin_two_factor.started_at' => now()->timestamp,
        ]);

        if (blank($user->two_factor_secret) || ! $user->two_factor_confirmed_at) {
            $secret = (new Google2FA)->generateSecretKey();
            $request->session()->put('admin_two_factor.secret', Crypt::encryptString($secret));

            return response()->json([
                'message' => 'Set up your authenticator app to continue.',
                'redirect' => route('admin.two-factor.setup'),
            ]);
        }

        return response()->json([
            'message' => 'Enter your authenticator code to continue.',
            'redirect' => route('admin.two-factor.challenge'),
        ]);
    }

    public function setup(Request $request): View
    {
        $user = $this->pendingTwoFactorUser($request);
        $encryptedSecret = $request->session()->get('admin_two_factor.secret');

        abort_unless(is_string($encryptedSecret) && $encryptedSecret !== '', 403);
        $secret = Crypt::decryptString($encryptedSecret);

        $google2fa = new Google2FA;
        $qrCode = (new Writer(new ImageRenderer(new RendererStyle(240, 2), new SvgImageBackEnd)))
            ->writeString($google2fa->getQRCodeUrl(config('app.name'), $user->email, $secret));

        return view('admin.login', [
            'authStep' => 'setup',
            'secret' => $secret,
            'qrCode' => 'data:image/svg+xml;base64,'.base64_encode($qrCode),
        ]);
    }

    public function confirmSetup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);
        $user = $this->pendingTwoFactorUser($request);
        $encryptedSecret = $request->session()->get('admin_two_factor.secret');
        $throttleKey = $this->twoFactorThrottleKey($request, $user);

        abort_unless(is_string($encryptedSecret) && $encryptedSecret !== '', 403);
        $secret = Crypt::decryptString($encryptedSecret);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return response()->json([
                'message' => 'Too many verification attempts. Please try again later.',
            ], 429);
        }

        $google2fa = new Google2FA;
        $step = $google2fa->verifyKey($secret, $validated['code'], 2, null, 0);

        if ($step === false) {
            RateLimiter::hit($throttleKey, 900);

            return response()->json([
                'message' => 'That authenticator code is invalid. Check the code and try again.',
            ], 422);
        }

        $recoveryCodes = array_map(
            fn (): string => 'RC-'.Str::upper(Str::random(10)),
            range(1, 8)
        );

        $user = DB::transaction(function () use ($user, $secret, $step, $recoveryCodes): User {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);

            abort_if($user->two_factor_confirmed_at, 409, 'Authenticator setup has already been completed.');

            $user->two_factor_secret = $secret;
            $user->two_factor_recovery_codes = array_map(
                fn (string $code): string => Hash::make($code),
                $recoveryCodes
            );
            $user->two_factor_last_used_step = $step;
            $user->two_factor_confirmed_at = now();
            $user->save();

            return $user;
        });

        return $this->completeTwoFactorLogin($request, $user, $recoveryCodes);
    }

    public function challenge(Request $request): View
    {
        $user = $this->pendingTwoFactorUser($request);

        abort_unless($user->two_factor_secret && $user->two_factor_confirmed_at, 403);

        return view('admin.login', ['authStep' => 'challenge']);
    }

    public function verifyChallenge(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'regex:/^(?:[0-9]{6}|RC-[A-Z0-9]{10})$/i'],
        ]);
        $user = $this->pendingTwoFactorUser($request);
        $throttleKey = $this->twoFactorThrottleKey($request, $user);

        abort_unless($user->two_factor_secret && $user->two_factor_confirmed_at, 403);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return response()->json([
                'message' => 'Too many verification attempts. Please try again later.',
            ], 429);
        }

        $code = strtoupper($validated['code']);
        $verified = ctype_digit($code)
            ? $this->verifyAndMarkTotp($user, $code)
            : $this->consumeRecoveryCode($user, $code);

        if (! $verified) {
            RateLimiter::hit($throttleKey, 900);

            return response()->json([
                'message' => 'That verification code is invalid. Check the code and try again.',
            ], 422);
        }

        return $this->completeTwoFactorLogin($request, $user);
    }

    private function verifyAndMarkTotp(User $user, string $code): bool
    {
        return DB::transaction(function () use ($user, $code): bool {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $step = (new Google2FA)->verifyKey(
                $lockedUser->two_factor_secret,
                $code,
                2,
                null,
                $lockedUser->two_factor_last_used_step ?? 0
            );

            if ($step === false) {
                return false;
            }

            $lockedUser->two_factor_last_used_step = $step;
            $lockedUser->save();

            return true;
        });
    }

    private function consumeRecoveryCode(User $user, string $code): bool
    {
        return DB::transaction(function () use ($user, $code): bool {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $recoveryCodes = $lockedUser->two_factor_recovery_codes ?? [];

            foreach ($recoveryCodes as $index => $hashedCode) {
                if (Hash::check($code, $hashedCode)) {
                    unset($recoveryCodes[$index]);
                    $lockedUser->two_factor_recovery_codes = array_values($recoveryCodes);
                    $lockedUser->save();

                    return true;
                }
            }

            return false;
        });
    }

    private function completeTwoFactorLogin(Request $request, User $user, array $recoveryCodes = []): JsonResponse
    {
        $landingUrl = $this->adminLandingUrl($user);

        abort_if($landingUrl === null, 403, 'No admin menu permissions have been assigned to this account.');

        $remember = (bool) $request->session()->get('admin_two_factor.remember', false);
        Auth::login($user, $remember);
        $request->session()->forget('admin_two_factor');
        $request->session()->put('admin_two_factor_verified', true);
        $request->session()->regenerate();

        RateLimiter::clear(Str::lower($user->email).'|'.$request->ip());
        RateLimiter::clear($this->twoFactorThrottleKey($request, $user));

        return response()->json([
            'message' => 'Signed in successfully.',
            'redirect' => $landingUrl,
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    private function pendingTwoFactorUser(Request $request): User
    {
        $userId = $request->session()->get('admin_two_factor.user_id');
        $user = $userId ? User::find($userId) : null;
        $startedAt = $request->session()->get('admin_two_factor.started_at');

        abort_unless($user?->isAdmin(), 403);

        if (! is_numeric($startedAt) || (int) $startedAt < now()->subMinutes(5)->timestamp) {
            $request->session()->forget('admin_two_factor');

            abort(403, 'Your sign-in attempt expired. Please sign in again.');
        }

        return $user;
    }

    private function twoFactorThrottleKey(Request $request, User $user): string
    {
        return 'admin-two-factor|'.$user->id.'|'.$request->ip();
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
