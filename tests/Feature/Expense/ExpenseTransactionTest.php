<?php

namespace Tests\Feature\Expense;

use App\Models\BranchStock;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Services\Expense\ExpenseService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ExpenseTransactionTest extends ExpenseTestCase
{
    public function test_expense_workflow_does_not_change_stock_or_sales_tables(): void
    {
        $branch = $this->createBranch('EIN');
        $owner = $this->createUser('owner');
        $category = $this->createCategory();
        $before = [
            'branch_stocks' => BranchStock::query()->count(),
            'stock_movements' => StockMovement::query()->count(),
            'sales' => Sale::query()->count(),
            'sale_items' => SaleItem::query()->count(),
        ];

        $this->actingAs($owner)->post(route('expenses.store'), $this->payload($branch, $category));
        $expense = Expense::query()->firstOrFail();
        $this->actingAs($owner)->patch(route('expenses.approve', $expense))->assertRedirect();

        foreach ($before as $table => $count) {
            $this->assertDatabaseCount($table, $count);
        }
    }

    public function test_file_is_cleaned_up_when_database_transaction_fails(): void
    {
        Storage::fake('public');
        $inactiveBranch = $this->createBranch('ETX', ['is_active' => false]);
        $owner = $this->createUser('owner');
        $category = $this->createCategory();

        try {
            app(ExpenseService::class)->create(
                $inactiveBranch,
                $category,
                now()->toImmutable(),
                '100000',
                'Biaya yang harus gagal tersimpan',
                UploadedFile::fake()->image('bukti.png'),
                $owner,
            );
            $this->fail('Transaksi seharusnya gagal.');
        } catch (ModelNotFoundException) {
            $this->assertDatabaseCount('expenses', 0);
            $this->assertSame([], Storage::disk('public')->allFiles('expense-proofs'));
        }
    }

    public function test_critical_services_use_transactions_and_row_locks(): void
    {
        $service = file_get_contents(app_path('Services/Expense/ExpenseService.php'));
        $approval = file_get_contents(app_path('Services/Expense/ExpenseApprovalService.php'));
        $category = file_get_contents(app_path('Services/Expense/ExpenseCategoryService.php'));

        foreach ([$service, $approval, $category] as $source) {
            $this->assertStringContainsString('DB::transaction', $source);
            $this->assertStringContainsString('lockForUpdate', $source);
        }
    }
}
