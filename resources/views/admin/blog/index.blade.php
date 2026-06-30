@extends('admin.layout')

@section('title', 'Manage Blog')
@section('heading', 'Manage Blog')

@section('content')
@php($tabs = ['' => 'All', 'published' => 'Published', 'draft' => 'Draft'])

<div class="page-actions">
    <div class="filter-tabs">
        @foreach ($tabs as $key => $label)
        <a href="{{ route('admin.blog.index', array_filter(['status' => $key, 'q' => request('q')])) }}"
            class="filter-tab {{ (string) $status === (string) $key ? 'active' : '' }}">
            {{ $label }}
            @if ($key !== '' && isset($counts[$key]))<span class="filter-count">{{ $counts[$key] }}</span>@endif
        </a>
        @endforeach
    </div>
    <a href="{{ route('admin.blog.create') }}" class="btn btn-sm">✍️ New Post</a>
</div>

<form method="GET" class="admin-search">
    @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search post title…">
    <button class="btn btn-sm" type="submit">Search</button>
</form>

<section class="panel">
    <table class="table">
        <thead>
            <tr>
                <th>Post</th>
                <th>Category</th>
                <th>Author</th>
                <th>Status</th>
                <th>Views</th>
                <th style="text-align:right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($posts as $post)
            <tr>
                <td>
                    <strong>{{ \Illuminate\Support\Str::limit($post->title, 50) }}</strong><br>
                    <span class="muted">
                        {{ $post->published_at?->format('d M Y') ?? 'Unpublished' }}
                        @if ($post->tags_count)· 🏷 {{ $post->tags_count }} {{ \Illuminate\Support\Str::plural('tag', $post->tags_count) }}@endif
                    </span>
                </td>
                <td>{{ $post->category?->name ?? '—' }}</td>
                <td>{{ $post->author?->name ?? '—' }}</td>
                <td><span class="pill pill-{{ $post->status === 'published' ? 'approved' : 'draft' }}">{{ ucfirst($post->status) }}</span></td>
                <td>{{ number_format($post->view_count) }}</td>
                <td>
                    <div class="row-actions">
                        @if ($post->status === 'published')
                        <a href="{{ route('blog.show', $post->slug) }}" target="_blank" class="btn btn-ghost btn-sm">View ↗</a>
                        @endif
                        <a href="{{ route('admin.blog.edit', $post) }}" class="btn btn-ghost btn-sm">Edit</a>
                        <form method="POST" action="{{ route('admin.blog.destroy', $post) }}"
                            data-confirm="Delete “{{ $post->title }}” permanently?">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-ghost btn-sm btn-danger" type="submit">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="muted">No posts yet. <a href="{{ route('admin.blog.create') }}">Write the first one →</a></td>
            </tr>
            @endforelse
        </tbody>
    </table>
</section>

<div class="pagination-wrap">{{ $posts->links() }}</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/admin-blog.js') }}"></script>
@endpush
