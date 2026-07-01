<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * A static content page (About, Contact, Privacy, …) authored by staff with the
 * `manage_pages` permission. Mirrors the blog post lifecycle (draft/published,
 * unique slug, soft deletes) but is standalone content — no categories or tags.
 */
class Page extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'author_id',
        'title',
        'slug',
        'meta_description',
        'body',
        'status',
        'show_in_header',
        'show_in_footer',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'show_in_header' => 'boolean',
            'show_in_footer' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Page $page) {
            if (empty($page->slug) && ! empty($page->title)) {
                $page->slug = static::uniqueSlug($page->title, $page->id);
            }
        });
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'page';
        $slug = $base;
        $i = 1;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->withTrashed()
            ->exists()
        ) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }
}
