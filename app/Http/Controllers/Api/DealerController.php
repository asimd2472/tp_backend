<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dealer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'state' => ['sometimes', 'nullable', 'string', 'max:255'],
            'district' => ['sometimes', 'nullable', 'string', 'max:255'],
            'dealer_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'dealer_address' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'contact_person' => ['sometimes', 'nullable', 'string', 'max:255'],
            'mobile_number' => ['sometimes', 'nullable', 'string', 'max:50'],
            'email_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $dealers = Dealer::query()
            ->filter($validated)
            ->orderBy('dealer_name')
            ->orderBy('id')
            ->paginate((int) ($validated['per_page'] ?? 15))
            ->withQueryString();

        return response()->json([
            'success' => true,
            'data' => $dealers->getCollection()->map(fn (Dealer $dealer): array => [
                'id' => $dealer->id,
                'state' => $dealer->state,
                'district' => $dealer->district,
                'dealer_name' => $dealer->dealer_name,
                'dealer_address' => $dealer->dealer_address,
                'contact_person' => $dealer->contact_person,
                'mobile_number' => $dealer->mobile_number,
                'email_id' => $dealer->email_id,
                'image' => $dealer->image,
                'image_url' => $dealer->image_url,
            ])->values(),
            'pagination' => [
                'current_page' => $dealers->currentPage(),
                'per_page' => $dealers->perPage(),
                'total' => $dealers->total(),
                'last_page' => $dealers->lastPage(),
                'from' => $dealers->firstItem(),
                'to' => $dealers->lastItem(),
                'next_page_url' => $dealers->nextPageUrl(),
                'prev_page_url' => $dealers->previousPageUrl(),
            ],
        ]);
    }
}
