<?php

namespace Tests\Feature\PaymentMethod;

use App\Models\PaymentMethod;
use App\Models\Sale;
use Tests\Feature\MasterDataTestCase;

class PaymentMethodManagementTest extends MasterDataTestCase
{
    public function test_owner_can_create_edit_status_and_delete_unused_payment_method(): void
    {
        $owner = $this->createUser('owner');
        $this->actingAs($owner)->post(route('payment-methods.store'), [
            'code' => '  debit_card ',
            'name' => '  Kartu   Debit ',
            'type' => 'non_cash',
            'sort_order' => 4,
            'is_active' => false,
        ])->assertRedirect();
        $method = PaymentMethod::query()->where('code', 'DEBIT_CARD')->firstOrFail();
        $this->assertSame('Kartu Debit', $method->name);
        $this->assertTrue($method->is_active);

        $this->actingAs($owner)->put(route('payment-methods.update', $method), [
            'code' => 'debit',
            'name' => 'Debit',
            'type' => 'other',
            'sort_order' => 8,
        ])->assertRedirect(route('payment-methods.show', $method));
        $this->assertDatabaseHas('payment_methods', ['id' => $method->id, 'code' => 'DEBIT', 'sort_order' => 8]);

        $this->actingAs($owner)->patch(route('payment-methods.status.update', $method), ['is_active' => false])
            ->assertRedirect();
        $this->assertFalse($method->fresh()->is_active);
        $this->actingAs($owner)->delete(route('payment-methods.destroy', $method))
            ->assertRedirect(route('payment-methods.index'));
        $this->assertModelMissing($method);
    }

    public function test_payment_method_validations_are_enforced(): void
    {
        $owner = $this->createUser('owner');
        PaymentMethod::factory()->create(['code' => 'CASH', 'name' => 'Tunai']);
        $this->actingAs($owner)->post(route('payment-methods.store'), [
            'code' => 'cash',
            'name' => 'TUNAI',
            'type' => 'invalid',
            'sort_order' => -1,
        ])->assertSessionHasErrors(['code', 'name', 'type', 'sort_order']);
        $this->actingAs($owner)->post(route('payment-methods.store'), [
            'code' => '',
            'name' => '',
            'type' => 'cash',
            'sort_order' => 1,
        ])->assertSessionHasErrors(['code', 'name']);
    }

    public function test_used_payment_method_cannot_be_deleted_and_sale_snapshot_is_unchanged(): void
    {
        $owner = $this->createUser('owner');
        $method = PaymentMethod::factory()->create(['name' => 'Nama Lama']);
        $cashier = $this->createUser('cashier');
        $sale = Sale::factory()->create([
            'branch_id' => $cashier->branch_id,
            'cashier_id' => $cashier,
            'payment_method_id' => $method,
            'payment_method_name' => 'Nama Lama',
        ]);

        $this->actingAs($owner)->put(route('payment-methods.update', $method), [
            'code' => $method->code,
            'name' => 'Nama Baru',
            'type' => 'cash',
            'sort_order' => 1,
        ])->assertRedirect();
        $this->assertSame('Nama Lama', $sale->fresh()->payment_method_name);
        $this->actingAs($owner)->delete(route('payment-methods.destroy', $method))
            ->assertSessionHasErrors('delete');
        $this->assertModelExists($method);
    }
}
