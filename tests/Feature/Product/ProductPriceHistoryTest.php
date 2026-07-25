<?php

namespace Tests\Feature\Product;

use App\Models\PriceHistory;
use App\Models\Product;
use Illuminate\Support\Facades\Route;

class ProductPriceHistoryTest extends ProductTestCase
{
    public function test_owner_price_change_creates_one_complete_history_from_database_values(): void
    {
        $owner = $this->createUser('owner');
        $product = Product::factory()->create([
            'purchase_price' => '50000.00',
            'selling_price' => '75000.00',
        ]);
        $payload = $this->productPayload($owner, $product->category, $product->unit, [
            'code' => $product->code,
            'barcode' => $product->barcode,
            'purchase_price' => '55000.00',
            'selling_price' => '82500.00',
            'price_change_reason' => 'Penyesuaian pemasok',
        ]);

        $this->actingAs($owner)->put(route('products.update', $product), $payload)->assertRedirect();
        $history = PriceHistory::query()->sole();
        $this->assertSame('50000.00', $history->old_purchase_price);
        $this->assertSame('55000.00', $history->new_purchase_price);
        $this->assertSame('75000.00', $history->old_selling_price);
        $this->assertSame('82500.00', $history->new_selling_price);
        $this->assertSame($owner->id, $history->changed_by);
        $this->assertSame('Penyesuaian pemasok', $history->reason);
        $this->assertNotNull($history->changed_at);
    }

    public function test_non_price_and_equivalent_price_updates_do_not_create_history(): void
    {
        $owner = $this->createUser('owner');
        $product = Product::factory()->create([
            'purchase_price' => '50000.00',
            'selling_price' => '75000.00',
        ]);
        $this->actingAs($owner)->put(route('products.update', $product), $this->productPayload(
            $owner,
            $product->category,
            $product->unit,
            [
                'code' => $product->code,
                'barcode' => $product->barcode,
                'name' => 'Nama Baru',
                'brand' => 'Merek Baru',
                'purchase_price' => '50000',
                'selling_price' => '75000.0',
            ],
        ))->assertRedirect();
        $this->assertSame(0, PriceHistory::query()->count());
    }

    public function test_admin_changes_selling_price_without_changing_or_receiving_purchase_prices(): void
    {
        $admin = $this->createUser('admin');
        $product = Product::factory()->create([
            'purchase_price' => '98765432.10',
            'selling_price' => '120000.00',
        ]);
        $this->actingAs($admin)->put(route('products.update', $product), $this->productPayload(
            $admin,
            $product->category,
            $product->unit,
            [
                'code' => $product->code,
                'barcode' => $product->barcode,
                'selling_price' => '125000.00',
            ],
        ))->assertRedirect();

        $this->assertSame('98765432.10', $product->fresh()->purchase_price);
        $history = PriceHistory::query()->sole();
        $this->assertSame($history->old_purchase_price, $history->new_purchase_price);

        $response = $this->actingAs($admin)->get(route('products.price-history.index', $product))->assertOk();
        $response
            ->assertDontSeeText('Harga Beli')
            ->assertDontSee('old_purchase_price')
            ->assertDontSee('new_purchase_price')
            ->assertDontSee('98.765.432');
        $response->assertViewHas('priceHistories', function ($histories): bool {
            $attributes = $histories->first()->getAttributes();

            return ! array_key_exists('old_purchase_price', $attributes)
                && ! array_key_exists('new_purchase_price', $attributes);
        });
    }

    public function test_history_is_read_only_latest_first_and_paginated(): void
    {
        $owner = $this->createUser('owner');
        $product = Product::factory()->create();
        PriceHistory::factory()->count(16)->create(['product_id' => $product, 'changed_by' => $owner]);
        $latest = PriceHistory::factory()->create([
            'product_id' => $product,
            'changed_by' => $owner,
            'reason' => 'Paling baru',
            'changed_at' => now()->addMinute(),
        ]);

        $this->actingAs($owner)->get(route('products.price-history.index', $product))
            ->assertOk()
            ->assertViewHas('priceHistories', fn ($items) => $items->count() === 15
                && $items->total() === 17
                && $items->first()->is($latest));
        $this->assertFalse(Route::has('price-histories.edit'));
        $this->assertFalse(Route::has('price-histories.destroy'));
    }
}
