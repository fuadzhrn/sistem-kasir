<?php

namespace Tests\Feature\Unit;

use App\Models\Unit;
use Tests\Feature\MasterDataTestCase;

class UnitAuthorizationTest extends MasterDataTestCase
{
    public function test_admin_can_manage_but_cannot_delete_unit(): void
    {
        $admin = $this->createUser('admin');
        $unit = Unit::factory()->create();
        $this->actingAs($admin)->post(route('units.store'), [
            'name' => 'Satuan Admin',
            'symbol' => 'SA',
        ])->assertRedirect();
        $this->assertDatabaseHas('units', ['slug' => 'satuan-admin']);
        $this->actingAs($admin)->get(route('units.edit', $unit))->assertOk();
        $this->actingAs($admin)->put(route('units.update', $unit), ['name' => 'Nama Baru', 'symbol' => 'NB'])
            ->assertRedirect();
        $this->actingAs($admin)->patch(route('units.status.update', $unit), ['is_active' => false])
            ->assertRedirect();
        $this->actingAs($admin)->delete(route('units.destroy', $unit))->assertForbidden();
    }

    public function test_cashier_cannot_manipulate_unit_routes(): void
    {
        $cashier = $this->createUser('cashier');
        $unit = Unit::factory()->create();
        $this->actingAs($cashier)->get(route('units.show', $unit))->assertForbidden();
        $this->actingAs($cashier)->delete(route('units.destroy', $unit))->assertForbidden();
    }
}
