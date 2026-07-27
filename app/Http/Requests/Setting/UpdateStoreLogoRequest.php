<?php

namespace App\Http\Requests\Setting;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UpdateStoreLogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('updateLogo', Setting::class) === true;
    }

    public function rules(): array
    {
        return [
            'logo' => [
                'required',
                File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(2 * 1024),
                Rule::dimensions()->minWidth(100)->minHeight(100)->maxWidth(3000)->maxHeight(3000),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'logo.required' => 'Pilih file logo terlebih dahulu.',
            'logo.image' => 'File logo tidak didukung.',
            'logo.mimes' => 'Logo harus berformat JPG, JPEG, PNG, atau WEBP.',
            'logo.max' => 'Ukuran logo terlalu besar. Maksimal 2 MB.',
            'logo.dimensions' => 'Dimensi logo harus antara 100×100 dan 3000×3000 piksel.',
        ];
    }
}
