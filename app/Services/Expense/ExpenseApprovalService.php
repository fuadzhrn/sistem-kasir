<?php

namespace App\Services\Expense;

use App\Models\ActivityLog;
use App\Models\Expense;
use App\Models\User;
use App\Support\Format\Rupiah;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpenseApprovalService
{
    public function approve(
        Expense $expense,
        User $actor,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): Expense {
        $this->ensureOwner($actor);

        return DB::transaction(function () use ($expense, $actor, $ipAddress, $userAgent): Expense {
            $locked = Expense::query()->lockForUpdate()->findOrFail($expense->getKey());
            $this->ensurePending($locked);
            $locked->update([
                'status' => Expense::STATUS_APPROVED,
                'approved_by' => $actor->getKey(),
                'approved_at' => now(),
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);
            $this->log($locked, $actor, 'expense_approved', 'Pengeluaran sebesar '.Rupiah::format($locked->amount).' disetujui.', $ipAddress, $userAgent);

            return $locked->refresh();
        });
    }

    public function reject(
        Expense $expense,
        string $reason,
        User $actor,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): Expense {
        $this->ensureOwner($actor);

        return DB::transaction(function () use ($expense, $reason, $actor, $ipAddress, $userAgent): Expense {
            $locked = Expense::query()->lockForUpdate()->findOrFail($expense->getKey());
            $this->ensurePending($locked);
            $locked->update([
                'status' => Expense::STATUS_REJECTED,
                'approved_by' => null,
                'approved_at' => null,
                'rejected_by' => $actor->getKey(),
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);
            $this->log($locked, $actor, 'expense_rejected', 'Pengeluaran sebesar '.Rupiah::format($locked->amount).' ditolak.', $ipAddress, $userAgent);

            return $locked->refresh();
        });
    }

    private function ensureOwner(User $actor): void
    {
        if (! $actor->is_active || ! $actor->isOwner()) {
            throw new AuthorizationException('Anda tidak memiliki izin untuk meninjau pengeluaran.');
        }
    }

    private function ensurePending(Expense $expense): void
    {
        if (! $expense->isPending()) {
            throw ValidationException::withMessages([
                'status' => 'Pengeluaran sudah diproses oleh pengguna lain.',
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
        ActivityLog::query()->create([
            'user_id' => $actor->getKey(),
            'branch_id' => $expense->branch_id,
            'action' => $action,
            'module' => 'expenses',
            'reference_type' => Expense::class,
            'reference_id' => $expense->getKey(),
            'description' => $description,
            'ip_address' => $ipAddress ? mb_substr($ipAddress, 0, 45) : null,
            'user_agent' => $userAgent ? mb_substr($userAgent, 0, 1000) : null,
        ]);
    }
}
