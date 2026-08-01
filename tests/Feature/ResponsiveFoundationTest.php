<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class ResponsiveFoundationTest extends TestCase
{
    public function test_application_layouts_load_the_global_responsive_foundation(): void
    {
        foreach (['app', 'auth', 'cashier'] as $layoutName) {
            $layout = (string) file_get_contents(
                resource_path("views/layouts/{$layoutName}.blade.php"),
            );

            $this->assertStringContainsString(
                'assets/css/responsive.css',
                $layout,
                "Layout {$layoutName} belum memuat fondasi responsive.",
            );
            $this->assertGreaterThan(
                strpos($layout, "@stack('styles')"),
                strpos($layout, 'assets/css/responsive.css'),
                "Fondasi responsive pada layout {$layoutName} harus dimuat setelah stylesheet halaman.",
            );
        }
    }

    public function test_responsive_stylesheet_defines_safe_breakpoints_and_no_overflow_mask(): void
    {
        $css = $this->responsiveCss();

        foreach (['1024px', '768px', '480px'] as $breakpoint) {
            $this->assertStringContainsString(
                "@media (max-width: {$breakpoint})",
                $css,
            );
        }

        $this->assertStringContainsString('grid-template-columns: repeat(2, minmax(0, 1fr));', $css);
        $this->assertStringContainsString('grid-template-columns: minmax(0, 1fr);', $css);
        $this->assertStringContainsString('min-width: 0;', $css);
        $this->assertStringNotContainsString('body {'.PHP_EOL.'    overflow-x: hidden;', $css);
        $this->assertStringNotContainsString('html {'.PHP_EOL.'    overflow-x: hidden;', $css);
    }

    public function test_global_components_have_safe_responsive_constraints(): void
    {
        $reset = (string) file_get_contents(public_path('assets/css/base/reset.css'));
        $forms = (string) file_get_contents(public_path('assets/css/components/forms.css'));
        $tables = (string) file_get_contents(public_path('assets/css/components/tables.css'));
        $modal = (string) file_get_contents(public_path('assets/css/components/modal.css'));
        $cards = (string) file_get_contents(public_path('assets/css/components/cards.css'));

        $this->assertStringContainsString('box-sizing: border-box;', $reset);
        $this->assertMatchesRegularExpression('/img\\s*\\{[^}]*height:\\s*auto;/s', $reset);
        $this->assertMatchesRegularExpression('/\\.form-control,[^{]+\\{[^}]*max-width:\\s*100%;[^}]*min-width:\\s*0;/s', $forms);
        $this->assertMatchesRegularExpression('/\\.card\\s*\\{[^}]*max-width:\\s*100%;[^}]*min-width:\\s*0;[^}]*width:\\s*100%;/s', $cards);
        $this->assertStringContainsString('overflow-x: auto;', $tables);
        $this->assertStringContainsString('-webkit-overflow-scrolling: touch;', $tables);
        $this->assertStringContainsString('max-height: calc(100dvh', $modal);
        $this->assertMatchesRegularExpression('/\\.modal__body\\s*\\{[^}]*overflow-y:\\s*auto;/s', $modal);
        $this->assertStringContainsString(
            '.modal__dialog > form:not(.modal__actions):not(.mobile-filter-sheet__body)',
            $modal,
        );
        $this->assertDoesNotMatchRegularExpression(
            '/\\.modal__dialog\\s*>\\s*form\\s*\\{/',
            $modal,
        );
    }

    public function test_every_application_table_is_inside_a_responsive_wrapper(): void
    {
        $views = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(resource_path('views/pages')),
        );
        $tableViews = [];
        $wrapperPattern = '/(?:table-wrapper|table-responsive|dashboard-table-wrapper|report-table-wrap|expense-table-wrapper|expense-category-table-wrapper)/';

        foreach ($views as $view) {
            if (! $view->isFile() || $view->getExtension() !== 'php') {
                continue;
            }

            $source = (string) file_get_contents($view->getPathname());

            if (! str_contains($source, '<table')) {
                continue;
            }

            $tableViews[] = $view->getPathname();
            $this->assertMatchesRegularExpression(
                $wrapperPattern,
                $source,
                "Tabel pada {$view->getPathname()} belum memiliki wrapper responsive.",
            );
        }

        $this->assertNotEmpty($tableViews);
    }

    public function test_dashboard_charts_keep_chart_js_responsive_mode(): void
    {
        foreach ([
            'assets/js/pages/dashboard-owner/dashboard-charts.js',
            'assets/js/pages/dashboard-admin/dashboard-charts.js',
        ] as $asset) {
            $javascript = (string) file_get_contents(public_path($asset));

            $this->assertStringContainsString('responsive: true', $javascript);
            $this->assertStringContainsString('maintainAspectRatio: false', $javascript);
        }
    }

    private function responsiveCss(): string
    {
        return (string) file_get_contents(public_path('assets/css/responsive.css'));
    }
}
