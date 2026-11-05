<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_name' => ['required', 'string', 'max:255'],
            'user_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'user_phone' => ['nullable', 'string', 'max:30'],
            'user_password' => ['required', 'string', 'min:8', 'confirmed'],
            'user_roles' => ['nullable', 'array'],
            'user_roles.*' => ['string', 'distinct', 'exists:roles,name'],
        ]);

        DB::transaction(function () use ($validated): void {
            $user = User::create([
                'name' => trim($validated['user_name']),
                'email' => trim($validated['user_email']),
                'phone' => $validated['user_phone'] ?? null,
                'password' => $validated['user_password'],
            ]);

            $user->syncRoles($validated['user_roles'] ?? []);
            $user->is_admin = $user->isAdmin();
            $user->save();
        });

        return redirect()->route('admin.roles.index')->withFragment('user-access')
            ->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'email' => trim((string) $request->input('email')),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        return redirect()->route('admin.roles.index')->withFragment('user-access')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->is(auth()->user())) {
            return redirect()->route('admin.roles.index')->withFragment('user-access')
                ->with('error', 'You cannot delete your own account.');
        }

        $user->syncRoles([]);
        $user->syncPermissions([]);
        $user->delete();

        return redirect()->route('admin.roles.index')->withFragment('user-access')
            ->with('success', 'User deleted successfully.');
    }
}
