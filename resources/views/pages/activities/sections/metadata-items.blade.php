<dl class="activities-metadata-list">
    @foreach ($items as $key => $value)
        <div>
            <dt>{{ str((string) $key)->replace('_', ' ')->title() }}</dt>
            <dd>
                @if (is_array($value))
                    @include('pages.activities.sections.metadata-items', ['items' => $value])
                @elseif (is_bool($value))
                    {{ $value ? 'Ya' : 'Tidak' }}
                @elseif ($value === null)
                    —
                @else
                    {{ $value }}
                @endif
            </dd>
        </div>
    @endforeach
</dl>
