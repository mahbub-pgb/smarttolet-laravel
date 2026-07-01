<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Page\PageService;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function __construct(private PageService $pages) {}

    /** GET /pages/{slug} — a single published static page. */
    public function show(string $slug): View
    {
        $page = $this->pages->showPublished($slug);

        return view('pages.show', compact('page'));
    }
}
