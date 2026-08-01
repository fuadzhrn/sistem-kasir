<dl class="activities-metadata-list">
    @foreach ($items as $key => $value)
        @php
            $normalizedKey = mb_strtolower((string) $key);
            $isMoney = is_numeric($value)
                && preg_match('/amount|price|cost|total|subtotal|discount|profit|hpp|inventory_value/', $normalizedKey) === 1
                && ! str_contains($normalizedKey, 'count');
        @endphp
        <div>
            <dt>{{ str((string) $key)->replace('_', ' ')->title() }}</dt>
            <dd>
                @if (is_array($value))
                    @include('pages.activities.sections.metadata-items', ['items' => $value])
                @elseif (is_bool($value))
                    {{ $value ? 'Ya' : 'Tidak' }}
                @elseif ($value === null)
                    —
                @elseif ($isMoney)
                    {{ \App\Support\Format\Rupiah::format((string) $value) }}
                @else
                    {{ $value }}
                @endif
            </dd>
        </div>
    @endforeach
</dl>
