<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $cartDetail = $request->user()->cartDetails()->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cart details saved successfully.',
            'data' => [
                'id' => $cartDetail->id,
            ],
        ], 201);
    }
}
