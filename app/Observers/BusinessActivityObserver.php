<?php

namespace App\Observers;

use App\Models\Branch;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Unit;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Database\Eloquent\Model;

class BusinessActivityObserver
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function created(Model $model): void
    {
        match (true) {
            $model instanceof Product => $this->productCreated($model),
            $model instanceof StockMovement => $this->initialStockCreated($model),
            $model instanceof StockAdjustment => $this->stockAdjustmentCreated($model),
            $model instanceof StockTransfer => $this->stockTransferRequested($model),
            $model instanceof User => $this->simpleCreated($model, 'user_created', 'users', 'Pengguna baru dibuat.'),
            $model instanceof Branch => $this->simpleCreated($model, 'branch_created', 'branches', 'Cabang baru dibuat.'),
            $model instanceof Category => $this->simpleCreated($model, 'category_created', 'categories', 'Kategori produk dibuat.'),
            $model instanceof Unit => $this->simpleCreated($model, 'unit_created', 'units', 'Satuan produk dibuat.'),
            $model instanceof PaymentMethod => $this->simpleCreated($model, 'payment_method_created', 'payment_methods', 'Metode pembayaran dibuat.'),
            default => null,
        };
    }

    public function updated(Model $model): void
    {
        match (true) {
            $model instanceof Product => $this->productUpdated($model),
            $model instanceof StockTransfer => $this->stockTransferUpdated($model),
            $model instanceof User => $this->userUpdated($model),
            $model instanceof Branch => $this->simpleUpdated($model, 'branch_updated', 'branch_status_changed', 'branches'),
            $model instanceof Category => $this->simpleUpdated($model, 'category_updated', 'category_status_changed', 'categories'),
            $model instanceof Unit => $this->simpleUpdated($model, 'unit_updated', 'unit_status_changed', 'units'),
            $model instanceof PaymentMethod => $this->simpleUpdated($model, 'payment_method_updated', 'payment_method_status_changed', 'payment_methods'),
            default => null,
        };
    }

    public function deleting(Model $model): void
    {
        match (true) {
            $model instanceof Category => $this->simpleDeleted($model, 'category_deleted', 'categories'),
            $model instanceof Unit => $this->simpleDeleted($model, 'unit_deleted', 'units'),
            $model instanceof PaymentMethod => $this->simpleDeleted($model, 'payment_method_deleted', 'payment_methods'),
            default => null,
        };
    }

    private function productCreated(Product $product): void
    {
        $actor = $this->actor($product, 'created_by');

        if ($actor === null) {
            return;
        }

        $this->auditLog->record(
            'product_created',
            'products',
            "Produk {$product->code} dibuat.",
            $actor,
            $this->globalBranch($actor),
            $product,
            [
                'product_code' => $product->code,
                'product_name' => $product->name,
                'selling_price' => $product->selling_price,
                'purchase_price' => $product->purchase_price,
                'global_resource' => ! $actor?->isOwner(),
            ],
        );
    }

    private function productUpdated(Product $product): void
    {
        $actor = $this->actor($product, 'updated_by');

        if ($actor === null) {
            return;
        }

        $branch = $this->globalBranch($actor);
        $safeChanges = $this->beforeAfter($product, [
            'category_id', 'unit_id', 'code', 'barcode', 'name', 'brand', 'size', 'minimum_stock',
        ]);

        if ($safeChanges !== []) {
            $this->auditLog->record('product_updated', 'products', "Produk {$product->code} diperbarui.", $actor, $branch, $product, [
                'changes' => $safeChanges,
                'global_resource' => ! $actor?->isOwner(),
            ]);
        }

        if ($product->wasChanged('is_active')) {
            $this->auditLog->record('product_status_changed', 'products', "Status produk {$product->code} diperbarui.", $actor, $branch, $product, [
                'before' => ['is_active' => (bool) $product->getOriginal('is_active')],
                'after' => ['is_active' => (bool) $product->is_active],
                'global_resource' => ! $actor?->isOwner(),
            ]);
        }

        if ($product->wasChanged('image_path')) {
            $action = $product->image_path ? 'product_image_updated' : 'product_image_removed';
            $this->auditLog->record($action, 'products', "Foto produk {$product->code} diperbarui.", $actor, $branch, $product, [
                'had_image_before' => filled($product->getOriginal('image_path')),
                'has_image_after' => filled($product->image_path),
                'global_resource' => ! $actor?->isOwner(),
            ]);
        }

        $purchaseChanged = $product->wasChanged('purchase_price');
        $sellingChanged = $product->wasChanged('selling_price');

        if ($purchaseChanged || $sellingChanged) {
            $action = $purchaseChanged && $sellingChanged
                ? 'product_prices_changed'
                : ($purchaseChanged ? 'product_purchase_price_changed' : 'product_selling_price_changed');
            $fields = array_filter(
                ['purchase_price' => $purchaseChanged, 'selling_price' => $sellingChanged],
            );
            $this->auditLog->record($action, 'prices', "Harga produk {$product->code} diperbarui.", $actor, $branch, $product, [
                'changes' => $this->beforeAfter($product, array_keys($fields)),
                'global_resource' => ! $actor?->isOwner(),
            ]);
        }
    }

    private function initialStockCreated(StockMovement $movement): void
    {
        $actor = $this->actor($movement, 'created_by');

        if ($movement->movement_type !== StockMovement::TYPE_INITIAL || $actor === null) {
            return;
        }

        $action = (float) $movement->quantity_before === 0.0
            ? 'initial_stock_created'
            : 'initial_stock_corrected';
        $this->auditLog->record($action, 'stocks', 'Stok awal produk dicatat.', $actor, (int) $movement->branch_id, $movement, [
            'product_id' => (int) $movement->product_id,
            'quantity_before' => $movement->quantity_before,
            'quantity_change' => $movement->quantity_change,
            'quantity_after' => $movement->quantity_after,
            'reason' => $movement->notes,
        ]);
    }

    private function stockAdjustmentCreated(StockAdjustment $adjustment): void
    {
        $actor = $this->actor($adjustment, 'created_by');

        if ($actor === null) {
            return;
        }

        $this->auditLog->record('stock_adjustment_created', 'stock_adjustments', "Penyesuaian stok {$adjustment->adjustment_number} dibuat.", $actor, (int) $adjustment->branch_id, $adjustment, [
            'adjustment_number' => $adjustment->adjustment_number,
            'product_id' => (int) $adjustment->product_id,
            'adjustment_type' => $adjustment->adjustment_type,
            'quantity_before' => $adjustment->quantity_before,
            'quantity_change' => $adjustment->quantity_change,
            'quantity_after' => $adjustment->quantity_after,
            'reason' => $adjustment->reason,
        ]);
    }

    private function stockTransferRequested(StockTransfer $transfer): void
    {
        $actor = $this->actor($transfer, 'requested_by');

        if ($actor === null) {
            return;
        }

        $this->auditLog->record('stock_transfer_requested', 'stock_transfers', "Mutasi stok {$transfer->transfer_number} diminta.", $actor, (int) $transfer->from_branch_id, $transfer, $this->stockTransferMetadata($transfer));
    }

    private function stockTransferUpdated(StockTransfer $transfer): void
    {
        if (! $transfer->wasChanged('status')) {
            return;
        }

        $actor = $this->actor($transfer, 'reviewed_by') ?? $this->actor($transfer, 'requested_by');

        if ($actor === null) {
            return;
        }
        $metadata = $this->stockTransferMetadata($transfer);

        if ($transfer->status === StockTransfer::STATUS_COMPLETED) {
            $this->auditLog->record('stock_transfer_completed_out', 'stock_transfers', "Mutasi {$transfer->transfer_number} dikeluarkan dari cabang asal.", $actor, (int) $transfer->from_branch_id, $transfer, $metadata);
            $this->auditLog->record('stock_transfer_completed_in', 'stock_transfers', "Mutasi {$transfer->transfer_number} diterima cabang tujuan.", $actor, (int) $transfer->to_branch_id, $transfer, $metadata);

            return;
        }

        $action = $transfer->status === StockTransfer::STATUS_REJECTED
            ? 'stock_transfer_rejected'
            : 'stock_transfer_cancelled';
        $this->auditLog->record($action, 'stock_transfers', "Status mutasi {$transfer->transfer_number} diperbarui.", $actor, (int) $transfer->from_branch_id, $transfer, $metadata);
    }

    /**
     * @return array<string, mixed>
     */
    private function stockTransferMetadata(StockTransfer $transfer): array
    {
        return [
            'transfer_number' => $transfer->transfer_number,
            'from_branch_id' => (int) $transfer->from_branch_id,
            'to_branch_id' => (int) $transfer->to_branch_id,
            'product_id' => (int) $transfer->product_id,
            'quantity' => $transfer->quantity,
            'status' => $transfer->status,
            'rejection_reason' => $transfer->rejection_reason,
        ];
    }

    private function userUpdated(User $user): void
    {
        $businessFields = ['name', 'username', 'email', 'role_id', 'branch_id', 'is_active'];

        if (! $user->wasChanged($businessFields)) {
            return;
        }

        $actor = auth()->user();

        if (! $actor instanceof User) {
            return;
        }

        $branch = $user->branch_id;
        $base = ['changes' => $this->beforeAfter($user, $businessFields)];

        if ($user->wasChanged(['name', 'username', 'email'])) {
            $this->auditLog->record('user_updated', 'users', "Data pengguna {$user->username} diperbarui.", $actor, $branch, $user, $base);
        }

        foreach ([
            'role_id' => 'user_role_changed',
            'branch_id' => 'user_branch_changed',
            'is_active' => 'user_status_changed',
        ] as $field => $action) {
            if ($user->wasChanged($field)) {
                $this->auditLog->record($action, 'users', "Akses pengguna {$user->username} diperbarui.", $actor, $branch, $user, [
                    'before' => [$field => $user->getOriginal($field)],
                    'after' => [$field => $user->getAttribute($field)],
                ]);
            }
        }
    }

    private function simpleCreated(Model $model, string $action, string $module, string $description): void
    {
        $actor = auth()->user();

        if (! $actor instanceof User) {
            return;
        }

        $branch = $model instanceof Branch ? $model->getKey() : ($model instanceof User ? $model->branch_id : $this->globalBranch($actor));
        $this->auditLog->record($action, $module, $description, $actor, $branch, $model, [
            'name' => $model->getAttribute('name'),
            'code' => $model->getAttribute('code'),
            'global_resource' => ! ($model instanceof Branch) && ! ($model instanceof User) && ! $actor->isOwner(),
        ]);
    }

    private function simpleUpdated(Model $model, string $updateAction, string $statusAction, string $module): void
    {
        $actor = auth()->user();

        if (! $actor instanceof User) {
            return;
        }

        $branch = $model instanceof Branch ? $model->getKey() : $this->globalBranch($actor);
        $changes = $this->beforeAfter($model, array_keys($model->getChanges()));

        if ($model->wasChanged('is_active')) {
            $this->auditLog->record($statusAction, $module, 'Status data master diperbarui.', $actor, $branch, $model, [
                'before' => ['is_active' => (bool) $model->getOriginal('is_active')],
                'after' => ['is_active' => (bool) $model->getAttribute('is_active')],
            ]);
            unset($changes['is_active']);
        }

        unset($changes['updated_at']);

        if ($changes !== []) {
            $this->auditLog->record($updateAction, $module, 'Data master diperbarui.', $actor, $branch, $model, ['changes' => $changes]);
        }
    }

    private function simpleDeleted(Model $model, string $action, string $module): void
    {
        $actor = auth()->user();

        if (! $actor instanceof User) {
            return;
        }

        $this->auditLog->record($action, $module, 'Data master dihapus.', $actor, $this->globalBranch($actor), $model, [
            'name' => $model->getAttribute('name'),
            'code' => $model->getAttribute('code'),
        ]);
    }

    /**
     * @param  array<int, string>  $fields
     * @return array<string, array{before: mixed, after: mixed}>
     */
    private function beforeAfter(Model $model, array $fields): array
    {
        $changes = [];

        foreach ($fields as $field) {
            if ($model->wasChanged($field)) {
                $changes[$field] = [
                    'before' => $model->getOriginal($field),
                    'after' => $model->getAttribute($field),
                ];
            }
        }

        return $changes;
    }

    private function actor(Model $model, string $field): ?User
    {
        $actor = auth()->user();

        return $actor instanceof User ? $actor : null;
    }

    private function globalBranch(?User $actor): ?int
    {
        return $actor?->isOwner() ? null : $actor?->branch_id;
    }
}
