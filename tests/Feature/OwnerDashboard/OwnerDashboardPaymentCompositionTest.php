<?php

namespace Tests\Feature\OwnerDashboard;

use App\Models\Sale;

class OwnerDashboardPaymentCompositionTest extends OwnerDashboardTestCase
{
    public function test_payment_composition_uses_snapshot_name_and_safe_percentages(): void
    {
        $owner = $this->createUser('owner');
        $branch = $this->createBranch('PAY');

        foreach ([
            ['Tunai', '600000.00'],
            ['QRIS', '300000.00'],
            ['Transfer', '100000.00'],
        ] as [$method, $total]) {
            $created = $this->createSale($branch, $owner, [
                'payment_method_name' => $method,
                'total' => $total,
                'subtotal' => $total,
                'total_cost' => '0.00',
            ]);
            $created['payment']->update(['name' => 'Nama Master Diubah']);
        }
        $this->createSale($branch, $owner, [
            'payment_method_name' => 'Tunai',
            'total' => '990000.00',
            'status' => Sale::STATUS_VOIDED,
        ]);

        $this->getDashboardData($owner)
            ->assertOk()
            ->assertJsonPath('data.charts.payment_composition.labels', ['Tunai', 'QRIS', 'Transfer'])
            ->assertJsonPath('data.charts.payment_composition.values', [600000, 300000, 100000])
            ->assertJsonPath('data.charts.payment_composition.percentages', [60, 30, 10])
            ->assertJsonPath('data.charts.payment_composition.empty', false);
    }

    public function test_empty_payment_data_has_no_nan_or_division_by_zero(): void
    {
        $owner = $this->createUser('owner');
        $response = $this->getDashboardData($owner)->assertOk();

        $response->assertJsonPath('data.charts.payment_composition.values', [])
            ->assertJsonPath('data.charts.payment_composition.percentages', [])
            ->assertJsonPath('data.charts.payment_composition.empty', true);
        $this->assertStringNotContainsString('NaN', $response->getContent());
    }
}
