<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Listing;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    /** GET / — landing page with featured listings. */
    public function index(): View
    {
        $featured = Listing::query()
            ->publiclyVisible()
            ->latest('approved_at')
            ->limit(6)
            ->get();

        $areas = Listing::query()
            ->publiclyVisible()
            ->select('area_name')
            ->distinct()
            ->orderBy('area_name')
            ->limit(12)
            ->pluck('area_name');

        $posts = BlogPost::query()
            ->where('status', BlogPost::STATUS_PUBLISHED)
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('home', compact('featured', 'areas', 'posts'));
    }
}
