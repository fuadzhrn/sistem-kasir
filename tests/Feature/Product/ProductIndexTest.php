<?php

namespace Tests\Feature\Product;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;

class ProductIndexTest extends ProductTestCase
{
    public function test_owner_and_admin_can_view_search_filter_and_paginate_products(): void
    {
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin');
        Product::factory()->count(16)->create();
        $category = Category::factory()->create(['name' => 'Kategori Khusus']);
        $unit = Unit::factory()->create(['name' => 'Satuan Khusus']);
        Product::factory()->create([
            'category_id' => $category,
            'unit_id' => $unit,
            'code' => 'FILTER-001',
            'barcode' => '000099998888',
            'name' => 'Produk Filter Khusus',
            'brand' => 'Merek Unik',
            'size' => '777 ml',
            'is_active' => false,
        ]);

        $this->actingAs($owner)->get(route('products.index'))->assertOk()
            ->assertViewHas('products', fn ($items) => $items->count() === 15 && $items->total() === 17);

        foreach (['FILTER-001', '000099998888', 'Produk Filter', 'Merek Unik', '777 ml'] as $search) {
            $this->actingAs($admin)->get(route('products.index', ['search' => $search]))
                ->assertOk()->assertSeeText('Produk Filter Khusus');
        }

        $this->actingAs($admin)->get(route('products.index', [
            'category' => $category->id,
            'unit' => $unit->id,
            'status' => 'inactive',
        ]))->assertOk()->assertSeeText('Produk Filter Khusus');
    }

    public function test_admin_product_list_does_not_receive_or_render_purchase_price(): void
    {
        $admin = $this->createUser('admin');
        Product::factory()->create([
            'name' => 'Produk Rahasia',
            'purchase_price' => '43210987.65',
        ]);

        $response = $this->actingAs($admin)->get(route('products.index'))->assertOk();
        $response
            ->assertDontSeeText('Harga Beli')
            ->assertDontSee('purchase_price')
            ->assertDontSee('43.210.988');
        $response->assertViewHas('products', function ($products): bool {
            return ! array_key_exists('purchase_price', $products->first()->getAttributes())
                && $products->first()->relationLoaded('category')
                && $products->first()->relationLoaded('unit');
        });
    }

    public function test_owner_sees_purchase_price_but_guest_and_cashier_are_denied(): void
    {
        $owner = $this->createUser('owner');
        $cashier = $this->createUser('cashier');
        Product::factory()->create(['purchase_price' => '123456.00']);
        $this->actingAs($owner)->get(route('products.index'))->assertOk()->assertSeeText('Harga Beli');
        auth()->guard()->logout();
        $this->get(route('products.index'))->assertRedirect(route('login'));
        $this->actingAs($cashier)->get(route('products.index'))->assertForbidden();
    }
}
