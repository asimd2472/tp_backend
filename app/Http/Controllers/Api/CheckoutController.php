<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\BookingDetailsMail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cart_details' => ['required', 'array', 'min:1'],
            'cart_details.*.id' => ['required', 'string', 'max:255'],
            'cart_details.*.productSlug' => ['required', 'string', 'max:255'],
            'cart_details.*.productName' => ['required', 'string', 'max:255'],
            'cart_details.*.modelName' => ['required', 'string', 'max:255'],
            'cart_details.*.designName' => ['required', 'string', 'max:255'],
            'cart_details.*.shadeName' => ['required', 'string', 'max:255'],
            'cart_details.*.sizeLabel' => ['required', 'string', 'max:255'],
            'cart_details.*.image' => ['required', 'string', 'max:2048'],
            'cart_details.*.unitPrice' => ['nullable', 'numeric', 'min:0'],
            'cart_details.*.quantity' => ['required', 'integer', 'min:1'],
            'construction_stage' => ['required', 'string', 'max:100'],
            'pincode' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:2000'],
        ]);

        $user = $request->user();
        $cartDetail = $user->cartDetails()->create($validated);
        $bookingNumber = 'TP-'.str_pad((string) $cartDetail->id, 6, '0', STR_PAD_LEFT);
        $products = array_map(fn (array $product): array => [
            ...$product,
            'type' => Str::contains(Str::lower($product['productSlug']), 'window') ? 'Window' : 'Door',
            'image_data_uri' => $this->productImageDataUri($product['image']),
        ], $validated['cart_details']);
        $pdfContents = Pdf::loadView('pdf.booking-details', [
            'bookingNumber' => $bookingNumber,
            'submittedAt' => $cartDetail->created_at,
            'user' => $user,
            'cartDetail' => $cartDetail,
            'products' => $products,
        ])->setPaper('a4')->output();

        Mail::to($user->email)->send(new BookingDetailsMail($pdfContents, $bookingNumber));

        return response()->json([
            'success' => true,
            'message' => 'Cart details saved successfully.',
            'data' => [
                'id' => $cartDetail->id,
            ],
        ], 201);
    }

    private function productImageDataUri(string $imageUrl): ?string
    {
        $imagePath = parse_url($imageUrl, PHP_URL_PATH);

        if (! is_string($imagePath) || ! Str::startsWith($imagePath, '/uploads/')) {
            return null;
        }

        $uploadsRoot = realpath(public_path('uploads'));
        $resolvedPath = realpath(public_path(ltrim($imagePath, '/')));

        if (
            $uploadsRoot === false
            || $resolvedPath === false
            || ! str_starts_with(
                strtolower($resolvedPath),
                strtolower($uploadsRoot.DIRECTORY_SEPARATOR),
            )
            || ! in_array(strtolower(pathinfo($resolvedPath, PATHINFO_EXTENSION)), ['gif', 'jpeg', 'jpg', 'png', 'webp'], true)
        ) {
            return null;
        }

        $fileSize = filesize($resolvedPath);
        $imageSize = @getimagesize($resolvedPath);

        if (
            $fileSize === false
            || $fileSize > 15 * 1024 * 1024
            || $imageSize === false
            || $imageSize[0] > 10000
            || $imageSize[1] > 10000
            || $imageSize[0] * $imageSize[1] > 6_000_000
        ) {
            return null;
        }

        $sourceBytes = file_get_contents($resolvedPath);

        if ($sourceBytes === false) {
            return null;
        }

        $source = @imagecreatefromstring($sourceBytes);

        if ($source === false) {
            return null;
        }

        $scale = min(1, 240 / imagesx($source), 280 / imagesy($source));
        $width = max(1, (int) round(imagesx($source) * $scale));
        $height = max(1, (int) round(imagesy($source) * $scale));
        $thumbnail = imagecreatetruecolor($width, $height);
        imagefill($thumbnail, 0, 0, imagecolorallocate($thumbnail, 255, 255, 255));
        imagecopyresampled(
            $thumbnail,
            $source,
            0,
            0,
            0,
            0,
            $width,
            $height,
            imagesx($source),
            imagesy($source),
        );

        ob_start();
        $encoded = imagejpeg($thumbnail, null, 82);
        $jpeg = ob_get_clean();
        imagedestroy($source);
        imagedestroy($thumbnail);

        if (! $encoded || ! is_string($jpeg)) {
            return null;
        }

        return 'data:image/jpeg;base64,'.base64_encode($jpeg);
    }
}
