<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Services\Blog\BlogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function __construct(private BlogService $blog) {}

    /** GET /blog — public list of published posts, filterable by category / tag. */
    public function index(Request $request): View
    {
        $page = max(1, $request->integer('page', 1));

        $posts = $this->blog
            ->publicList($request->only(['category', 'tag', 'q']), 9, $page)
            ->withQueryString();

        $categories = BlogCategory::query()
            ->whereHas('posts', fn ($q) => $q->published())
            ->withCount(['posts as posts_count' => fn ($q) => $q->published()])
            ->orderBy('name')
            ->get();

        $tags = BlogTag::query()
            ->whereHas('posts', fn ($q) => $q->published())
            ->orderBy('name')
            ->get();

        return view('blog.index', compact('posts', 'categories', 'tags'));
    }

    /** GET /blog/{slug} — single published post. */
    public function show(string $slug): View
    {
        $post = $this->blog->showPublished($slug);

        $related = BlogPost::query()
            ->published()
            ->with('category:id,name,slug')
            ->where('id', '!=', $post->id)
            ->when($post->category_id, fn ($q) => $q->where('category_id', $post->category_id))
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('blog.show', compact('post', 'related'));
    }
}
