<?php

namespace App\Services\MasterData;

use App\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentMethodService
{
    public function create(array $data): PaymentMethod
    {
        return DB::transaction(fn (): PaymentMethod => PaymentMethod::query()->create([
            'code' => $data['code'],
            'name' => $data['name'],
            'type' => $data['type'],
            'sort_order' => $data['sort_order'],
            'is_active' => true,
        ]));
    }

    public function update(PaymentMethod $paymentMethod, array $data): PaymentMethod
    {
        return DB::transaction(function () use ($paymentMethod, $data): PaymentMethod {
            $locked = PaymentMethod::query()->lockForUpdate()->findOrFail($paymentMethod->getKey());
            $locked->update([
                'code' => $data['code'],
                'name' => $data['name'],
                'type' => $data['type'],
                'sort_order' => $data['sort_order'],
            ]);

            return $locked->refresh();
        });
    }

    public function updateStatus(PaymentMethod $paymentMethod, bool $isActive): PaymentMethod
    {
        return DB::transaction(function () use ($paymentMethod, $isActive): PaymentMethod {
            $locked = PaymentMethod::query()->lockForUpdate()->findOrFail($paymentMethod->getKey());
            $locked->update(['is_active' => $isActive]);

            return $locked->refresh();
        });
    }

    public function deleteIfUnused(PaymentMethod $paymentMethod): void
    {
        DB::transaction(function () use ($paymentMethod): void {
            $locked = PaymentMethod::query()->lockForUpdate()->findOrFail($paymentMethod->getKey());

            if ($locked->sales()->exists()) {
                throw ValidationException::withMessages([
                    'delete' => 'Metode pembayaran tidak dapat dihapus karena sudah digunakan oleh transaksi.',
                ]);
            }

            $locked->delete();
        });
    }
}
