<?php

namespace Tests\Feature\PaymentMethod;

use App\Models\PaymentMethod;
use Tests\Feature\MasterDataTestCase;

class PaymentMethodIndexTest extends MasterDataTestCase
{
    public function test_owner_and_admin_can_search_filter_and_paginate_payment_methods(): void
    {
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin');
        PaymentMethod::factory()->count(16)->create();
        PaymentMethod::factory()->create([
            'code' => 'SPECIAL',
            'name' => 'Transfer Khusus',
            'type' => 'non_cash',
            'is_active' => false,
            'sort_order' => 99,
        ]);

        $this->actingAs($owner)->get(route('payment-methods.index'))->assertOk()
            ->assertViewHas('paymentMethods', fn ($items) => $items->count() === 15 && $items->total() === 17);
        $this->actingAs($admin)->get(route('payment-methods.index', [
            'search' => 'SPECIAL',
            'status' => 'inactive',
            'type' => 'non_cash',
        ]))->assertOk()->assertSeeText('Transfer Khusus')->assertDontSeeText('Tambah Metode Pembayaran');
    }

    public function test_cashier_is_denied_from_payment_method_administration(): void
    {
        $this->actingAs($this->createUser('cashier'))->get(route('payment-methods.index'))->assertForbidden();
    }
}
