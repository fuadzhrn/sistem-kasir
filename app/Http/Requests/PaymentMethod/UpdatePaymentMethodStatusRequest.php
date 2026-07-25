<?php

namespace App\Http\Requests\PaymentMethod;

use App\Models\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentMethodStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $paymentMethod = $this->route('paymentMethod');

        return $paymentMethod instanceof PaymentMethod
            && $this->user()?->can('updateStatus', $paymentMethod) === true;
    }

    public function rules(): array
    {
        return ['is_active' => ['required', 'boolean']];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge([
                'is_active' => filter_var($this->input('is_active'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE),
            ]);
        }
    }
}
