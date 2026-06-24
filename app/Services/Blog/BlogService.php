<?php

declare(strict_types=1);

namespace App\Services\Blog;

use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BlogService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<BlogPost>
     */
    public function publicList(array $filters, int $perPage, int $page): LengthAwarePaginator
    {
        return BlogPost::query()
            ->published()
            ->with(['author:id,name,photo', 'category:id,name,slug'])
            ->when(! empty($filters['category']), fn ($q) => $q->whereHas('category', fn ($c) => $c->where('slug', $filters['category'])))
            ->when(! empty($filters['tag']), fn ($q) => $q->whereHas('tags', fn ($t) => $t->where('slug', $filters['tag'])))
            ->when(! empty($filters['q']), fn ($q) => $q->where('title', 'like', '%'.$filters['q'].'%'))
            ->latest('published_at')
            ->paginate(perPage: $perPage, page: $page);
    }

    public function showPublished(string $slug): BlogPost
    {
        $post = BlogPost::query()->published()->where('slug', $slug)
            ->with(['author:id,name,photo', 'category:id,name,slug', 'tags:id,name,slug'])
            ->firstOrFail();

        $post->increment('view_count');

        return $post;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $author, array $data): BlogPost
    {
        $post = BlogPost::create([
            'author_id' => $author->id,
            'category_id' => $data['category_id'] ?? null,
            'title' => $data['title'],
            'excerpt' => $data['excerpt'] ?? null,
            'body' => $data['body'],
            'cover_image' => $data['cover_image'] ?? null,
            'status' => $data['status'] ?? BlogPost::STATUS_DRAFT,
            'published_at' => ($data['status'] ?? null) === BlogPost::STATUS_PUBLISHED ? now() : null,
        ]);

        $this->syncTags($post, $data['tags'] ?? null);

        return $post->load(['category:id,name,slug', 'tags:id,name,slug']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(BlogPost $post, array $data): BlogPost
    {
        if (($data['status'] ?? null) === BlogPost::STATUS_PUBLISHED && $post->published_at === null) {
            $data['published_at'] = now();
        }

        $post->fill($data)->save();

        if (array_key_exists('tags', $data)) {
            $this->syncTags($post, $data['tags']);
        }

        return $post->refresh()->load(['category:id,name,slug', 'tags:id,name,slug']);
    }

    public function delete(BlogPost $post): void
    {
        $post->delete();
    }

    /**
     * @param  array<int, string>|null  $tagNames
     */
    private function syncTags(BlogPost $post, ?array $tagNames): void
    {
        if ($tagNames === null) {
            return;
        }

        $ids = collect($tagNames)
            ->filter()
            ->map(fn (string $name) => BlogTag::firstOrCreate(['slug' => \Illuminate\Support\Str::slug($name)], ['name' => $name])->id)
            ->all();

        $post->tags()->sync($ids);
    }
}
