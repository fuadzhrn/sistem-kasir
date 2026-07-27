<?php

namespace Tests\Feature;

use Tests\TestCase;

class QuantityFrontendContractTest extends TestCase
{
    public function test_layouts_load_the_central_quantity_formatter(): void
    {
        foreach ([
            resource_path('views/layouts/app.blade.php'),
            resource_path('views/layouts/cashier.blade.php'),
        ] as $layout) {
            $this->assertStringContainsString(
                'assets/js/core/quantity.js',
                (string) file_get_contents($layout),
            );
        }
    }

    public function test_frontend_quantity_formatter_uses_indonesian_locale_without_parse_float(): void
    {
        $source = (string) file_get_contents(public_path('assets/js/core/quantity.js'));

        $this->assertStringContainsString("Intl.NumberFormat('id-ID'", $source);
        $this->assertStringContainsString('maximumFractionDigits: 0', $source);
        $this->assertStringContainsString('replace(/0+$/', $source);
        $this->assertStringNotContainsString('parseFloat', $source);
        $this->assertStringNotContainsString('Number.parseFloat', $source);
    }
}
