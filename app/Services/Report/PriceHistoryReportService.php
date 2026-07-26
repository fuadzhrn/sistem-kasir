<?php

namespace App\Services\Report;

use App\Models\PriceHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class PriceHistoryReportService extends AbstractReportService
{
    public function build(User $user, array $filters, bool $forPrint = false): array
    {
        $context = $this->foundation($user, $filters);
        $query = PriceHistory::query()->whereBetween('changed_at', [$context['range']['start'], $context['range']['end']])
            ->when(isset($filters['product_id']), fn (Builder $q) => $q->where('product_id', $filters['product_id']))
            ->when(isset($filters['changed_by']), fn (Builder $q) => $q->where('changed_by', $filters['changed_by']))
            ->when(isset($filters['category_id']), fn (Builder $q) => $q->whereHas('product', fn (Builder $p) => $p->where('category_id', $filters['category_id'])))
            ->when(($filters['change_type'] ?? 'all') === 'selling', fn (Builder $q) => $q->whereColumn('old_selling_price', '!=', 'new_selling_price'))
            ->when(($filters['change_type'] ?? 'all') === 'purchase', fn (Builder $q) => $q->whereColumn('old_purchase_price', '!=', 'new_purchase_price'))
            ->when(isset($filters['search']), function (Builder $q) use ($filters) {
                $s = $this->like($filters['search']);
                $q->where(fn (Builder $x) => $x->where('reason', 'like', $s)->orWhereHas('product', fn (Builder $p) => $p->where('code', 'like', $s)->orWhere('name', 'like', $s))->orWhereHas('changedBy', fn (Builder $u) => $u->where('name', 'like', $s)));
            });
        $total = (clone $query)->reorder()->selectRaw('COUNT(id) change_count')->selectRaw('COUNT(DISTINCT product_id) product_count')->selectRaw('SUM(CASE WHEN old_selling_price != new_selling_price THEN 1 ELSE 0 END) selling_count');
        if ($context['access']['can_view_purchase_price_history']) {
            $total->selectRaw('SUM(CASE WHEN old_purchase_price != new_purchase_price THEN 1 ELSE 0 END) purchase_count');
        }
        $total = $total->first();
        $sort = ['date' => 'changed_at', 'product' => 'product_id', 'selling_change' => 'new_selling_price', 'purchase_change' => 'new_purchase_price'][$filters['sort'] ?? 'date'];
        $columns = ['id', 'product_id', 'changed_by', 'old_selling_price', 'new_selling_price', 'reason', 'changed_at'];
        if ($context['access']['can_view_purchase_price_history']) {
            $columns[] = 'old_purchase_price';
            $columns[] = 'new_purchase_price';
        }
        $data = (clone $query)->select($columns)->with(['product:id,code,name,category_id', 'changedBy:id,name'])->orderBy($sort, $filters['direction'] ?? 'desc')->orderByDesc('id');
        $map = function (PriceHistory $h) use ($context) {
            $row = ['date' => $h->changed_at->translatedFormat('d M Y, H.i'), 'product' => ($h->product?->code ?? '—').' — '.($h->product?->name ?? 'Produk tidak tersedia'), 'old_selling' => $this->money($h->old_selling_price), 'new_selling' => $this->money($h->new_selling_price), 'selling_difference' => $this->money((float) $h->new_selling_price - (float) $h->old_selling_price), 'changed_by' => $h->changedBy?->name ?? '—', 'reason' => $h->reason ?: '—'];
            if ($context['access']['can_view_purchase_price_history']) {
                $row['old_purchase'] = $this->money($h->old_purchase_price);
                $row['new_purchase'] = $this->money($h->new_purchase_price);
                $row['purchase_difference'] = $this->money((float) $h->new_purchase_price - (float) $h->old_purchase_price);
            }

            return $row;
        };
        $rows = $forPrint ? $this->printableRows($data, $map) : $data->paginate((int) $filters['per_page'])->withQueryString()->through($map);
        $displayColumns = [['key' => 'date', 'label' => 'Waktu'], ['key' => 'product', 'label' => 'Produk']];
        if ($context['access']['can_view_purchase_price_history']) {
            $displayColumns[] = ['key' => 'old_purchase', 'label' => 'Harga Beli Lama'];
            $displayColumns[] = ['key' => 'new_purchase', 'label' => 'Harga Beli Baru'];
            $displayColumns[] = ['key' => 'purchase_difference', 'label' => 'Selisih Beli'];
        }
        $displayColumns = [...$displayColumns, ['key' => 'old_selling', 'label' => 'Harga Jual Lama'], ['key' => 'new_selling', 'label' => 'Harga Jual Baru'], ['key' => 'selling_difference', 'label' => 'Selisih Jual'], ['key' => 'changed_by', 'label' => 'Pengubah'], ['key' => 'reason', 'label' => 'Alasan']];
        $summary = [['label' => 'Perubahan', 'value' => number_format((int) $total->change_count, 0, ',', '.')], ['label' => 'Perubahan Harga Jual', 'value' => number_format((int) $total->selling_count, 0, ',', '.')], ['label' => 'Produk', 'value' => number_format((int) $total->product_count, 0, ',', '.')]];
        if ($context['access']['can_view_purchase_price_history']) {
            $summary[] = ['label' => 'Perubahan Harga Beli', 'value' => number_format((int) $total->purchase_count, 0, ',', '.')];
        }

        return $this->result('price-histories', 'Laporan Perubahan Harga', 'Riwayat harga bersifat immutable. Admin hanya menerima harga jual.', $context, $displayColumns, $rows, $summary, $filters, $forPrint, ['filter_options' => $this->filterOptions($user, $context, ['users', 'products', 'categories'])]);
    }
}
