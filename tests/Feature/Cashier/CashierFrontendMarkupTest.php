<?php

namespace Tests\Feature\Cashier;

class CashierFrontendMarkupTest extends CashierTestCase
{
    public function test_desktop_product_cart_and_payment_markup_is_complete(): void
    {
        $branch = $this->createBranch('UI');
        $owner = $this->createUser('owner');
        $this->createProduct();
        $this->createPaymentMethod();

        $this->actingAs($owner)->get(route('cashier.index', ['branch_id' => $branch->id]))
            ->assertOk()
            ->assertSee('class="cashier-workspace"', false)
            ->assertSee('class="cashier-products-panel', false)
            ->assertSee('class="cashier-cart-panel', false)
            ->assertSee('id="cashier-product-search"', false)
            ->assertSee('data-category-id', false)
            ->assertSee('data-product-loading', false)
            ->assertSee('data-product-empty', false)
            ->assertSee('data-cart-empty', false)
            ->assertSee('data-payment-discount', false)
            ->assertSee('data-payment-method', false)
            ->assertSee('data-amount-received', false)
            ->assertSee('data-payment-change', false)
            ->assertSee('data-summary-total', false)
            ->assertSee('Bayar &amp; Cetak', false)
            ->assertSee('Bayar Tanpa Cetak');
    }

    public function test_mobile_tabs_cart_bar_and_aria_markup_is_complete(): void
    {
        $branch = $this->createBranch('MOB');
        $cashier = $this->createUser('cashier', $branch);

        $this->actingAs($cashier)->get(route('cashier.index'))
            ->assertOk()
            ->assertSee('role="tablist"', false)
            ->assertSee('role="tab"', false)
            ->assertSee('role="tabpanel"', false)
            ->assertSee('aria-selected="true"', false)
            ->assertSee('aria-controls="cashier-products-panel"', false)
            ->assertSee('aria-controls="cashier-cart-panel"', false)
            ->assertSee('data-mobile-cart-bar', false)
            ->assertSee('Lihat Keranjang');
    }

    public function test_modals_assets_viewport_and_module_script_are_present_without_inline_handlers(): void
    {
        $branch = $this->createBranch('ASSET');
        $owner = $this->createUser('owner');
        $content = $this->actingAs($owner)->get(route('cashier.index', ['branch_id' => $branch->id]))
            ->assertOk()
            ->assertSee('id="cashier-payment-preview-modal"', false)
            ->assertSee('id="cashier-clear-cart-modal"', false)
            ->assertSee('id="cashier-branch-change-modal"', false)
            ->assertSee('assets/css/pages/cashier.css')
            ->assertSee('assets/css/pages/cashier-responsive.css')
            ->assertSee('assets/js/pages/cashier/index.js')
            ->assertSee('type="module"', false)
            ->assertSee('name="viewport"', false)
            ->assertSee('Mode Desain Kasir')
            ->getContent();

        $this->assertStringNotContainsString('onclick=', $content);
        $this->assertStringNotContainsString('window.print', $content);
        $this->assertStringNotContainsString('action="/sales"', $content);
    }

    public function test_all_required_view_sections_and_javascript_modules_are_nonempty(): void
    {
        $sections = [
            'cashier-header', 'simulation-alert', 'branch-selector', 'product-panel',
            'product-filters', 'product-grid', 'product-card-template', 'product-loading',
            'product-empty-state', 'cart-panel', 'cart-items', 'cart-item-template',
            'cart-empty-state', 'cart-summary', 'payment-form', 'mobile-tabs',
            'mobile-cart-bar', 'payment-preview-modal', 'branch-change-modal', 'clear-cart-modal',
        ];
        $modules = [
            'index', 'product-browser', 'cart-store', 'cart-renderer',
            'payment-calculator', 'payment-form', 'mobile-tabs',
            'branch-switcher', 'cashier-utils',
        ];

        foreach ($sections as $section) {
            $path = resource_path('views/pages/cashier/sections/'.$section.'.blade.php');
            $this->assertFileExists($path);
            $this->assertNotSame('', trim((string) file_get_contents($path)));
        }

        foreach ($modules as $module) {
            $path = public_path('assets/js/pages/cashier/'.$module.'.js');
            $this->assertFileExists($path);
            $this->assertNotSame('', trim((string) file_get_contents($path)));
        }
    }

    public function test_frontend_sources_keep_cart_branch_scoped_and_mobile_rules_local_to_cashier(): void
    {
        $cartStore = file_get_contents(public_path('assets/js/pages/cashier/cart-store.js'));
        $productBrowser = file_get_contents(public_path('assets/js/pages/cashier/product-browser.js'));
        $cashierScripts = collect(glob(public_path('assets/js/pages/cashier/*.js')))
            ->map(fn (string $path): string => (string) file_get_contents($path))
            ->implode("\n");
        $responsiveCss = file_get_contents(public_path('assets/css/pages/cashier-responsive.css'));

        $this->assertStringContainsString('window.sessionStorage', $cartStore);
        $this->assertStringContainsString("'_branch_' + branchId", $cartStore);
        $this->assertStringContainsString('await store.revalidate', file_get_contents(
            public_path('assets/js/pages/cashier/index.js'),
        ));
        $this->assertStringContainsString('textContent = product.name', $productBrowser);
        $this->assertStringNotContainsString('localStorage', $cashierScripts);
        $this->assertStringNotContainsString('window.print', $cashierScripts);
        $this->assertStringNotContainsString('innerHTML', $cashierScripts);
        $this->assertStringNotContainsString('method: \'POST\'', $cashierScripts);
        $this->assertStringContainsString('@media (max-width: 768px)', $responsiveCss);
        $this->assertStringContainsString('env(safe-area-inset-bottom', $responsiveCss);
        $this->assertStringContainsString('min-height: 44px', $responsiveCss);
        $this->assertStringContainsString('.cashier-', $responsiveCss);
    }
}
