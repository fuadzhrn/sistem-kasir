<?php

namespace App\Services\Expense;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Support\Format\Rupiah;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class ExpenseService
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function create(
        Branch $branch,
        ExpenseCategory $category,
        CarbonInterface $expenseDate,
        string $amount,
        string $description,
        ?UploadedFile $proof,
        User $actor,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): Expense {
        $newProof = $this->storeProof($proof);

        try {
            return DB::transaction(function () use (
                $branch,
                $category,
                $expenseDate,
                $amount,
                $description,
                $newProof,
                $actor,
                $ipAddress,
                $userAgent,
            ): Expense {
                $activeBranch = Branch::query()->whereKey($branch->getKey())->where('is_active', true)->firstOrFail();
                $activeCategory = ExpenseCategory::query()->whereKey($category->getKey())->where('is_active', true)->firstOrFail();
                $expense = Expense::query()->create([
                    'branch_id' => $activeBranch->getKey(),
                    'expense_category_id' => $activeCategory->getKey(),
                    'expense_date' => $expenseDate->toDateString(),
                    'amount' => $amount,
                    'description' => $description,
                    'proof_file' => $newProof,
                    'status' => Expense::STATUS_PENDING,
                    'created_by' => $actor->getKey(),
                    'updated_by' => null,
                    'approved_by' => null,
                    'approved_at' => null,
                    'rejected_by' => null,
                    'rejected_at' => null,
                    'rejection_reason' => null,
                ]);
                $this->log(
                    $expense,
                    $actor,
                    'expense_created',
                    'Pengeluaran sebesar '.Rupiah::format($amount).' dicatat untuk '.$activeBranch->name.'.',
                    $ipAddress,
                    $userAgent,
                );

                return $expense;
            });
        } catch (Throwable $exception) {
            $this->deleteProof($newProof);
            throw $exception;
        }
    }

    public function update(
        Expense $expense,
        ExpenseCategory $category,
        CarbonInterface $expenseDate,
        string $amount,
        string $description,
        ?UploadedFile $proof,
        User $actor,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): Expense {
        $newProof = $this->storeProof($proof);
        $oldProof = null;

        try {
            $updated = DB::transaction(function () use (
                $expense,
                $category,
                $expenseDate,
                $amount,
                $description,
                $newProof,
                $actor,
                $ipAddress,
                $userAgent,
                &$oldProof,
            ): Expense {
                $locked = Expense::query()->lockForUpdate()->findOrFail($expense->getKey());
                $this->ensurePending($locked);
                $validCategory = ExpenseCategory::query()
                    ->whereKey($category->getKey())
                    ->where(fn ($query) => $query
                        ->where('is_active', true)
                        ->orWhere('id', $locked->expense_category_id))
                    ->firstOrFail();
                $oldProof = $locked->proof_file;
                $locked->update([
                    'expense_category_id' => $validCategory->getKey(),
                    'expense_date' => $expenseDate->toDateString(),
                    'amount' => $amount,
                    'description' => $description,
                    'proof_file' => $newProof ?? $oldProof,
                    'updated_by' => $actor->getKey(),
                ]);
                $this->log($locked, $actor, 'expense_updated', 'Pengeluaran pending diperbarui.', $ipAddress, $userAgent);

                return $locked->refresh();
            });
        } catch (Throwable $exception) {
            $this->deleteProof($newProof);
            throw $exception;
        }

        if ($newProof !== null && $oldProof !== null && $oldProof !== $newProof) {
            $this->deleteProof($oldProof);
        }

        return $updated;
    }

    public function removeProof(
        Expense $expense,
        User $actor,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): Expense {
        $oldProof = null;
        $updated = DB::transaction(function () use (
            $expense,
            $actor,
            $ipAddress,
            $userAgent,
            &$oldProof,
        ): Expense {
            $locked = Expense::query()->lockForUpdate()->findOrFail($expense->getKey());
            $this->ensurePending($locked);
            $oldProof = $locked->proof_file;
            $locked->update([
                'proof_file' => null,
                'updated_by' => $actor->getKey(),
            ]);
            $this->log($locked, $actor, 'expense_proof_removed', 'Bukti pengeluaran dihapus.', $ipAddress, $userAgent);

            return $locked->refresh();
        });
        $this->deleteProof($oldProof);

        return $updated;
    }

    private function storeProof(?UploadedFile $proof): ?string
    {
        if ($proof === null) {
            return null;
        }

        $path = $proof->store('expense-proofs', 'public');

        if (! is_string($path) || $path === '') {
            throw ValidationException::withMessages([
                'proof' => 'Bukti tidak dapat disimpan. Tidak ada perubahan yang diterapkan.',
            ]);
        }

        return $path;
    }

    private function deleteProof(?string $path): void
    {
        if ($path !== null && str_starts_with($path, 'expense-proofs/')) {
            Storage::disk('public')->delete($path);
        }
    }

    private function ensurePending(Expense $expense): void
    {
        if (! $expense->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'Pengeluaran tidak dapat diedit karena sudah diproses.',
            ]);
        }
    }

    private function log(
        Expense $expense,
        User $actor,
        string $action,
        string $description,
        ?string $ipAddress,
        ?string $userAgent,
    ): void {
        $this->auditLog->record(
            $action,
            'expenses',
            $description,
            $actor,
            (int) $expense->branch_id,
            $expense,
            [
                'expense_date' => $expense->expense_date?->toDateString(),
                'amount' => $expense->amount,
                'status' => $expense->status,
                'category_id' => (int) $expense->expense_category_id,
                'has_proof' => filled($expense->proof_file),
            ],
            $ipAddress,
            $userAgent,
        );
    }
}
