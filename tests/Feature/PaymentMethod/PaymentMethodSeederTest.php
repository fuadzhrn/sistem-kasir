<?php

namespace Tests\Feature\PaymentMethod;

use App\Models\PaymentMethod;
use Database\Seeders\PaymentMethodSeeder;
use Tests\Feature\MasterDataTestCase;

class PaymentMethodSeederTest extends MasterDataTestCase
{
    public function test_seeder_is_idempotent_and_preserves_user_changes(): void
    {
        $this->seed(PaymentMethodSeeder::class);
        $this->assertSame(3, PaymentMethod::query()->count());
        $cash = PaymentMethod::query()->where('code', 'CASH')->firstOrFail();
        $cash->update(['name' => 'Tunai Kustom', 'is_active' => false, 'sort_order' => 20]);
        PaymentMethod::factory()->create(['code' => 'CUSTOM']);

        $this->seed(PaymentMethodSeeder::class);
        $this->assertSame(4, PaymentMethod::query()->count());
        $this->assertDatabaseHas('payment_methods', [
            'id' => $cash->id,
            'name' => 'Tunai Kustom',
            'is_active' => false,
            'sort_order' => 20,
        ]);
        foreach (['CASH', 'TRANSFER', 'QRIS', 'CUSTOM'] as $code) {
            $this->assertDatabaseHas('payment_methods', ['code' => $code]);
        }
    }
}
