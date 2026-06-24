<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Report;
use App\Models\User;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /** GET /admin — overview with headline counts. */
    public function index(): View
    {
        $stats = [
            'users' => User::count(),
            'listings' => Listing::count(),
            'pending' => Listing::where('status', Listing::STATUS_PENDING)->count(),
            'approved' => Listing::where('status', Listing::STATUS_APPROVED)->count(),
            'reports' => Report::query()->where('status', 'pending')->count(),
        ];

        $recentUsers = User::query()->latest()->limit(8)->get();
        $recentListings = Listing::query()->latest()->limit(8)->get();

        return view('admin.dashboard', compact('stats', 'recentUsers', 'recentListings'));
    }
}
