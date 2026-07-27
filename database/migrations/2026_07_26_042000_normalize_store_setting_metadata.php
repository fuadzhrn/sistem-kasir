<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var array<string, array{type: string, group: string}>
     */
    private array $metadata = [
        'store.name' => ['type' => 'string', 'group' => 'store'],
        'store.logo_path' => ['type' => 'string', 'group' => 'store'],
        'store.address' => ['type' => 'string', 'group' => 'store'],
        'store.phone' => ['type' => 'string', 'group' => 'store'],
        'receipt.footer_message' => ['type' => 'string', 'group' => 'receipt'],
        'receipt.additional_information' => ['type' => 'string', 'group' => 'receipt'],
        'receipt.default_paper_width' => ['type' => 'integer', 'group' => 'receipt'],
        'receipt.show_logo' => ['type' => 'boolean', 'group' => 'receipt'],
        'receipt.show_store_address' => ['type' => 'boolean', 'group' => 'receipt'],
        'receipt.show_store_phone' => ['type' => 'boolean', 'group' => 'receipt'],
        'receipt.show_branch_address' => ['type' => 'boolean', 'group' => 'receipt'],
        'receipt.show_branch_phone' => ['type' => 'boolean', 'group' => 'receipt'],
        'receipt.show_product_code' => ['type' => 'boolean', 'group' => 'receipt'],
        'receipt.show_transaction_notes' => ['type' => 'boolean', 'group' => 'receipt'],
        'receipt.show_copy_label' => ['type' => 'boolean', 'group' => 'receipt'],
        'receipt.number_format' => ['type' => 'string', 'group' => 'receipt'],
        'receipt.number_prefix' => ['type' => 'string', 'group' => 'receipt'],
        'receipt.number_separator' => ['type' => 'string', 'group' => 'receipt'],
        'receipt.sequence_digits' => ['type' => 'integer', 'group' => 'receipt'],
        'business.default_minimum_stock' => ['type' => 'decimal', 'group' => 'business'],
        'business.maximum_cashier_discount' => ['type' => 'decimal', 'group' => 'business'],
    ];

    public function up(): void
    {
        foreach ($this->metadata as $key => $metadata) {
            DB::table('settings')->where('key', $key)->update($metadata);
        }
    }

    public function down(): void
    {
        // Metadata lama tidak dipulihkan karena group legacy bersifat ambigu.
    }
};
