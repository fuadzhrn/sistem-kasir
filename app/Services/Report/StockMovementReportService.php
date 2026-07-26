<?php

namespace App\Services\Report;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class StockMovementReportService extends AbstractReportService
{
    public function build(User $user, array $filters, bool $forPrint = false): array
    {
        $context = $this->foundation($user, $filters);
        $query = DB::table('stock_movements as movement')->join('branches as branch', 'branch.id', '=', 'movement.branch_id')->join('products as product', 'product.id', '=', 'movement.product_id')->join('users as creator', 'creator.id', '=', 'movement.created_by')
            ->whereBetween('movement.created_at', [$context['range']['start'], $context['range']['end']])
            ->when($context['access']['branch_id'], fn (Builder $q, int $id) => $q->where('movement.branch_id', $id))
            ->when(isset($filters['product_id']), fn (Builder $q) => $q->where('movement.product_id', $filters['product_id']))
            ->when(isset($filters['created_by']), fn (Builder $q) => $q->where('movement.created_by', $filters['created_by']))
            ->when(isset($filters['movement_type']), fn (Builder $q) => $q->where('movement.movement_type', $filters['movement_type']))
            ->when(isset($filters['reference_type']), fn (Builder $q) => $q->where('movement.reference_type', $filters['reference_type']))
            ->when(isset($filters['search']), function (Builder $q) use ($filters) {
                $s = $this->like($filters['search']);
                $q->where(fn (Builder $x) => $x->where('product.code', 'like', $s)->orWhere('product.name', 'like', $s)->orWhere('movement.reference_id', 'like', $s)->orWhere('movement.notes', 'like', $s)->orWhere('creator.name', 'like', $s));
            });
        $total = (clone $query)->selectRaw('COUNT(movement.id) movement_count')->selectRaw('SUM(CASE WHEN movement.quantity_change > 0 THEN 1 ELSE 0 END) incoming_count')->selectRaw('SUM(CASE WHEN movement.quantity_change < 0 THEN 1 ELSE 0 END) outgoing_count')->first();
        $typeTotals = (clone $query)->select('movement.movement_type')
            ->selectRaw('COUNT(movement.id) movement_count')
            ->groupBy('movement.movement_type')
            ->orderBy('movement.movement_type')
            ->get();
        $sort = ['date' => 'movement.created_at', 'product' => 'product.name', 'type' => 'movement.movement_type'][$filters['sort'] ?? 'date'];
        $data = (clone $query)->select(['movement.id', 'movement.created_at', 'branch.name as branch_name', 'product.code', 'product.name as product_name', 'movement.movement_type', 'movement.quantity_before', 'movement.quantity_change', 'movement.quantity_after', 'creator.name as creator_name', 'movement.reference_type', 'movement.reference_id', 'movement.notes']);
        if ($context['access']['can_view_inventory_cost']) {
            $data->addSelect('movement.unit_cost');
        }
        $data->orderBy($sort, $filters['direction'] ?? 'desc')->orderByDesc('movement.id');
        $map = function ($r) use ($context) {
            $reference = $r->reference_type
                ? class_basename($r->reference_type).($r->reference_id ? ' #'.$r->reference_id : '')
                : '—';
            $row = ['date' => date('d M Y, H.i', strtotime($r->created_at)), 'branch' => $r->branch_name, 'product' => $r->code.' — '.$r->product_name, 'type' => str_replace('_', ' ', mb_convert_case($r->movement_type, MB_CASE_TITLE)), 'before' => $this->quantity($r->quantity_before), 'change' => $this->quantity($r->quantity_change), 'after' => $this->quantity($r->quantity_after), 'creator' => $r->creator_name, 'reference' => $reference, 'notes' => $r->notes ?: '—'];
            if ($context['access']['can_view_inventory_cost']) {
                $row['unit_cost'] = $this->money($r->unit_cost);
            }

            return $row;
        };
        $rows = $forPrint ? $this->printableRows($data, $map) : $data->paginate((int) $filters['per_page'])->withQueryString()->through($map);
        $columns = [['key' => 'date', 'label' => 'Waktu'], ['key' => 'branch', 'label' => 'Cabang'], ['key' => 'product', 'label' => 'Produk'], ['key' => 'type', 'label' => 'Tipe'], ['key' => 'before', 'label' => 'Sebelum'], ['key' => 'change', 'label' => 'Perubahan'], ['key' => 'after', 'label' => 'Sesudah'], ['key' => 'creator', 'label' => 'Pengguna'], ['key' => 'reference', 'label' => 'Referensi'], ['key' => 'notes', 'label' => 'Catatan']];
        if ($context['access']['can_view_inventory_cost']) {
            $columns[] = ['key' => 'unit_cost', 'label' => 'Unit Cost'];
        }

        $summary = [['label' => 'Jumlah Movement', 'value' => number_format((int) $total->movement_count, 0, ',', '.')], ['label' => 'Movement Masuk', 'value' => number_format((int) $total->incoming_count, 0, ',', '.')], ['label' => 'Movement Keluar', 'value' => number_format((int) $total->outgoing_count, 0, ',', '.')]];
        foreach ($typeTotals as $typeTotal) {
            $summary[] = [
                'label' => str_replace('_', ' ', mb_convert_case($typeTotal->movement_type, MB_CASE_TITLE)),
                'value' => number_format((int) $typeTotal->movement_count, 0, ',', '.'),
            ];
        }

        return $this->result('stock-movements', 'Laporan Pergerakan Stok', 'Audit perubahan quantity; quantity lintas produk tidak dijumlahkan.', $context, $columns, $rows, $summary, $filters, $forPrint, ['filter_options' => $this->filterOptions($user, $context, ['branches', 'users', 'products'])]);
    }
}
