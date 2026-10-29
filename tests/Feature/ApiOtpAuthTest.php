<?php

namespace Tests\Feature;

use App\Mail\EmailOtpMail;
use App\Models\EmailOtpChallenge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ApiOtpAuthTest extends TestCase
{
    use RefreshDatabase;

    private const FRONTEND_ORIGIN = 'https://example.com';

    public function test_new_customer_verifies_email_before_completing_registration(): void
    {
        Mail::fake();

        $this->postWithFrontendOrigin('/api/login', [
            'email' => 'Customer@Example.com',
        ])->assertAccepted();

        $this->assertDatabaseMissing('users', ['email' => 'customer@example.com']);
        $challenge = EmailOtpChallenge::query()->where('email', 'customer@example.com')->firstOrFail();
        $otp = $this->sentOtp();
        $this->assertTrue(Hash::check($otp, $challenge->code_hash));

        $verification = $this->postWithFrontendOrigin('/api/verify-otp', [
            'email' => 'customer@example.com',
            'otp' => $otp,
        ])->assertOk()
            ->assertJsonPath('is_registered', false)
            ->assertJsonStructure(['registration_token']);

        $this->assertDatabaseMissing('users', ['email' => 'customer@example.com']);

        $response = $this->postWithFrontendOrigin('/api/register', [
            'email' => 'customer@example.com',
            'registration_token' => $verification->json('registration_token'),
            'name' => 'Customer One',
            'phone' => '+1 555 123 0001',
        ])->assertCreated()
            ->assertJsonPath('is_registered', true)
            ->assertJsonPath('user.name', 'Customer One')
            ->assertJsonPath('user.email', 'customer@example.com')
            ->assertJsonPath('user.phone', '+1 555 123 0001');

        $user = User::query()->where('email', 'customer@example.com')->firstOrFail();
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame('+1 555 123 0001', $user->phone);
        $this->assertDatabaseMissing('email_otp_challenges', ['id' => $challenge->id]);

        $token = $response->json('access_token');
        $this->withToken($token)->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);

        $this->app['auth']->forgetGuards();
        $this->withToken('invalid-token')->postJson('/api/auth/logout')
            ->assertUnauthorized();

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->postJson('/api/auth/logout')
            ->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_existing_customer_can_request_and_verify_a_login_otp(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'member@example.com']);

        $this->postWithFrontendOrigin('/api/login', ['email' => $user->email])
            ->assertAccepted();

        $response = $this->postWithFrontendOrigin('/api/verify-otp', [
            'email' => $user->email,
            'otp' => $this->sentOtp(),
        ])->assertOk()
            ->assertJsonPath('is_registered', true)
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonStructure(['access_token', 'token_type', 'user']);

        $this->withToken($response->json('access_token'))->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('user.email', $user->email);
    }

    public function test_email_submission_does_not_disclose_whether_an_account_exists(): void
    {
        Mail::fake();

        $this->postWithFrontendOrigin('/api/login', ['email' => 'missing@example.com'])
            ->assertAccepted();

        Mail::assertSent(EmailOtpMail::class, fn (EmailOtpMail $mail): bool => $mail->hasTo('missing@example.com'));
        $this->assertDatabaseHas('email_otp_challenges', [
            'email' => 'missing@example.com',
            'purpose' => 'login',
        ]);
    }

    public function test_resend_otp_can_send_a_code_without_a_previous_challenge(): void
    {
        Mail::fake();

        $this->postWithFrontendOrigin('/api/resend-otp', [
            'email' => 'new-customer@example.com',
        ])->assertAccepted()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.expires_in_seconds', 600)
            ->assertJsonPath('data.resend_after_seconds', 60);

        Mail::assertSent(EmailOtpMail::class, fn (EmailOtpMail $mail): bool => $mail->hasTo('new-customer@example.com'));
        $this->assertDatabaseHas('email_otp_challenges', [
            'email' => 'new-customer@example.com',
            'purpose' => 'login',
        ]);
    }

    public function test_otp_login_does_not_send_codes_to_administrator_accounts(): void
    {
        Mail::fake();
        $admin = User::factory()->admin()->create();

        $this->postWithFrontendOrigin('/api/login', ['email' => $admin->email])
            ->assertAccepted();
        $this->postWithFrontendOrigin('/api/resend-otp', ['email' => $admin->email])
            ->assertAccepted();

        Mail::assertNothingSent();
        $this->assertDatabaseCount('email_otp_challenges', 0);
    }

    public function test_otp_resend_is_throttled_and_failed_codes_are_limited(): void
    {
        Mail::fake();
        $this->postWithFrontendOrigin('/api/login', [
            'email' => 'customer2@example.com',
        ])->assertAccepted();

        $this->postWithFrontendOrigin('/api/resend-otp', [
            'email' => 'customer2@example.com',
        ])->assertTooManyRequests()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Please wait before requesting another verification code.')
            ->assertJsonPath('data.retry_after_seconds', 60);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postWithFrontendOrigin('/api/verify-otp', [
                'email' => 'customer2@example.com',
                'otp' => '000000',
            ])->assertUnprocessable();
        }

        $this->assertDatabaseMissing('email_otp_challenges', [
            'email' => 'customer2@example.com',
            'purpose' => 'login',
        ]);
    }

    public function test_email_submission_requires_an_allowed_frontend_origin(): void
    {
        $this->postJson('/api/login', [
            'email' => 'customer3@example.com',
        ])->assertForbidden();

        $this->assertDatabaseCount('email_otp_challenges', 0);
    }

    private function postWithFrontendOrigin(string $uri, array $data)
    {
        return $this->withHeader('Origin', self::FRONTEND_ORIGIN)->postJson($uri, $data);
    }

    private function sentOtp(): string
    {
        $otp = null;

        Mail::assertSent(EmailOtpMail::class, function (EmailOtpMail $mail) use (&$otp): bool {
            $otp = $mail->code;

            return true;
        });

        $this->assertNotNull($otp);

        return (string) $otp;
    }
}