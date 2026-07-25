<?php

namespace Tests\Feature\Unit;

use App\Models\Unit;
use Tests\Feature\MasterDataTestCase;

class UnitIndexTest extends MasterDataTestCase
{
    public function test_owner_and_admin_can_search_filter_and_paginate_units(): void
    {
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin');
        Unit::factory()->count(16)->create();
        Unit::factory()->create(['name' => 'Liter Khusus', 'symbol' => 'LK', 'slug' => 'liter-khusus', 'is_active' => false]);

        $this->actingAs($owner)->get(route('units.index'))->assertOk()
            ->assertViewHas('units', fn ($items) => $items->count() === 15 && $items->total() === 17);
        $this->actingAs($admin)->get(route('units.index', ['search' => 'LK', 'status' => 'inactive']))
            ->assertOk()->assertSeeText('Liter Khusus');
    }

    public function test_cashier_is_denied_from_unit_module(): void
    {
        $this->actingAs($this->createUser('cashier'))->get(route('units.index'))->assertForbidden();
    }
}
