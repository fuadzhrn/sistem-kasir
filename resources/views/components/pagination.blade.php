@php
    $itemLabel = $itemLabel ?? 'data';
    $firstItem = $paginator->firstItem() ?? 0;
    $lastItem = $paginator->lastItem() ?? 0;
@endphp

<nav class="app-pagination" role="navigation" aria-label="Navigasi halaman">
    <p class="app-pagination__summary">Menampilkan <strong>{{ number_format($firstItem, 0, ',', '.') }}–{{ number_format($lastItem, 0, ',', '.') }}</strong> dari <strong>{{ number_format($paginator->total(), 0, ',', '.') }}</strong> {{ $itemLabel }}</p>

    @if ($paginator->hasPages())
        <div class="app-pagination__links">
            @if ($paginator->onFirstPage())
                <span
                    class="app-pagination__link app-pagination__link--disabled"
                    aria-disabled="true"
                    aria-label="{{ __('pagination.previous') }}"
                >
                    <span class="app-pagination__icon" aria-hidden="true">‹</span>
                    {{ __('pagination.previous') }}
                </span>
            @else
                <a
                    class="app-pagination__link"
                    href="{{ $paginator->previousPageUrl() }}"
                    rel="prev"
                    aria-label="{{ __('pagination.previous') }}"
                >
                    <span class="app-pagination__icon" aria-hidden="true">‹</span>
                    {{ __('pagination.previous') }}
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="app-pagination__item app-pagination__item--ellipsis" aria-hidden="true">
                        {{ $element }}
                    </span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page === $paginator->currentPage())
                            <span
                                class="app-pagination__link app-pagination__link--active"
                                aria-current="page"
                                aria-label="Halaman {{ $page }}"
                            >
                                {{ $page }}
                            </span>
                        @else
                            <a
                                class="app-pagination__link app-pagination__link--number"
                                href="{{ $url }}"
                                aria-label="Buka halaman {{ $page }}"
                            >
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a
                    class="app-pagination__link"
                    href="{{ $paginator->nextPageUrl() }}"
                    rel="next"
                    aria-label="{{ __('pagination.next') }}"
                >
                    {{ __('pagination.next') }}
                    <span class="app-pagination__icon" aria-hidden="true">›</span>
                </a>
            @else
                <span
                    class="app-pagination__link app-pagination__link--disabled"
                    aria-disabled="true"
                    aria-label="{{ __('pagination.next') }}"
                >
                    {{ __('pagination.next') }}
                    <span class="app-pagination__icon" aria-hidden="true">›</span>
                </span>
            @endif
        </div>
    @endif
</nav>
