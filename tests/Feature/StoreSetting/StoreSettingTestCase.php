<?php

namespace Tests\Feature\StoreSetting;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class StoreSettingTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingSeeder::class);
    }

    protected function createUser(string $role, array $attributes = []): User
    {
        $roleModel = Role::query()->firstOrCreate(
            ['slug' => $role],
            ['name' => ucfirst($role), 'is_active' => true],
        );

        return User::factory()->create([
            'role_id' => $roleModel,
            'branch_id' => $role === 'owner' ? null : Branch::factory()->create(),
            'is_active' => true,
            ...$attributes,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function receiptPayload(array $overrides = []): array
    {
        return [
            'receipt_footer_message' => 'Terima kasih.',
            'receipt_additional_information' => 'Simpan nota ini.',
            'default_paper_width' => 80,
            'show_logo' => '1',
            'show_store_address' => '1',
            'show_store_phone' => '1',
            'show_branch_address' => '1',
            'show_branch_phone' => '1',
            'show_product_code' => '0',
            'show_transaction_notes' => '0',
            'show_copy_label' => '1',
            'number_format' => 'branch_date_sequence',
            'number_prefix' => null,
            'number_separator' => '-',
            'sequence_digits' => 4,
            ...$overrides,
        ];
    }
}
