<?php

namespace Tests\Feature\StoreSetting;

use App\Models\Branch;
use App\Models\Setting;
use App\Services\Sale\SaleNumberService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleNumberSettingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_prefix_change_midday_does_not_reset_branch_daily_sequence(): void
    {
        $branch = Branch::factory()->create(['code' => 'UTM']);
        $date = Carbon::parse('2026-07-24 09:00:00');
        $service = app(SaleNumberService::class);

        $this->assertSame('UTM-20260724-0001', $service->generate($branch, $date));
        Setting::query()->updateOrCreate(['key' => 'receipt.number_format'], [
            'value' => 'prefix_branch_date_sequence',
            'type' => 'string',
            'group' => 'receipt',
        ]);
        Setting::query()->updateOrCreate(['key' => 'receipt.number_prefix'], [
            'value' => 'INV',
            'type' => 'string',
            'group' => 'receipt',
        ]);

        $this->assertSame('INV-UTM-20260724-0002', app(SaleNumberService::class)->generate($branch, $date));
        $this->assertDatabaseHas('sale_number_sequences', [
            'branch_id' => $branch->id,
            'sequence_date' => '2026-07-24 00:00:00',
            'last_number' => 2,
        ]);
    }
}
