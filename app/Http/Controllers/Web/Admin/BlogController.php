<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Services\Blog\BlogService;
use App\Services\Media\MediaService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Server-rendered blog management for staff with the `manage_blog` permission
 * (moderator / editor and above). Validation is done inline rather than via the
 * API form requests because the web layer authenticates on the session `web`
 * guard, while those requests resolve the default (JWT) guard.
 */
class BlogController extends Controller
{
    public function __construct(private BlogService $blog, private MediaService $media) {}

    /** GET /admin/blog — paginated list of every post (draft + published). */
    public function index(Request $request): View
    {
        $status = $request->string('status')->value();

        $posts = BlogPost::query()
            ->with(['author:id,name', 'category:id,name'])
            ->withCount('tags')
            ->when(in_array($status, ['draft', 'published'], true), fn ($q) => $q->where('status', $status))
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->string('q').'%'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = BlogPost::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.blog.index', compact('posts', 'counts', 'status'));
    }

    /** GET /admin/blog/create */
    public function create(): View
    {
        return view('admin.blog.form', [
            'post' => new BlogPost(['status' => BlogPost::STATUS_DRAFT]),
            'categories' => BlogCategory::orderBy('name')->get(),
        ]);
    }

    /** POST /admin/blog */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->prepare($request, $this->validateData($request));

        $post = $this->blog->create(auth('web')->user(), $data);

        return redirect()->route('admin.blog.index')
            ->with('status', "“{$post->title}” saved.");
    }

    /** GET /admin/blog/{post}/edit */
    public function edit(BlogPost $post): View
    {
        $post->load('tags:id,name');

        return view('admin.blog.form', [
            'post' => $post,
            'categories' => BlogCategory::orderBy('name')->get(),
        ]);
    }

    /** PUT /admin/blog/{post} */
    public function update(Request $request, BlogPost $post): RedirectResponse
    {
        $data = $this->prepare($request, $this->validateData($request));

        $this->blog->update($post, $data);

        return redirect()->route('admin.blog.index')
            ->with('status', "“{$post->title}” updated.");
    }

    /** DELETE /admin/blog/{post} */
    public function destroy(BlogPost $post): RedirectResponse
    {
        $title = $post->title;
        $this->blog->delete($post);

        return back()->with('status', "“{$title}” deleted.");
    }

    /**
     * POST /admin/blog/upload-image — image drop target for CKEditor's upload
     * adapter. Returns the stored URL in the shape the adapter expects.
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'upload' => ['required', 'file', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
        ]);

        $media = $this->media->upload(auth('web')->user(), $request->file('upload'), 'blog');

        return response()->json(['url' => $media->url]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'body' => ['required', 'string'],
            'cover_image' => ['nullable', 'string', 'max:255'],
            'cover_file' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
            'category_id' => ['nullable', 'integer', 'exists:blog_categories,id'],
            'new_category' => ['nullable', 'string', 'max:100'],
            'status' => ['required', Rule::in([BlogPost::STATUS_DRAFT, BlogPost::STATUS_PUBLISHED])],
            'tags' => ['nullable', 'string', 'max:500'],
        ], [], [
            'new_category' => 'new category',
            'cover_file' => 'cover image',
        ]);
    }

    /**
     * Normalise the validated form input into the shape BlogService expects:
     * resolve an optional new category, split the comma-separated tag string,
     * and drop blank optional strings.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepare(Request $request, array $data): array
    {
        // A typed-in new category wins over the dropdown selection.
        $newCategory = trim((string) ($data['new_category'] ?? ''));
        if ($newCategory !== '') {
            $data['category_id'] = BlogCategory::firstOrCreate(
                ['slug' => Str::slug($newCategory)],
                ['name' => $newCategory],
            )->id;
        }
        unset($data['new_category']);

        // Cover image precedence: an explicit "remove" wins, then a freshly
        // uploaded file, otherwise the pasted-URL value already in $data.
        if ($request->boolean('remove_cover')) {
            $data['cover_image'] = null;
        } elseif ($request->hasFile('cover_file')) {
            $data['cover_image'] = $this->media
                ->upload(auth('web')->user(), $request->file('cover_file'), 'blog')
                ->url;
        }
        unset($data['cover_file']);

        $data['tags'] = collect(explode(',', (string) ($data['tags'] ?? '')))
            ->map(fn (string $t) => trim($t))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $data['excerpt'] = $data['excerpt'] ?: null;
        $data['cover_image'] = $data['cover_image'] ?: null;

        return $data;
    }
}
