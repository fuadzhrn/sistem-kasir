<?php

namespace Tests\Feature\StoreSetting;

use App\Models\Setting;

class StoreGeneralSettingTest extends StoreSettingTestCase
{
    public function test_owner_updates_trimmed_general_information_and_actor(): void
    {
        $owner = $this->createUser('owner');

        $this->actingAs($owner)->put(route('settings.store.general.update'), [
            'store_name' => '  Toko   Agro Makmur  ',
            'store_address' => '  Jalan Tani 10  ',
            'store_phone' => '+62 812-3456',
            'key' => 'app.key',
        ])->assertRedirect()->assertSessionHas('status');

        $this->assertDatabaseHas('settings', [
            'key' => 'store.name',
            'value' => 'Toko Agro Makmur',
            'updated_by' => $owner->id,
        ]);
        $this->assertDatabaseHas('settings', ['key' => 'store.phone', 'value' => '+62 812-3456']);
        $this->assertDatabaseMissing('settings', ['key' => 'app.key']);
    }

    public function test_name_validation_rejects_blank_or_too_long_without_partial_write(): void
    {
        $owner = $this->createUser('owner');
        $original = Setting::query()->where('key', 'store.name')->value('value');

        $this->actingAs($owner)->from(route('settings.store.index'))
            ->put(route('settings.store.general.update'), [
                'store_name' => '   ',
                'store_address' => 'Tidak boleh tersimpan',
            ])
            ->assertRedirect(route('settings.store.index'))
            ->assertSessionHasErrors('store_name');

        $this->assertSame($original, Setting::query()->where('key', 'store.name')->value('value'));
        $this->assertNotSame('Tidak boleh tersimpan', Setting::query()->where('key', 'store.address')->value('value'));
    }
}
