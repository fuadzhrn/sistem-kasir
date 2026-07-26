<?php

namespace App\Services\Report;

use App\Models\Branch;
use App\Models\User;

final class ReportAccessContextService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function resolve(User $user, array $filters): array
    {
        abort_unless($user->is_active && $user->hasAnyRole(['owner', 'admin']), 403);

        if ($user->isAdmin()) {
            abort_if($user->branch_id === null, 403, 'Akun Admin belum terhubung dengan cabang.');
            $branch = Branch::query()->find($user->branch_id);
            abort_if($branch === null, 403, 'Cabang akun Admin tidak tersedia.');
        } else {
            $branch = isset($filters['branch_id'])
                ? Branch::query()->findOrFail($filters['branch_id'])
                : null;
        }

        return [
            'user' => $user,
            'branch' => $branch,
            'branch_id' => $branch?->getKey(),
            'branch_name' => $branch?->name ?? 'Semua Cabang',
            'all_branches' => $user->isOwner() && $branch === null,
            'can_view_financial_cost' => true,
            'can_view_inventory_cost' => $user->isOwner(),
            'can_view_purchase_price_history' => $user->isOwner(),
        ];
    }
}
