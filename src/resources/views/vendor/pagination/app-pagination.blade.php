@if ($paginator->hasPages())
<nav class="pagination" role="navigation" aria-label="ページネーション">
    @if ($paginator->onFirstPage())
    <span class="pagination__button is-disabled" aria-disabled="true">前へ</span>
    @else
    <a
        href="{{ $paginator->previousPageUrl() }}"
        class="pagination__button"
        rel="prev">
        前へ
    </a>
    @endif

    <div class="pagination__pages">
        @foreach ($elements as $element)
        @if (is_string($element))
        <span class="pagination__ellipsis">{{ $element }}</span>
        @endif

        @if (is_array($element))
        @foreach ($element as $page => $url)
        @if ($page == $paginator->currentPage())
        <span class="pagination__page is-active" aria-current="page">
            {{ $page }}
        </span>
        @else
        <a href="{{ $url }}" class="pagination__page">{{ $page }}</a>
        @endif
        @endforeach
        @endif
        @endforeach
    </div>

    @if ($paginator->hasMorePages())
    <a
        href="{{ $paginator->nextPageUrl() }}"
        class="pagination__button"
        rel="next">
        次へ
    </a>
    @else
    <span class="pagination__button is-disabled" aria-disabled="true">次へ</span>
    @endif
</nav>
@endif