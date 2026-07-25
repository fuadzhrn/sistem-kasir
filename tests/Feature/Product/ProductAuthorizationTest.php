<?php

namespace Tests\Feature\Product;

use App\Models\Product;
use Illuminate\Support\Facades\Route;

class ProductAuthorizationTest extends ProductTestCase
{
    public function test_cashier_cannot_access_any_product_administration_route(): void
    {
        $cashier = $this->createUser('cashier');
        $product = Product::factory()->create();
        $this->actingAs($cashier)->get(route('products.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('products.show', $product))->assertForbidden();
        $this->actingAs($cashier)->get(route('products.create'))->assertForbidden();
        $this->actingAs($cashier)->get(route('products.edit', $product))->assertForbidden();
        $this->actingAs($cashier)->get(route('products.price-history.index', $product))->assertForbidden();
        $this->actingAs($cashier)->patch(route('products.status.update', $product), ['is_active' => false])
            ->assertForbidden();
    }

    public function test_inactive_user_is_denied_and_product_has_no_hard_delete_route(): void
    {
        $inactiveOwner = $this->createUser('owner', ['is_active' => false]);
        $this->actingAs($inactiveOwner)->get(route('products.index'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('login');
        $this->assertFalse(Route::has('products.destroy'));
    }

    public function test_admin_cannot_manipulate_purchase_or_internal_fields_on_update(): void
    {
        $admin = $this->createUser('admin');
        $product = Product::factory()->create([
            'purchase_price' => '543210.00',
            'selling_price' => '700000.00',
            'is_active' => true,
        ]);
        $payload = $this->productPayload($admin, $product->category, $product->unit, [
            'code' => $product->code,
            'barcode' => $product->barcode,
            'selling_price' => '710000.00',
            'purchase_price' => '1.00',
            'is_active' => false,
            'created_by' => 999,
            'updated_by' => 999,
            'branch_id' => 999,
            'image_path' => 'products/attacker.php',
        ]);

        $this->actingAs($admin)->put(route('products.update', $product), $payload)
            ->assertSessionHasErrors('purchase_price');
        $fresh = $product->fresh();
        $this->assertSame('543210.00', $fresh->purchase_price);
        $this->assertSame('700000.00', $fresh->selling_price);
        $this->assertTrue($fresh->is_active);
        $this->assertNull($fresh->image_path);
    }

    public function test_admin_show_and_edit_html_never_contains_purchase_price(): void
    {
        $admin = $this->createUser('admin');
        $product = Product::factory()->create(['purchase_price' => '87654321.00']);

        foreach ([route('products.show', $product), route('products.edit', $product)] as $url) {
            $this->actingAs($admin)->get($url)->assertOk()
                ->assertDontSeeText('Harga Beli')
                ->assertDontSee('purchase_price')
                ->assertDontSee('87.654.321');
        }
    }
}
