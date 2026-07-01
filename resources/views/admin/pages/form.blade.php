@extends('admin.layout')

@php($editing = $page->exists)

@section('title', $editing ? 'Edit Page' : 'New Page')
@section('heading', $editing ? 'Edit Page' : 'New Page')

@section('content')
@if ($errors->any())
    <div class="alert alert-error">
        <strong>Please fix the following:</strong>
        <ul style="margin:8px 0 0 18px">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<form method="POST"
    action="{{ $editing ? route('admin.pages.update', $page) : route('admin.pages.store') }}"
    class="blog-form">
    @csrf
    @if ($editing)@method('PUT')@endif

    <div class="blog-form-grid">
        <div class="panel">
            <div class="field">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" value="{{ old('title', $page->title) }}"
                    maxlength="200" placeholder="e.g. About Us" required>
                @if ($editing)<p class="form-hint">URL: /pages/{{ $page->slug }}</p>@endif
            </div>

            <div class="field">
                <label for="meta_description">Meta description <span class="muted">(optional, for SEO)</span></label>
                <textarea id="meta_description" name="meta_description" rows="2" maxlength="300"
                    placeholder="A short summary shown in search-engine results…">{{ old('meta_description', $page->meta_description) }}</textarea>
            </div>

            <div class="field">
                <label for="post-body">Content</label>
                <p class="form-hint">Use the image button in the toolbar (or paste / drag an image straight into the editor) to add pictures.</p>
                <textarea id="post-body" name="body" rows="18"
                    data-upload-url="{{ route('admin.pages.upload') }}">{{ old('body', $page->body) }}</textarea>
            </div>
        </div>

        <aside class="panel">
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="draft" @selected(old('status', $page->status) === 'draft')>Draft</option>
                    <option value="published" @selected(old('status', $page->status) === 'published')>Published</option>
                </select>
            </div>

            <div class="field">
                <label>Placement</label>
                <label class="checkbox-inline">
                    <input type="checkbox" name="show_in_header" value="1" @checked(old('show_in_header', $page->show_in_header))>
                    Show link in the site header
                </label>
                <label class="checkbox-inline">
                    <input type="checkbox" name="show_in_footer" value="1" @checked(old('show_in_footer', $page->show_in_footer))>
                    Show link in the site footer
                </label>
                <p class="form-hint">Links only appear once the page is published.</p>
            </div>

            <div class="field">
                <label for="sort_order">Order</label>
                <input type="number" id="sort_order" name="sort_order" min="0" max="9999"
                    value="{{ old('sort_order', $page->sort_order ?? 0) }}">
                <p class="form-hint">Lower numbers appear first in the header / footer.</p>
            </div>
        </aside>
    </div>

    <div class="blog-form-foot">
        <a href="{{ route('admin.pages.index') }}" class="btn btn-ghost">Cancel</a>
        <button type="submit" class="btn">{{ $editing ? 'Update page' : 'Create page' }}</button>
    </div>
</form>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/ckeditor5-super/ckeditor.js') }}?v={{ filemtime(public_path('vendor/ckeditor5-super/ckeditor.js')) }}"></script>
    <script src="{{ asset('js/admin-blog.js') }}?v={{ filemtime(public_path('js/admin-blog.js')) }}"></script>
@endpush
