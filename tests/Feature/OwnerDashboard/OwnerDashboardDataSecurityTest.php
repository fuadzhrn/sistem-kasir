<?php

namespace Tests\Feature\OwnerDashboard;

use App\Models\ActivityLog;
use App\Models\BranchStock;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleItem;

class OwnerDashboardDataSecurityTest extends OwnerDashboardTestCase
{
    public function test_json_omits_sensitive_fields_and_database_content_is_safely_encoded(): void
    {
        $owner = $this->createUser('owner');
        $branch = $this->createBranch('SEC', ['name' => '<script>cabang</script>']);
        $product = $this->createProduct('SEC-X', ['name' => '<script>produk</script>']);
        $this->createSale($branch, $owner, [], $product);
        $this->createExpense($branch, $owner, Expense::STATUS_APPROVED, [
            'description' => '<script>pengeluaran</script>',
        ]);

        $content = $this->getDashboardData($owner)->assertOk()->getContent();

        foreach ([
            'password',
            'checkout_token',
            'session_id',
            'csrf-token',
            'APP_KEY',
            'vendor\\laravel',
            'select *',
            'Stack trace',
        ] as $sensitiveText) {
            $this->assertStringNotContainsString($sensitiveText, $content);
        }
        $this->assertStringNotContainsString('<script>', $content);
    }

    public function test_dashboard_requests_are_read_only_and_reject_malicious_filters(): void
    {
        $owner = $this->createUser('owner');
        $branch = $this->createBranch('RDO');
        $this->createSale($branch, $owner);
        $this->createExpense($branch, $owner);
        $counts = [
            Sale::class => Sale::query()->count(),
            SaleItem::class => SaleItem::query()->count(),
            Expense::class => Expense::query()->count(),
            BranchStock::class => BranchStock::query()->count(),
            ActivityLog::class => ActivityLog::query()->count(),
        ];

        $this->getDashboardData($owner)->assertOk();
        $this->actingAs($owner)->get(route('dashboard.owner'))->assertOk();

        foreach ($counts as $model => $count) {
            $this->assertSame($count, $model::query()->count());
        }

        $this->getDashboardData($owner, ['branch_id' => '1 OR 1=1'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('branch_id');
        $this->getDashboardData($owner, [
            'period' => 'custom',
            'date_from' => "' OR 1=1 --",
            'date_to' => '2026-07-25',
        ])->assertJsonValidationErrors('date_from');
    }
}
