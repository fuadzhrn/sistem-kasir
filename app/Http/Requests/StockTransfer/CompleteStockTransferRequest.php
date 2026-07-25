<?php

namespace App\Http\Requests\StockTransfer;

use Illuminate\Foundation\Http\FormRequest;

class CompleteStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('complete', $this->route('stockTransfer')) === true;
    }

    public function rules(): array
    {
        return [];
    }
}
