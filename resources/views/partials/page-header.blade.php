<header class="page-heading">
    @if (! empty($breadcrumbs))
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <ol>
                @foreach ($breadcrumbs as $breadcrumb)
                    <li>
                        @if (! empty($breadcrumb['url']))
                            <a href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['label'] }}</a>
                        @else
                            <span aria-current="page">{{ $breadcrumb['label'] }}</span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    @endif

    <div class="page-heading__row">
        <div class="page-heading__content">
            @if (! empty($eyebrow))
                <span class="badge badge-outline">{{ $eyebrow }}</span>
            @endif
            <h2>{{ $title }}</h2>
            @if (! empty($description))
                <p>{{ $description }}</p>
            @endif
        </div>

        @if (! empty($actions))
            <div class="page-heading__actions">
                @foreach ($actions as $action)
                    <button
                        class="{{ $action['class'] ?? 'btn btn-secondary' }}"
                        type="button"
                        @if (! empty($action['modal'])) data-modal-open="{{ $action['modal'] }}" @endif
                        @if (! empty($action['toast'])) data-toast-demo="{{ $action['toast'] }}" @endif
                    >
                        {{ $action['label'] }}
                    </button>
                @endforeach
            </div>
        @endif
    </div>
</header>
