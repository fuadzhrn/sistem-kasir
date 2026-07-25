<?php

namespace Tests\Feature\Cashier;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Route;

class CashierProductDataSecurityTest extends CashierTestCase
{
    public function test_product_response_contains_only_safe_allowlisted_fields(): void
    {
        $branch = $this->createBranch('SEC');
        $owner = $this->createUser('owner');
        $product = $this->createProduct([
            'purchase_price' => '987654.00',
            'selling_price' => '25000.00',
        ]);
        $this->createStock($branch, $product, '9.500', '876543.00');

        $response = $this->actingAs($owner)->getJson(route('cashier.products.index', [
            'branch_id' => $branch->id,
        ]))->assertOk();
        $item = $response->json('data.0');
        $this->assertSame([
            'id', 'code', 'barcode', 'name', 'brand', 'size', 'category_name',
            'unit_name', 'unit_symbol', 'selling_price', 'stock_quantity',
            'stock_status', 'stock_status_label', 'image_url', 'is_available', 'updated_at',
        ], array_keys($item));

        foreach ([
            'purchase_price', 'average_cost', 'unit_cost', 'hpp', 'profit',
            'created_by', 'updated_by', 'minimum_stock', 'branch_stock_id',
        ] as $forbidden) {
            $this->assertArrayNotHasKey($forbidden, $item);
        }
        $this->assertStringNotContainsString('987654', $response->getContent());
        $this->assertStringNotContainsString('876543', $response->getContent());
        $this->assertStringNotContainsString('D:\\', $response->getContent());
    }

    public function test_no_role_receives_purchase_or_average_cost_from_cashier_endpoint(): void
    {
        $branch = $this->createBranch('ROLE');
        $product = $this->createProduct(['purchase_price' => '765432.00']);
        $this->createStock($branch, $product, '5.000', '654321.00');
        $users = [
            $this->createUser('owner'),
            $this->createUser('admin', $branch),
            $this->createUser('cashier', $branch),
        ];

        foreach ($users as $user) {
            $content = $this->actingAs($user)->getJson(route('cashier.products.index', $this->endpointParams(
                $user,
                $branch,
            )))->assertOk()->getContent();
            $this->assertStringNotContainsString('765432', $content);
            $this->assertStringNotContainsString('654321', $content);
        }
    }

    public function test_cashier_html_does_not_embed_product_cost_or_product_records(): void
    {
        $branch = $this->createBranch('HTML');
        $cashier = $this->createUser('cashier', $branch);
        $product = $this->createProduct([
            'name' => '<script>window.compromised=true</script>',
            'purchase_price' => '918273.00',
        ]);
        $this->createStock($branch, $product, '5.000', '817263.00');

        $this->actingAs($cashier)->get(route('cashier.index'))
            ->assertOk()
            ->assertDontSee('918273')
            ->assertDontSee('817263')
            ->assertDontSee('window.compromised')
            ->assertDontSee('purchase_price')
            ->assertDontSee('average_cost');

        $json = $this->actingAs($cashier)->getJson(route('cashier.products.index'))->assertOk();
        $json->assertJsonPath('data.0.name', '<script>window.compromised=true</script>');
        $source = file_get_contents(public_path('assets/js/pages/cashier/product-browser.js'));
        $this->assertStringContainsString('.textContent = product.name', $source);
        $this->assertStringNotContainsString('innerHTML', $source);
        $this->assertStringNotContainsString('eval(', $source);
    }

    public function test_endpoint_is_read_only_rate_limited_and_database_never_changes(): void
    {
        $branch = $this->createBranch('RATE');
        $cashier = $this->createUser('cashier', $branch);
        $product = $this->createProduct();
        $stock = $this->createStock($branch, $product, '6.000');
        $before = [
            'sales' => Sale::query()->count(),
            'items' => SaleItem::query()->count(),
            'movements' => StockMovement::query()->count(),
        ];

        foreach (range(1, 90) as $requestNumber) {
            $this->actingAs($cashier)->getJson(route('cashier.products.index'))->assertOk();
        }
        $this->actingAs($cashier)->getJson(route('cashier.products.index'))->assertTooManyRequests();

        $this->assertSame(['GET', 'HEAD'], Route::getRoutes()->getByName('cashier.products.index')?->methods());
        $this->assertSame($before['sales'], Sale::query()->count());
        $this->assertSame($before['items'], SaleItem::query()->count());
        $this->assertSame($before['movements'], StockMovement::query()->count());
        $this->assertSame('6.000', $stock->refresh()->quantity);
    }
}
