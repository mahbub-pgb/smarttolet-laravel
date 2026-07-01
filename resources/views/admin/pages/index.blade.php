@extends('admin.layout')

@section('title', 'Manage Pages')
@section('heading', 'Manage Pages')

@section('content')
@php($tabs = ['' => 'All', 'published' => 'Published', 'draft' => 'Draft'])

<div class="page-actions">
    <div class="filter-tabs">
        @foreach ($tabs as $key => $label)
        <a href="{{ route('admin.pages.index', array_filter(['status' => $key, 'q' => request('q')])) }}"
            class="filter-tab {{ (string) $status === (string) $key ? 'active' : '' }}">
            {{ $label }}
            @if ($key !== '' && isset($counts[$key]))<span class="filter-count">{{ $counts[$key] }}</span>@endif
        </a>
        @endforeach
    </div>
    <a href="{{ route('admin.pages.create') }}" class="btn btn-sm">📄 New Page</a>
</div>

<form method="GET" class="admin-search">
    @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Search page title…">
    <button class="btn btn-sm" type="submit">Search</button>
</form>

<section class="panel">
    <table class="table">
        <thead>
            <tr>
                <th>Page</th>
                <th>Placement</th>
                <th>Author</th>
                <th>Status</th>
                <th style="text-align:right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pages as $page)
            <tr>
                <td>
                    <strong>{{ \Illuminate\Support\Str::limit($page->title, 50) }}</strong><br>
                    <span class="muted">/pages/{{ $page->slug }}</span>
                </td>
                <td>
                    @if ($page->show_in_header)<span class="pill pill-approved">Header</span>@endif
                    @if ($page->show_in_footer)<span class="pill pill-approved">Footer</span>@endif
                    @unless ($page->show_in_header || $page->show_in_footer)<span class="muted">—</span>@endunless
                </td>
                <td>{{ $page->author?->name ?? '—' }}</td>
                <td><span class="pill pill-{{ $page->status === 'published' ? 'approved' : 'draft' }}">{{ ucfirst($page->status) }}</span></td>
                <td>
                    <div class="row-actions">
                        @if ($page->status === 'published')
                        <a href="{{ route('pages.show', $page->slug) }}" target="_blank" class="btn btn-ghost btn-sm">View ↗</a>
                        @endif
                        <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-ghost btn-sm">Edit</a>
                        <form method="POST" action="{{ route('admin.pages.destroy', $page) }}"
                            data-confirm="Delete “{{ $page->title }}” permanently?">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-ghost btn-sm btn-danger" type="submit">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="muted">No pages yet. <a href="{{ route('admin.pages.create') }}">Create the first one →</a></td>
            </tr>
            @endforelse
        </tbody>
    </table>
</section>

<div class="pagination-wrap">{{ $pages->links() }}</div>
@endsection
