@extends('admin.layout')

@php($editing = $post->exists)

@section('title', $editing ? 'Edit Post' : 'New Post')
@section('heading', $editing ? 'Edit Post' : 'New Post')

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
    action="{{ $editing ? route('admin.blog.update', $post) : route('admin.blog.store') }}"
    class="blog-form" enctype="multipart/form-data">
    @csrf
    @if ($editing)@method('PUT')@endif

    <div class="blog-form-grid">
        <div class="panel">
            <div class="field">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" value="{{ old('title', $post->title) }}"
                    maxlength="200" placeholder="A clear, descriptive headline" required>
            </div>

            <div class="field">
                <label for="excerpt">Excerpt <span class="muted">(optional summary)</span></label>
                <textarea id="excerpt" name="excerpt" rows="2" maxlength="300"
                    placeholder="One or two sentences shown in the blog list…">{{ old('excerpt', $post->excerpt) }}</textarea>
            </div>

            <div class="field">
                <label for="post-body">Content</label>
                <p class="form-hint">Use the image button in the toolbar (or paste / drag an image straight into the editor) to add pictures inside the article.</p>
                <textarea id="post-body" name="body" rows="16"
                    data-upload-url="{{ route('admin.blog.upload') }}">{{ old('body', $post->body) }}</textarea>
                <button type="button" class="btn btn-ghost btn-sm" id="ml-content-btn" style="margin-top:8px">🗂️ Insert image from library</button>
            </div>
        </div>

        <aside class="panel">
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="draft" @selected(old('status', $post->status) === 'draft')>Draft</option>
                    <option value="published" @selected(old('status', $post->status) === 'published')>Published</option>
                </select>
            </div>

            <div class="field">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id">
                    <option value="">— No category —</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((int) old('category_id', $post->category_id) === $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="new_category">Or add a new category</label>
                <input type="text" id="new_category" name="new_category" value="{{ old('new_category') }}"
                    maxlength="100" placeholder="e.g. Market News">
                <p class="form-hint">If filled, this overrides the dropdown above.</p>
            </div>

            <div class="field">
                <label for="tags">Tags</label>
                <input type="text" id="tags" name="tags"
                    value="{{ old('tags', $post->exists ? $post->tags->pluck('name')->implode(', ') : '') }}"
                    placeholder="dhaka, apartments, budget">
                <p class="form-hint">Comma-separated. New tags are created automatically.</p>
            </div>

            <div class="field">
                <label>Cover image</label>
                @if ($post->cover_image)
                    <img src="{{ $post->cover_image }}" alt="Current cover" class="cover-preview" id="cover-current">
                @endif
                <img alt="New cover preview" class="cover-preview" id="cover-preview" hidden>
                <input type="file" id="cover_file" name="cover_file" accept="image/*">
                <p class="form-hint">Upload a JPG, PNG, WEBP or GIF (max 5&nbsp;MB). Replaces the current cover.</p>
                <button type="button" class="btn btn-ghost btn-sm" id="ml-cover-btn" style="margin-bottom:8px">🗂️ Choose from library</button>
                <input type="text" id="cover_image" name="cover_image"
                    value="{{ old('cover_image', $post->cover_image) }}" maxlength="255"
                    placeholder="…or paste an image URL">
                @if ($post->cover_image)
                    <label class="checkbox-inline">
                        <input type="checkbox" name="remove_cover" value="1"> Remove the current cover image
                    </label>
                @endif
            </div>
        </aside>
    </div>

    <div class="blog-form-foot">
        <a href="{{ route('admin.blog.index') }}" class="btn btn-ghost">Cancel</a>
        <button type="submit" class="btn">{{ $editing ? 'Update post' : 'Create post' }}</button>
    </div>
</form>

@include('partials.media-library')
@endsection

@push('scripts')
    <script src="{{ asset('vendor/ckeditor5/ckeditor.js') }}?v={{ filemtime(public_path('vendor/ckeditor5/ckeditor.js')) }}"></script>
    <script src="{{ asset('js/media-library.js') }}?v={{ filemtime(public_path('js/media-library.js')) }}"></script>
    <script src="{{ asset('js/admin-blog.js') }}?v={{ filemtime(public_path('js/admin-blog.js')) }}"></script>
@endpush
