@php
    $colors = [
        ['name' => 'Primary', 'hex' => '#166534', 'class' => 'primary'],
        ['name' => 'Primary dark', 'hex' => '#14532d', 'class' => 'primary-dark'],
        ['name' => 'Primary hover', 'hex' => '#15803d', 'class' => 'primary-hover'],
        ['name' => 'Primary light', 'hex' => '#dcfce7', 'class' => 'primary-light'],
        ['name' => 'Surface', 'hex' => '#ffffff', 'class' => 'surface'],
        ['name' => 'Background', 'hex' => '#f5f7f6', 'class' => 'background'],
        ['name' => 'Text', 'hex' => '#1f2937', 'class' => 'text'],
        ['name' => 'Muted', 'hex' => '#6b7280', 'class' => 'muted'],
        ['name' => 'Success', 'hex' => '#16a34a', 'class' => 'success'],
        ['name' => 'Warning', 'hex' => '#f59e0b', 'class' => 'warning'],
        ['name' => 'Danger', 'hex' => '#dc2626', 'class' => 'danger'],
        ['name' => 'Info', 'hex' => '#2563eb', 'class' => 'info'],
    ];
@endphp

<section class="ds-section" id="colors" aria-labelledby="colors-title">
    <div class="ds-section__header">
        <div>
            <span class="ds-section__number">02</span>
            <h2 id="colors-title">Palet warna</h2>
            <p>Nuansa hijau pertanian dipadukan dengan warna status yang mudah dibedakan.</p>
        </div>
    </div>

    <div class="color-grid">
        @foreach ($colors as $color)
            <article class="color-swatch">
                <div class="color-swatch__preview color-swatch__preview--{{ $color['class'] }}"></div>
                <div class="color-swatch__meta">
                    <strong>{{ $color['name'] }}</strong>
                    <code>{{ $color['hex'] }}</code>
                </div>
            </article>
        @endforeach
    </div>
</section>
