<?php

namespace Tests\Feature\StoreSetting;

class ReceiptSettingTest extends StoreSettingTestCase
{
    public function test_owner_updates_receipt_settings_as_typed_canonical_values(): void
    {
        $owner = $this->createUser('owner');

        $this->actingAs($owner)->put(
            route('settings.store.receipt.update'),
            $this->receiptPayload([
                'default_paper_width' => 58,
                'show_logo' => null,
                'receipt_additional_information' => "<script>alert('x')</script>\nBaris dua",
            ]),
        )->assertRedirect()->assertSessionHas('status');

        $this->assertDatabaseHas('settings', ['key' => 'receipt.default_paper_width', 'value' => '58']);
        $this->assertDatabaseHas('settings', ['key' => 'receipt.show_logo', 'value' => '0']);
        $this->assertDatabaseHas('settings', [
            'key' => 'receipt.additional_information',
            'value' => "<script>alert('x')</script>\nBaris dua",
        ]);
    }

    public function test_only_supported_paper_width_is_accepted(): void
    {
        $this->actingAs($this->createUser('owner'))->put(
            route('settings.store.receipt.update'),
            $this->receiptPayload(['default_paper_width' => 76]),
        )->assertSessionHasErrors('default_paper_width');
    }
}
