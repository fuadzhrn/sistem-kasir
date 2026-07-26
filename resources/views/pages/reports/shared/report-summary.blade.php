<section class="report-summary" aria-label="Total berdasarkan seluruh hasil filter">
    @foreach ($report['summary'] as $item)
        <article class="report-summary__card card"><p>{{ $item['label'] }}</p><strong class="{{ str_starts_with($item['value'], '-Rp') ? 'is-negative' : '' }}">{{ $item['value'] }}</strong></article>
    @endforeach
</section>
