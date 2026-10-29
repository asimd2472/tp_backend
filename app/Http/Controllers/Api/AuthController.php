<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\EmailOtpMail;
use App\Models\EmailOtpChallenge;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use Laravel\Sanctum\PersonalAccessToken;
use Throwable;

class AuthController extends Controller
{
    private const LOGIN = 'login';

    private const OTP_TTL_MINUTES = 10;

    private const OTP_RESEND_SECONDS = 60;

    private const OTP_MAX_ATTEMPTS = 5;

    private const API_TOKEN_TTL_DAYS = 30;

    public function register(Request $request): JsonResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
            'phone' => $request->input('phone', $request->input('phone_no')),
        ]);
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'registration_token' => ['required', 'string', 'max:4096'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
        ]);

        try {
            $verification = json_decode(
                Crypt::decryptString($validated['registration_token']),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (Throwable) {
            $verification = null;
        }

        if (! is_array($verification)
            || ($verification['email'] ?? null) !== $validated['email']
            || ! is_numeric($verification['expires_at'] ?? null)
            || (int) $verification['expires_at'] < now()->timestamp) {
            return response()->json([
                'message' => 'The email verification has expired. Request a new code and try again.',
            ], 422);
        }

        if (User::query()->whereRaw('LOWER(email) = ?', [$validated['email']])->exists()) {
            return response()->json(['message' => 'An account with this email already exists.'], 409);
        }

        $user = DB::transaction(function () use ($validated): User {
            $user = User::query()->create([
                'name' => trim($validated['name']),
                'email' => $validated['email'],
                'phone' => trim($validated['phone']),
                'password' => Str::random(64),
                'is_admin' => false,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            return $user;
        });

        return $this->tokenResponse($user, 'Account created successfully.', 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $this->validateEmail($request);

        return $this->issueOtp($validated['email']);
    }

    public function resendOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);
        return $this->issueOtp(Str::lower(trim($validated['email'])));
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'otp' => ['required', 'digits:6'],
        ]);
        $email = Str::lower(trim($validated['email']));

        $result = DB::transaction(function () use ($validated, $email): array {
            $challenge = EmailOtpChallenge::query()
                ->where('email', $email)
                ->where('purpose', self::LOGIN)
                ->lockForUpdate()
                ->first();

            if (! $challenge || $challenge->expires_at->isPast() || $challenge->attempts >= self::OTP_MAX_ATTEMPTS) {
                $challenge?->delete();

                return ['error' => 'The verification code is invalid or expired.'];
            }

            if (! Hash::check($validated['otp'], $challenge->code_hash)) {
                $challenge->increment('attempts');
                if ($challenge->attempts >= self::OTP_MAX_ATTEMPTS) {
                    $challenge->delete();
                }

                return ['error' => 'The verification code is invalid or expired.'];
            }

            $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
            if ($user?->isAdmin()) {
                $challenge->delete();

                return ['error' => 'The verification code is invalid or expired.'];
            }

            $challenge->delete();

            if (! $user) {
                return [
                    'is_registered' => false,
                    'registration_token' => $this->registrationToken($email),
                ];
            }

            if (! $user->email_verified_at) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            return [
                'is_registered' => true,
                'user' => $user,
                'token' => $user->createToken(
                    'customer-api',
                    ['*'],
                    now()->addDays(self::API_TOKEN_TTL_DAYS),
                )->plainTextToken,
            ];
        });

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], 422);
        }

        if (! $result['is_registered']) {
            return response()->json([
                'message' => 'Email verified. Continue with customer registration.',
                'is_registered' => false,
                'registration_token' => $result['registration_token'],
            ]);
        }

        return response()->json([
            'message' => 'Signed in successfully.',
            'is_registered' => true,
            'token_type' => 'Bearer',
            'access_token' => $result['token'],
            'user' => $this->userPayload($result['user']),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->userPayload($request->user())]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json(['message' => 'Signed out successfully.']);
    }

    private function validateEmail(Request $request): array
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        return $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);
    }

    private function issueOtp(string $email): JsonResponse
    {
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        if ($user?->isAdmin()) {
            return $this->acceptedResponse();
        }

        $challenge = EmailOtpChallenge::query()
            ->where('email', $email)
            ->where('purpose', self::LOGIN)
            ->first();

        if ($challenge && $challenge->last_sent_at->gt(now()->subSeconds(self::OTP_RESEND_SECONDS))) {
            return response()->json([
                'success' => false,
                'message' => 'Please wait before requesting another verification code.',
                'data' => [
                    'retry_after_seconds' => max(
                        1,
                        (int) ceil(self::OTP_RESEND_SECONDS - $challenge->last_sent_at->diffInSeconds(now())),
                    ),
                ],
            ], 429);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $challenge = EmailOtpChallenge::query()->updateOrCreate(
            ['email' => $email, 'purpose' => self::LOGIN],
            [
                'code_hash' => Hash::make($code),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
                'last_sent_at' => now(),
            ],
        );

        try {
            Mail::to($email)->send(new EmailOtpMail($code, self::LOGIN));
        } catch (Throwable $exception) {
            $challenge->delete();
            throw $exception;
        }

        return $this->acceptedResponse();
    }

    private function acceptedResponse(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'If this email can be used to sign in or register, a verification code has been sent. Check your inbox.',
            'data' => [
                'expires_in_seconds' => self::OTP_TTL_MINUTES * 60,
                'resend_after_seconds' => self::OTP_RESEND_SECONDS,
            ],
        ], 202);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'email_verified_at' => $user->email_verified_at?->toISOString(),
        ];
    }

    private function registrationToken(string $email): string
    {
        return Crypt::encryptString(json_encode([
            'email' => $email,
            'expires_at' => now()->addMinutes(15)->timestamp,
        ], JSON_THROW_ON_ERROR));
    }

    private function tokenResponse(User $user, string $message, int $status = 200): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'is_registered' => true,
            'token_type' => 'Bearer',
            'access_token' => $user->createToken(
                'customer-api',
                ['*'],
                now()->addDays(self::API_TOKEN_TTL_DAYS),
            )->plainTextToken,
            'user' => $this->userPayload($user),
        ], $status);
    }
}