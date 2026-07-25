<?php

namespace Tests\Feature\PaymentMethod;

use App\Models\PaymentMethod;
use Tests\Feature\MasterDataTestCase;

class PaymentMethodAuthorizationTest extends MasterDataTestCase
{
    public function test_admin_is_read_only_for_payment_methods(): void
    {
        $admin = $this->createUser('admin');
        $method = PaymentMethod::factory()->create();
        $this->actingAs($admin)->get(route('payment-methods.index'))->assertOk();
        $this->actingAs($admin)->get(route('payment-methods.show', $method))->assertOk();
        $this->actingAs($admin)->get(route('payment-methods.create'))->assertForbidden();
        $this->actingAs($admin)->get(route('payment-methods.edit', $method))->assertForbidden();
        $this->actingAs($admin)->patch(route('payment-methods.status.update', $method), ['is_active' => false])
            ->assertForbidden();
        $this->actingAs($admin)->delete(route('payment-methods.destroy', $method))->assertForbidden();
    }

    public function test_cashier_direct_url_manipulation_is_denied(): void
    {
        $cashier = $this->createUser('cashier');
        $method = PaymentMethod::factory()->create();
        $this->actingAs($cashier)->get(route('payment-methods.show', $method))->assertForbidden();
        $this->actingAs($cashier)->put(route('payment-methods.update', $method), [
            'code' => 'MANIPULASI',
            'name' => 'Manipulasi',
            'type' => 'cash',
            'sort_order' => 0,
        ])->assertForbidden();
    }
}
