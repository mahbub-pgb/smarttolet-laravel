<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Blog\StoreBlogPostRequest;
use App\Http\Requests\Blog\UpdateBlogPostRequest;
use App\Http\Resources\BlogPostResource;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Services\Blog\BlogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function __construct(private BlogService $blog) {}

    // --- Public ----------------------------------------------------------

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->blog->publicList(
            $request->only(['category', 'tag', 'q']),
            (int) $request->integer('limit', 12),
            (int) $request->integer('page', 1),
        );

        return $this->paginatedResponse($paginator, 'OK', fn ($items) => BlogPostResource::collection($items));
    }

    public function show(string $slug): JsonResponse
    {
        return $this->ok(new BlogPostResource($this->blog->showPublished($slug)), 'OK');
    }

    public function categories(): JsonResponse
    {
        return $this->ok(
            BlogCategory::query()->withCount('posts')->orderBy('name')->get(['id', 'name', 'slug', 'description']),
            'OK',
        );
    }

    // --- Staff (manage_blog) ---------------------------------------------

    public function store(StoreBlogPostRequest $request): JsonResponse
    {
        $post = $this->blog->create($request->user(), $request->validated());

        return $this->created(new BlogPostResource($post), 'Post created.');
    }

    public function update(UpdateBlogPostRequest $request, BlogPost $post): JsonResponse
    {
        $updated = $this->blog->update($post, $request->validated());

        return $this->ok(new BlogPostResource($updated), 'Post updated.');
    }

    public function destroy(BlogPost $post): JsonResponse
    {
        $this->blog->delete($post);

        return $this->noContentResponse('Post deleted.');
    }
}
