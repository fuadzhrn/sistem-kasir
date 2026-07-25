<?php

namespace Tests\Feature\Unit;

use App\Models\Unit;
use Database\Seeders\UnitSeeder;
use Tests\Feature\MasterDataTestCase;

class UnitSeederTest extends MasterDataTestCase
{
    public function test_seeder_is_idempotent_preserves_custom_and_inactive_units(): void
    {
        $this->seed(UnitSeeder::class);
        $this->assertSame(13, Unit::query()->count());
        $liter = Unit::query()->where('slug', 'liter')->firstOrFail();
        $liter->update(['name' => 'Liter Pengguna', 'is_active' => false]);
        Unit::factory()->create(['name' => 'Custom', 'slug' => 'custom']);

        $this->seed(UnitSeeder::class);
        $this->assertSame(14, Unit::query()->count());
        $this->assertDatabaseHas('units', ['id' => $liter->id, 'name' => 'Liter Pengguna', 'is_active' => false]);
        $this->assertDatabaseHas('units', ['slug' => 'unit', 'symbol' => 'unit']);
        $this->assertDatabaseHas('units', ['slug' => 'custom']);
    }
}
