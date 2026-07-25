<?php

namespace App\Http\Requests\Sale;

use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;

class VoidSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $sale = $this->sale();
        $user = $this->user();

        if ($sale === null || $user === null) {
            return false;
        }

        return $user->can('void', $sale)
            || ($sale->isVoided() && $user->can('view', $sale));
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'refund_confirmed' => $this->requiresRefundConfirmation()
                ? ['required', 'accepted']
                : ['nullable', 'boolean'],
            'confirmation' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Alasan pembatalan wajib diisi.',
            'reason.min' => 'Alasan pembatalan minimal 10 karakter.',
            'reason.max' => 'Alasan pembatalan maksimal 1.000 karakter.',
            'refund_confirmed.required' => 'Konfirmasi pengembalian dana manual wajib diberikan.',
            'refund_confirmed.accepted' => 'Konfirmasi pengembalian dana manual wajib dicentang.',
            'confirmation.required' => 'Konfirmasi pembatalan permanen wajib diberikan.',
            'confirmation.accepted' => 'Anda harus memahami bahwa pembatalan bersifat permanen.',
        ];
    }

    public function sale(): ?Sale
    {
        $user = $this->user();

        if ($user === null) {
            return null;
        }

        return Sale::query()
            ->accessibleTo($user)
            ->whereKey($this->route('sale'))
            ->with(['paymentMethod:id,type', 'saleVoid:id,sale_id'])
            ->first();
    }

    public function requiresRefundConfirmation(): bool
    {
        $sale = $this->sale();

        return $sale !== null && $sale->paymentMethod?->type !== 'cash';
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reason' => preg_replace('/\s+/u', ' ', trim((string) $this->input('reason'))),
        ]);
    }
}
