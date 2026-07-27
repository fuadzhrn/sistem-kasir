<?php

namespace App\Http\Requests\Setting;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class DeleteStoreLogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('deleteLogo', Setting::class) === true;
    }

    public function rules(): array
    {
        return [];
    }
}
