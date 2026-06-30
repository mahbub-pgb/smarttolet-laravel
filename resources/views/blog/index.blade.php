@extends('layouts.app')

@section('title', 'Blog')

@section('content')
    <section class="section">
        <div class="container">
            <div class="section-head">
                <div>
                    <h2>From the blog</h2>
                    <p>{{ $posts->total() }} {{ \Illuminate\Support\Str::plural('article', $posts->total()) }} on renting, neighbourhoods, and tenant tips.</p>
                </div>
            </div>

            {{-- Category pill nav: keeps the active tag/keyword, swaps the category. --}}
            @php($catBase = request()->except(['page', 'category']))
            <nav class="cat-nav" aria-label="Browse by category">
                <a href="{{ route('blog.index', $catBase) }}"
                   class="cat-pill @if (! request()->filled('category')) is-active @endif">
                    <span class="cat-pill-icon">✨</span> All
                </a>
                @foreach ($categories as $category)
                    <a href="{{ route('blog.index', array_merge($catBase, ['category' => $category->slug])) }}"
                       class="cat-pill @if (request('category') === $category->slug) is-active @endif">
                        {{ $category->name }} <span class="cat-pill-count">{{ $category->posts_count }}</span>
                    </a>
                @endforeach
            </nav>

            <div class="layout">
                <aside class="filters">
                    <form method="GET" action="{{ route('blog.index') }}">
                        <h3>Filter</h3>
                        @if (request('category'))<input type="hidden" name="category" value="{{ request('category') }}">@endif

                        <div class="field">
                            <label>Keyword</label>
                            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search titles…">
                        </div>

                        <div class="field">
                            <label>Tag</label>
                            <select name="tag">
                                <option value="">Any tag</option>
                                @foreach ($tags as $tag)
                                    <option value="{{ $tag->slug }}" @selected(request('tag') === $tag->slug)>{{ $tag->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn btn-block">Apply filters</button>
                        <a href="{{ route('blog.index') }}" class="btn btn-ghost btn-block" style="margin-top:8px">Reset</a>
                    </form>
                </aside>

                <div>
                    @if ($posts->isEmpty())
                        <div class="empty">
                            <p>No articles match your filters.</p>
                            <a href="{{ route('blog.index') }}" class="btn btn-ghost btn-sm">Clear filters</a>
                        </div>
                    @else
                        <div class="blog-grid">
                            @foreach ($posts as $post)
                                <article class="blog-card">
                                    <a href="{{ route('blog.show', $post->slug) }}" class="blog-card-media">
                                        @if ($post->cover_image)
                                            <img src="{{ $post->cover_image }}" alt="{{ $post->title }}" loading="lazy">
                                        @else
                                            <span class="blog-card-placeholder">📝</span>
                                        @endif
                                    </a>
                                    <div class="blog-card-body">
                                        @if ($post->category)
                                            <a href="{{ route('blog.index', ['category' => $post->category->slug]) }}" class="blog-card-cat">{{ $post->category->name }}</a>
                                        @endif
                                        <h3><a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a></h3>
                                        @if ($post->excerpt)<p>{{ \Illuminate\Support\Str::limit($post->excerpt, 120) }}</p>@endif
                                        <div class="blog-card-meta">
                                            <span>{{ $post->author?->name ?? 'SmartToLet' }}</span>
                                            <span>·</span>
                                            <span>{{ $post->published_at?->format('d M Y') }}</span>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                        <div class="pagination-wrap">{{ $posts->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
