{{-- Custom pagination themed by public/css/app.css (.pagination/.page-item/.page-link).
     A single, framework-agnostic <ul> — no Bootstrap/Tailwind utility classes. --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination">
        <ul class="pagination">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link" aria-hidden="true">&laquo;</span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous">&laquo;</a>
                </li>
            @endif

            {{-- Page numbers --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link">{{ $element }}</span>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active" aria-current="page">
                                <span class="page-link">{{ $page }}</span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next">&raquo;</a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link" aria-hidden="true">&raquo;</span>
                </li>
            @endif
        </ul>

        {{-- Jump straight to a page: a plain GET form that preserves the active
             query string (filters, sort) and only swaps the `page` value. --}}
        <form method="GET" action="{{ url()->current() }}" class="page-jump">
            @foreach (request()->except('page') as $key => $value)
                @if (is_array($value))
                    @foreach ($value as $item)
                        <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <label for="page-jump-input">Page</label>
            <input type="number" id="page-jump-input" name="page" class="page-jump-input"
                   min="1" max="{{ $paginator->lastPage() }}"
                   value="{{ $paginator->currentPage() }}" aria-label="Page number">
            <span>of {{ $paginator->lastPage() }}</span>
            <button type="submit" class="btn btn-sm">Go</button>
        </form>
    </nav>
@endif
