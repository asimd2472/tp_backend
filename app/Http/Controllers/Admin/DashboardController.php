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

        $months = collect(range(0, 5))
            ->map(fn (int $offset) => $startOfPeriod->copy()->addMonths($offset));

        return view('admin.dashboard', [
            'user' => request()->user(),
            'totalUsers' => User::count(),
            'adminCount' => User::where('is_admin', true)->count(),
            'memberCount' => User::where('is_admin', false)->count(),
            'users' => User::query()->latest()->paginate(8),
            'chartLabels' => $months->map(fn (Carbon $month) => $month->format('M'))->all(),
            'chartValues' => $months->map(
                fn (Carbon $month) => $registrationsByMonth->get($month->format('Y-m'), 0)
            )->all(),
        ]);
    }
}
