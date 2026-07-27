<?php

namespace Tests\Feature\StoreSetting;

class ReceiptNumberSettingTest extends StoreSettingTestCase
{
    public function test_prefix_format_is_normalized_and_saved_from_whitelist(): void
    {
        $this->actingAs($this->createUser('owner'))->put(
            route('settings.store.receipt.update'),
            $this->receiptPayload([
                'number_format' => 'prefix_branch_date_sequence_slash',
                'number_prefix' => 'inv9',
                'number_separator' => '/',
                'sequence_digits' => 5,
            ]),
        )->assertRedirect()->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('settings', ['key' => 'receipt.number_prefix', 'value' => 'INV9']);
        $this->assertDatabaseHas('settings', ['key' => 'receipt.sequence_digits', 'value' => '5']);
    }

    public function test_arbitrary_format_prefix_and_mismatched_separator_are_rejected(): void
    {
        $owner = $this->createUser('owner');

        $this->actingAs($owner)->put(route('settings.store.receipt.update'), $this->receiptPayload([
            'number_format' => '{{ phpinfo() }}',
        ]))->assertSessionHasErrors('number_format');

        $this->actingAs($owner)->put(route('settings.store.receipt.update'), $this->receiptPayload([
            'number_format' => 'prefix_branch_date_sequence',
            'number_prefix' => 'INV TEST',
            'number_separator' => '/',
        ]))->assertSessionHasErrors(['number_prefix', 'number_separator']);
    }
}
