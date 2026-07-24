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
                'key' => 'receipt_width',
                'value' => 'auto',
                'type' => 'string',
                'group' => 'receipt',
            ],
            [
                'key' => 'receipt_message',
                'value' => 'Terima kasih telah berbelanja.',
                'type' => 'string',
                'group' => 'receipt',
            ],
            [
                'key' => 'default_minimum_stock',
                'value' => '0',
                'type' => 'decimal',
                'group' => 'inventory',
            ],
            [
                'key' => 'maximum_cashier_discount',
                'value' => '0',
                'type' => 'decimal',
                'group' => 'sales',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::query()->updateOrCreate(
                ['key' => $setting['key']],
                [...$setting, 'is_public' => false],
            );
        }
    }
}
