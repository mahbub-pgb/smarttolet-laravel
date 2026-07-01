@extends('layouts.app')

@section('title', $page->title)

@push('head')
    @if ($page->meta_description)
        <meta name="description" content="{{ $page->meta_description }}">
    @endif
@endpush

@section('content')
    <article class="section">
        <div class="container container-narrow">
            <header class="post-head">
                <h1>{{ $page->title }}</h1>
            </header>

            {{-- Body is HTML authored in CKEditor by trusted staff (manage_pages). --}}
            <div class="prose">
                {!! $page->body !!}
            </div>
        </div>
    </article>
@endsection
