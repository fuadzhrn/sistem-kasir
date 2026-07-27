<?php

namespace App\Http\Requests\Setting;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateReceiptSettingRequest extends FormRequest
{
    /**
     * @var array<int, string>
     */
    private const BOOLEAN_FIELDS = [
        'show_logo',
        'show_store_address',
        'show_store_phone',
        'show_branch_address',
        'show_branch_phone',
        'show_product_code',
        'show_transaction_notes',
        'show_copy_label',
    ];

    public function authorize(): bool
    {
        return $this->user()?->can('update', Setting::class) === true;
    }

    public function rules(): array
    {
        return [
            'receipt_footer_message' => ['nullable', 'string', 'max:500'],
            'receipt_additional_information' => ['nullable', 'string', 'max:1000'],
            'default_paper_width' => ['required', 'integer', Rule::in([58, 80])],
            'show_logo' => ['required', 'boolean'],
            'show_store_address' => ['required', 'boolean'],
            'show_store_phone' => ['required', 'boolean'],
            'show_branch_address' => ['required', 'boolean'],
            'show_branch_phone' => ['required', 'boolean'],
            'show_product_code' => ['required', 'boolean'],
            'show_transaction_notes' => ['required', 'boolean'],
            'show_copy_label' => ['required', 'boolean'],
            'number_format' => [
                'required',
                Rule::in([
                    'branch_date_sequence',
                    'prefix_branch_date_sequence',
                    'branch_date_sequence_slash',
                    'prefix_branch_date_sequence_slash',
                ]),
            ],
            'number_prefix' => [
                'nullable',
                'string',
                'max:10',
                'regex:/^[A-Z0-9]+$/',
                'required_if:number_format,prefix_branch_date_sequence,prefix_branch_date_sequence_slash',
            ],
            'number_separator' => ['required', Rule::in(['-', '/'])],
            'sequence_digits' => ['required', 'integer', Rule::in([4, 5, 6])],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $format = (string) $this->input('number_format');
                $expectedSeparator = str_ends_with($format, '_slash') ? '/' : '-';

                if (! $validator->errors()->has('number_separator')
                    && $this->input('number_separator') !== $expectedSeparator) {
                    $validator->errors()->add(
                        'number_separator',
                        'Pemisah harus sesuai dengan pola nomor nota yang dipilih.',
                    );
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'receipt_footer_message.max' => 'Pesan penutup maksimal 500 karakter.',
            'receipt_additional_information.max' => 'Informasi tambahan maksimal 1.000 karakter.',
            'default_paper_width.in' => 'Ukuran struk hanya dapat menggunakan 58 mm atau 80 mm.',
            'number_format.in' => 'Pola nomor nota tidak valid.',
            'number_prefix.required_if' => 'Prefix wajib diisi untuk pola yang menggunakan prefix.',
            'number_prefix.regex' => 'Prefix hanya boleh berisi huruf A–Z dan angka 0–9 tanpa spasi.',
            'number_prefix.max' => 'Prefix maksimal 10 karakter.',
            'number_separator.in' => 'Pemisah nomor nota tidak valid.',
            'sequence_digits.in' => 'Jumlah digit urutan hanya boleh 4, 5, atau 6.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $booleans = [];

        foreach (self::BOOLEAN_FIELDS as $field) {
            $booleans[$field] = $this->boolean($field);
        }

        $this->merge([
            ...$booleans,
            'receipt_footer_message' => $this->nullableTrimmed('receipt_footer_message'),
            'receipt_additional_information' => $this->nullableTrimmed('receipt_additional_information'),
            'number_format' => trim((string) $this->input('number_format')),
            'number_prefix' => $this->nullableUppercase('number_prefix'),
            'number_separator' => trim((string) $this->input('number_separator')),
        ]);
    }

    private function nullableTrimmed(string $key): ?string
    {
        $value = trim((string) $this->input($key));

        return $value === '' ? null : $value;
    }

    private function nullableUppercase(string $key): ?string
    {
        $value = mb_strtoupper(trim((string) $this->input($key)));

        return $value === '' ? null : $value;
    }
}
