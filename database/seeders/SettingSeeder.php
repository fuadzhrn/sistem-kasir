<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => 'store.name',
                'value' => config('app.name', 'Toko'),
                'type' => 'string',
                'group' => 'store',
            ],
            ['key' => 'store.logo_path', 'value' => null, 'type' => 'string', 'group' => 'store'],
            ['key' => 'store.address', 'value' => null, 'type' => 'string', 'group' => 'store'],
            ['key' => 'store.phone', 'value' => null, 'type' => 'string', 'group' => 'store'],
            [
                'key' => 'receipt.footer_message',
                'value' => 'Terima kasih telah berbelanja.',
                'type' => 'string',
                'group' => 'receipt',
            ],
            ['key' => 'receipt.additional_information', 'value' => null, 'type' => 'string', 'group' => 'receipt'],
            ['key' => 'receipt.default_paper_width', 'value' => '80', 'type' => 'integer', 'group' => 'receipt'],
            ['key' => 'receipt.show_logo', 'value' => '1', 'type' => 'boolean', 'group' => 'receipt'],
            ['key' => 'receipt.show_store_address', 'value' => '1', 'type' => 'boolean', 'group' => 'receipt'],
            ['key' => 'receipt.show_store_phone', 'value' => '1', 'type' => 'boolean', 'group' => 'receipt'],
            ['key' => 'receipt.show_branch_address', 'value' => '1', 'type' => 'boolean', 'group' => 'receipt'],
            ['key' => 'receipt.show_branch_phone', 'value' => '1', 'type' => 'boolean', 'group' => 'receipt'],
            ['key' => 'receipt.show_product_code', 'value' => '0', 'type' => 'boolean', 'group' => 'receipt'],
            ['key' => 'receipt.show_transaction_notes', 'value' => '0', 'type' => 'boolean', 'group' => 'receipt'],
            ['key' => 'receipt.show_copy_label', 'value' => '1', 'type' => 'boolean', 'group' => 'receipt'],
            ['key' => 'receipt.number_format', 'value' => 'branch_date_sequence', 'type' => 'string', 'group' => 'receipt'],
            ['key' => 'receipt.number_prefix', 'value' => null, 'type' => 'string', 'group' => 'receipt'],
            ['key' => 'receipt.number_separator', 'value' => '-', 'type' => 'string', 'group' => 'receipt'],
            ['key' => 'receipt.sequence_digits', 'value' => '4', 'type' => 'integer', 'group' => 'receipt'],
            [
                'key' => 'business.default_minimum_stock',
                'value' => '0.000',
                'type' => 'decimal',
                'group' => 'business',
            ],
            [
                'key' => 'business.maximum_cashier_discount',
                'value' => '0.00',
                'type' => 'decimal',
                'group' => 'business',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::query()->firstOrCreate(
                ['key' => $setting['key']],
                [...$setting, 'is_public' => false],
            );
        }
    }
}
