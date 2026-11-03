<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dealer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
use RuntimeException;

class DealerController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate($this->filterRules());
        $dealers = Dealer::query()
            ->filter($filters)
            ->orderBy('dealer_name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.dealers.index', compact('dealers', 'filters'));
    }

    public function create(): View
    {
        return view('admin.dealers.form', [
            'dealer' => new Dealer,
            'isEdit' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->dealerRules());
        $image = $this->storeImage($request->file('image'));

        try {
            $dealer = Dealer::create($this->dealerData($validated, $image));
        } catch (\Throwable $exception) {
            if ($image) {
                Storage::disk('public')->delete($image);
            }

            throw $exception;
        }

        return redirect()->route('admin.dealers.edit', $dealer)->with('success', 'Dealer created successfully.');
    }

    public function edit(Dealer $dealer): View
    {
        return view('admin.dealers.form', [
            'dealer' => $dealer,
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, Dealer $dealer): RedirectResponse
    {
        $validated = $request->validate($this->dealerRules());
        $newImage = $this->storeImage($request->file('image'));
        $previousImage = $dealer->image;

        try {
            $dealer->update($this->dealerData($validated, $newImage ?? $previousImage));
        } catch (\Throwable $exception) {
            if ($newImage) {
                Storage::disk('public')->delete($newImage);
            }

            throw $exception;
        }

        if ($newImage && $previousImage) {
            Storage::disk('public')->delete($previousImage);
        }

        return redirect()->route('admin.dealers.edit', $dealer)->with('success', 'Dealer updated successfully.');
    }

    public function destroy(Dealer $dealer): RedirectResponse
    {
        $image = $dealer->image;
        $dealer->delete();

        if ($image) {
            Storage::disk('public')->delete($image);
        }

        return redirect()->route('admin.dealers.index')->with('success', 'Dealer deleted successfully.');
    }

    private function dealerRules(): array
    {
        return [
            'state' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'dealer_name' => ['required', 'string', 'max:255'],
            'dealer_address' => ['nullable', 'string', 'max:10000'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'mobile_number' => ['nullable', 'string', 'max:50'],
            'email_id' => ['nullable', 'email', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:20480'],
        ];
    }

    private function dealerData(array $validated, ?string $image): array
    {
        return [
            'state' => $validated['state'] ?? null,
            'district' => $validated['district'] ?? null,
            'dealer_name' => trim($validated['dealer_name']),
            'dealer_address' => $validated['dealer_address'] ?? null,
            'contact_person' => $validated['contact_person'] ?? null,
            'mobile_number' => $validated['mobile_number'] ?? null,
            'email_id' => $validated['email_id'] ?? null,
            'image' => $image,
        ];
    }

    private function storeImage(?UploadedFile $file): ?string
    {
        if ($file === null) {
            return null;
        }

        $path = $file->store('dealers', 'public');
        if ($path === false) {
            throw new RuntimeException('Unable to store the dealer image.');
        }

        return $path;
    }

    private function filterRules(): array
    {
        return [
            'state' => ['sometimes', 'nullable', 'string', 'max:255'],
            'district' => ['sometimes', 'nullable', 'string', 'max:255'],
            'dealer_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'dealer_address' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'contact_person' => ['sometimes', 'nullable', 'string', 'max:255'],
            'mobile_number' => ['sometimes', 'nullable', 'string', 'max:50'],
            'email_id' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
