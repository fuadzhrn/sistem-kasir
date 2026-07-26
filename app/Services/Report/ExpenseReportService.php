<?php

namespace App\Services\Report;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class ExpenseReportService extends AbstractReportService
{
    public function build(User $user, array $filters, bool $forPrint = false): array
    {
        $context = $this->foundation($user, $filters);
        $query = Expense::query()->accessibleTo($user)->when($context['access']['branch_id'], fn (Builder $q, int $id) => $q->where('branch_id', $id))
            ->whereBetween('expense_date', [$context['range']['date_from'], $context['range']['date_to']])
            ->when(($filters['status'] ?? 'all') !== 'all', fn (Builder $q) => $q->where('status', $filters['status']))
            ->when(isset($filters['category_id']), fn (Builder $q) => $q->where('expense_category_id', $filters['category_id']))
            ->when(isset($filters['created_by']), fn (Builder $q) => $q->where('created_by', $filters['created_by']))
            ->when(isset($filters['search']), function (Builder $q) use ($filters, $user) {
                $s = $this->like($filters['search']);
                $q->where(fn (Builder $x) => $x->where('description', 'like', $s)->orWhereHas('expenseCategory', fn (Builder $c) => $c->where('name', 'like', $s))->orWhereHas('creator', fn (Builder $u) => $u->where('name', 'like', $s))->when($user->isOwner(), fn (Builder $o) => $o->orWhereHas('branch', fn (Builder $b) => $b->where('name', 'like', $s))));
            });
        $total = (clone $query)->reorder()->selectRaw("SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) pending_count")->selectRaw("SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) approved_count")->selectRaw("SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) rejected_count")->selectRaw("COALESCE(SUM(CASE WHEN status='pending' THEN amount ELSE 0 END),0) pending_amount")->selectRaw("COALESCE(SUM(CASE WHEN status='approved' THEN amount ELSE 0 END),0) approved_amount")->selectRaw("COALESCE(SUM(CASE WHEN status='rejected' THEN amount ELSE 0 END),0) rejected_amount")->first();
        $sort = ['date' => 'expense_date', 'amount' => 'amount', 'status' => 'status', 'category' => 'expense_category_id'][$filters['sort'] ?? 'date'];
        $data = (clone $query)->select(['id', 'branch_id', 'expense_category_id', 'created_by', 'approved_by', 'rejected_by', 'expense_date', 'amount', 'description', 'proof_file', 'status', 'approved_at', 'rejected_at'])->with(['branch:id,name', 'expenseCategory:id,name', 'creator:id,name', 'approver:id,name', 'rejector:id,name'])->orderBy($sort, $filters['direction'] ?? 'desc')->orderByDesc('id');
        $map = fn (Expense $e) => ['date' => $e->expense_date->translatedFormat('d M Y'), 'branch' => $e->branch?->name ?? '—', 'category' => $e->expenseCategory?->name ?? '—', 'description' => $e->description, 'amount' => $this->money($e->amount), 'creator' => $e->creator?->name ?? '—', 'status' => $e->statusLabel(), 'reviewer' => $e->approver?->name ?? $e->rejector?->name ?? '—', 'reviewed_at' => $e->approved_at?->translatedFormat('d M Y, H.i') ?? $e->rejected_at?->translatedFormat('d M Y, H.i') ?? '—', 'proof' => $e->proof_file ? 'Ada' : 'Tidak ada', 'detail_url' => route('expenses.show', $e)];
        $rows = $forPrint ? $this->printableRows($data, $map) : $data->paginate((int) $filters['per_page'])->withQueryString()->through($map);

        return $this->result('expenses', 'Laporan Pengeluaran', 'Hanya pengeluaran approved yang diperhitungkan pada laba bersih.', $context, [
            ['key' => 'date', 'label' => 'Tanggal'], ['key' => 'branch', 'label' => 'Cabang'], ['key' => 'category', 'label' => 'Kategori'], ['key' => 'description', 'label' => 'Deskripsi', 'link' => 'detail_url'], ['key' => 'amount', 'label' => 'Nominal'], ['key' => 'creator', 'label' => 'Pencatat'], ['key' => 'status', 'label' => 'Status'], ['key' => 'reviewer', 'label' => 'Reviewer'], ['key' => 'reviewed_at', 'label' => 'Waktu Review'], ['key' => 'proof', 'label' => 'Bukti'],
        ], $rows, [['label' => 'Pending', 'value' => number_format((int) $total->pending_count, 0, ',', '.').' / '.$this->money($total->pending_amount)], ['label' => 'Disetujui', 'value' => number_format((int) $total->approved_count, 0, ',', '.').' / '.$this->money($total->approved_amount)], ['label' => 'Ditolak', 'value' => number_format((int) $total->rejected_count, 0, ',', '.').' / '.$this->money($total->rejected_amount)]], $filters, $forPrint, ['filter_options' => $this->filterOptions($user, $context, ['branches', 'users', 'expense_categories'])]);
    }
}
