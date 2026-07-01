<?php

declare(strict_types=1);

namespace App\Services\Page;

use App\Models\Page;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PageService
{
    /**
     * Cache key holding the trimmed set of published pages used to build the
     * header/footer navigation on every web request.
     */
    private const NAV_CACHE_KEY = 'nav_pages';

    private const NAV_CACHE_TTL = 600;

    public function showPublished(string $slug): Page
    {
        return Page::query()->published()->where('slug', $slug)->firstOrFail();
    }

    /**
     * Published pages flagged for the header or footer, ordered for display.
     * Cached (~10 min) and busted whenever a page is written.
     *
     * @return Collection<int, Page>
     */
    public function navigationPages(): Collection
    {
        return Cache::remember(self::NAV_CACHE_KEY, self::NAV_CACHE_TTL, function () {
            return Page::query()
                ->published()
                ->where(fn ($q) => $q->where('show_in_header', true)->orWhere('show_in_footer', true))
                ->orderBy('sort_order')
                ->orderBy('title')
                ->get(['title', 'slug', 'show_in_header', 'show_in_footer', 'sort_order']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $author, array $data): Page
    {
        $page = Page::create([
            'author_id' => $author->id,
            'title' => $data['title'],
            'meta_description' => $data['meta_description'] ?? null,
            'body' => $data['body'],
            'status' => $data['status'] ?? Page::STATUS_DRAFT,
            'show_in_header' => $data['show_in_header'] ?? false,
            'show_in_footer' => $data['show_in_footer'] ?? false,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        $this->flushNav();

        return $page;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Page $page, array $data): Page
    {
        $page->fill($data)->save();

        $this->flushNav();

        return $page->refresh();
    }

    public function delete(Page $page): void
    {
        $page->delete();

        $this->flushNav();
    }

    public function flushNav(): void
    {
        Cache::forget(self::NAV_CACHE_KEY);
    }
}
