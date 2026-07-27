<?php

namespace Tests\Feature\StoreSetting;

use App\Models\Setting;

class StoreSettingAuthorizationTest extends StoreSettingTestCase
{
    public function test_guest_is_redirected_and_non_owner_roles_are_forbidden(): void
    {
        $this->get(route('settings.store.index'))->assertRedirect(route('login'));
        $this->actingAs($this->createUser('admin'))->get(route('settings.store.index'))->assertForbidden();
        $this->actingAs($this->createUser('cashier'))->get(route('settings.store.index'))->assertForbidden();
    }

    public function test_inactive_owner_is_logged_out_before_settings_are_opened(): void
    {
        $this->actingAs($this->createUser('owner', ['is_active' => false]))
            ->get(route('settings.store.index'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_non_owner_cannot_mutate_settings_by_direct_url(): void
    {
        $original = Setting::query()->where('key', 'store.name')->value('value');

        $this->actingAs($this->createUser('admin'))->put(route('settings.store.general.update'), [
            'store_name' => 'Manipulasi',
        ])->assertForbidden();
        $this->actingAs($this->createUser('cashier'))->put(
            route('settings.store.business.update'),
            ['default_minimum_stock' => 9, 'maximum_cashier_discount' => 999999],
        )->assertForbidden();

        $this->assertSame($original, Setting::query()->where('key', 'store.name')->value('value'));
    }
}
