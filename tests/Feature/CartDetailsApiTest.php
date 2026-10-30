<?php

namespace Tests\Feature;

use App\Mail\BookingDetailsMail;
use App\Models\CartDetail;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CartDetailsApiTest extends TestCase
{
    use RefreshDatabase;

    private const FRONTEND_ORIGIN = 'https://example.com';

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.frontend_origins' => [self::FRONTEND_ORIGIN]]);
    }

    public function test_authenticated_user_can_save_cart_details(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        $payload = [
            'cart_details' => [
                [
                    'id' => 'embossed-wood-finish-doors-3-17-321-8331',
                    'productSlug' => 'embossed-wood-finish-doors',
                    'productName' => 'Embossed Wood Finish Doors',
                    'modelName' => 'Classic',
                    'designName' => 'E01',
                    'shadeName' => 'Sun Teak',
                    'sizeLabel' => '100 × 2050 mm',
                    'image' => 'http://127.0.0.1:8000/uploads/default/model.webp',
                    'unitPrice' => null,
                    'quantity' => 3,
                ],
            ],
            'construction_stage' => 'Planning',
            'pincode' => '700100',
            'address' => 'gh dggd',
        ];

        $this->withHeader('Origin', self::FRONTEND_ORIGIN)
            ->actingAs($user, 'sanctum')
            ->postJson('/api/submit-cart-details', $payload)
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Cart details saved successfully.');

        $this->assertDatabaseCount('cart_details', 1);
        $saved = CartDetail::query()->firstOrFail();
        $this->assertSame($user->id, $saved->user_id);
        $this->assertSame($payload['cart_details'], $saved->cart_details);
        $this->assertSame('Planning', $saved->construction_stage);
        $this->assertSame('700100', $saved->pincode);
        $this->assertSame('gh dggd', $saved->address);

        Mail::assertSent(
            BookingDetailsMail::class,
            fn (BookingDetailsMail $mail): bool => $mail->hasTo($user->email)
                && $mail->bookingNumber === 'TP-000001',
        );
        $attachment = Mail::sent(BookingDetailsMail::class)->first()->attachments()[0];
        $this->assertSame('booking-details-TP-000001.pdf', $attachment->as);
        $this->assertSame('application/pdf', $attachment->mime);

        $pdfContents = $attachment->attachWith(
            fn (string $path): string => file_get_contents($path),
            fn (Closure $data): string => $data(),
        );
        $this->assertStringStartsWith('%PDF-', $pdfContents);
    }

    public function test_saving_cart_details_requires_authentication(): void
    {
        $this->withHeader('Origin', self::FRONTEND_ORIGIN)
            ->postJson('/api/submit-cart-details', [])
            ->assertUnauthorized();

        $this->assertDatabaseCount('cart_details', 0);
    }

    public function test_cart_details_payload_is_validated(): void
    {
        $user = User::factory()->create();

        $this->withHeader('Origin', self::FRONTEND_ORIGIN)
            ->actingAs($user, 'sanctum')
            ->postJson('/api/submit-cart-details', [
                'cart_details' => [],
                'construction_stage' => 'Planning',
                'pincode' => '700100',
                'address' => 'gh dggd',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cart_details']);

        $this->assertDatabaseCount('cart_details', 0);
    }
}
