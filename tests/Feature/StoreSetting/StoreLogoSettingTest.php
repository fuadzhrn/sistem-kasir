<?php

namespace Tests\Feature\StoreSetting;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StoreLogoSettingTest extends StoreSettingTestCase
{
    public function test_owner_uploads_random_relative_logo_and_can_remove_it(): void
    {
        Storage::fake('public');
        $owner = $this->createUser('owner');

        $this->actingAs($owner)->post(route('settings.store.logo.update'), [
            'logo' => UploadedFile::fake()->image('logo-asli.png', 300, 300)->size(100),
        ])->assertRedirect()->assertSessionHas('status');

        $path = (string) Setting::query()->where('key', 'store.logo_path')->value('value');
        $this->assertStringStartsWith('store/', $path);
        $this->assertStringNotContainsString('logo-asli', $path);
        $this->assertFalse(str_starts_with($path, '/'));
        Storage::disk('public')->assertExists($path);

        $this->actingAs($owner)->delete(route('settings.store.logo.destroy'))->assertRedirect();
        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseHas('settings', ['key' => 'store.logo_path', 'value' => null]);
    }

    public function test_svg_and_oversized_logo_are_rejected(): void
    {
        Storage::fake('public');
        $owner = $this->createUser('owner');

        $this->actingAs($owner)->post(route('settings.store.logo.update'), [
            'logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
        ])->assertSessionHasErrors('logo');

        $this->actingAs($owner)->post(route('settings.store.logo.update'), [
            'logo' => UploadedFile::fake()->image('besar.jpg', 300, 300)->size(2100),
        ])->assertSessionHasErrors('logo');
    }
}
