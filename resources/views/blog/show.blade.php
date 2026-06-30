@extends('layouts.app')

@section('title', $post->title)

@push('head')
    <meta name="description" content="{{ $post->excerpt ?? \Illuminate\Support\Str::limit(strip_tags($post->body), 150) }}">
@endpush

@section('content')
    <article class="section">
        <div class="container container-narrow">
            <nav class="breadcrumb">
                <a href="{{ route('blog.index') }}">Blog</a>
                @if ($post->category)
                    <span>/</span>
                    <a href="{{ route('blog.index', ['category' => $post->category->slug]) }}">{{ $post->category->name }}</a>
                @endif
            </nav>

            <header class="post-head">
                <h1>{{ $post->title }}</h1>
                <div class="post-meta">
                    <span>{{ $post->author?->name ?? 'SmartToLet' }}</span>
                    <span>·</span>
                    <span>{{ $post->published_at?->format('d M Y') }}</span>
                    <span>·</span>
                    <span>{{ number_format($post->view_count) }} views</span>
                </div>
            </header>

            @if ($post->cover_image)
                <img src="{{ $post->cover_image }}" alt="{{ $post->title }}" class="post-cover">
            @endif

            {{-- Body is HTML authored in CKEditor by trusted staff (manage_blog). --}}
            <div class="prose">
                {!! $post->body !!}
            </div>

            @if ($post->tags->isNotEmpty())
                <div class="post-tags">
                    @foreach ($post->tags as $tag)
                        <a href="{{ route('blog.index', ['tag' => $tag->slug]) }}" class="tag-chip">🏷 {{ $tag->name }}</a>
                    @endforeach
                </div>
            @endif
        </div>
    </article>

    @if ($related->isNotEmpty())
        <section class="section section-alt">
            <div class="container">
                <h2 style="margin-bottom:18px">Related articles</h2>
                <div class="blog-grid">
                    @foreach ($related as $rel)
                        <article class="blog-card">
                            <a href="{{ route('blog.show', $rel->slug) }}" class="blog-card-media">
                                @if ($rel->cover_image)
                                    <img src="{{ $rel->cover_image }}" alt="{{ $rel->title }}" loading="lazy">
                                @else
                                    <span class="blog-card-placeholder">📝</span>
                                @endif
                            </a>
                            <div class="blog-card-body">
                                @if ($rel->category)<span class="blog-card-cat">{{ $rel->category->name }}</span>@endif
                                <h3><a href="{{ route('blog.show', $rel->slug) }}">{{ $rel->title }}</a></h3>
                                <div class="blog-card-meta">
                                    <span>{{ $rel->published_at?->format('d M Y') }}</span>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
