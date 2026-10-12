<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $startOfPeriod = now()->startOfMonth()->subMonths(5);
        $registrationsByMonth = User::query()
            ->where('created_at', '>=', $startOfPeriod)
            ->get(['created_at'])
            ->groupBy(fn (User $user) => $user->created_at->format('Y-m'))
            ->map->count();

        $totalUsers = User::count();
        $adminCount = User::query()
            ->where('is_admin', true)
            ->orWhereHas('roles', fn ($query) => $query->whereIn('name', ['super-admin', 'admin']))
            ->count();

        $months = collect(range(0, 5))
            ->map(fn (int $offset) => $startOfPeriod->copy()->addMonths($offset));

        return view('admin.dashboard', [
            'user' => request()->user(),
            'totalUsers' => $totalUsers,
            'adminCount' => $adminCount,
            'memberCount' => max($totalUsers - $adminCount, 0),
            'users' => User::query()->with('roles')->latest()->paginate(8),
            'chartLabels' => $months->map(fn (Carbon $month) => $month->format('M'))->all(),
            'chartValues' => $months->map(
                fn (Carbon $month) => $registrationsByMonth->get($month->format('Y-m'), 0)
            )->all(),
        ]);
    }
}
