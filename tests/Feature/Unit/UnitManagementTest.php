<?php

namespace Tests\Feature\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Tests\Feature\MasterDataTestCase;

class UnitManagementTest extends MasterDataTestCase
{
    public function test_owner_can_create_edit_status_and_delete_unused_unit(): void
    {
        $owner = $this->createUser('owner');
        $this->actingAs($owner)->post(route('units.store'), ['name' => '  Kilo   Liter ', 'symbol' => ' kL '])
            ->assertRedirect();
        $unit = Unit::query()->where('slug', 'kilo-liter')->firstOrFail();
        $this->assertSame('Kilo Liter', $unit->name);
        $this->assertSame('kL', $unit->symbol);

        $this->actingAs($owner)->put(route('units.update', $unit), ['name' => 'Kiloliter', 'symbol' => 'kL'])
            ->assertRedirect(route('units.show', $unit));
        $this->actingAs($owner)->patch(route('units.status.update', $unit), ['is_active' => false])
            ->assertRedirect();
        $this->assertFalse($unit->fresh()->is_active);
        $this->actingAs($owner)->delete(route('units.destroy', $unit))->assertRedirect(route('units.index'));
        $this->assertModelMissing($unit);
    }

    public function test_unit_validation_and_used_delete_protection(): void
    {
        $owner = $this->createUser('owner');
        $unit = Unit::factory()->create(['name' => 'Liter', 'slug' => 'liter']);
        $this->actingAs($owner)->post(route('units.store'), ['name' => 'LITER'])->assertSessionHasErrors('name');
        $this->actingAs($owner)->post(route('units.store'), ['name' => '', 'symbol' => str_repeat('x', 21)])
            ->assertSessionHasErrors(['name', 'symbol']);
        Product::factory()->create(['category_id' => Category::factory(), 'unit_id' => $unit]);
        $this->actingAs($owner)->delete(route('units.destroy', $unit))->assertSessionHasErrors('delete');
        $this->assertModelExists($unit);
    }
}
