<?php

namespace App\Http\Requests\PaymentMethod;

use App\Models\PaymentMethod;
use Illuminate\Validation\Rule;

class UpdatePaymentMethodRequest extends StorePaymentMethodRequest
{
    public function authorize(): bool
    {
        $paymentMethod = $this->route('paymentMethod');

        return $paymentMethod instanceof PaymentMethod
            && $this->user()?->can('update', $paymentMethod) === true;
    }

    public function rules(): array
    {
        /** @var PaymentMethod $paymentMethod */
        $paymentMethod = $this->route('paymentMethod');
        $rules = parent::rules();
        $rules['code'] = [
            'required',
            'string',
            'max:30',
            'regex:/^[A-Z0-9_-]+$/',
            Rule::unique('payment_methods', 'code')->ignore($paymentMethod),
        ];

        return $rules;
    }

    protected function nameExists(?int $ignoreId = null): bool
    {
        /** @var PaymentMethod $paymentMethod */
        $paymentMethod = $this->route('paymentMethod');

        return parent::nameExists((int) $paymentMethod->getKey());
    }
}
