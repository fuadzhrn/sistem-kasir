<?php

namespace Tests\Feature\StoreSetting;

class BusinessSettingTest extends StoreSettingTestCase
{
    public function test_business_values_normalize_quantity_and_rupiah(): void
    {
        $this->actingAs($this->createUser('owner'))->put(
            route('settings.store.business.update'),
            ['default_minimum_stock' => '2,500', 'maximum_cashier_discount' => 'Rp10.000'],
        )->assertRedirect()->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('settings', [
            'key' => 'business.default_minimum_stock',
            'value' => '2.500',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'business.maximum_cashier_discount',
            'value' => '10000.00',
        ]);
    }

    public function test_negative_and_excess_precision_values_are_rejected(): void
    {
        $this->actingAs($this->createUser('owner'))->put(
            route('settings.store.business.update'),
            ['default_minimum_stock' => '-1.0001', 'maximum_cashier_discount' => '-100'],
        )->assertSessionHasErrors(['default_minimum_stock', 'maximum_cashier_discount']);
    }
}
