<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private array $aliases = [
        'store_name' => 'store.name',
        'receipt_width' => 'receipt.default_paper_width',
        'receipt_message' => 'receipt.footer_message',
        'default_minimum_stock' => 'business.default_minimum_stock',
        'maximum_cashier_discount' => 'business.maximum_cashier_discount',
    ];

    public function up(): void
    {
        foreach ($this->aliases as $legacy => $canonical) {
            $canonicalExists = DB::table('settings')->where('key', $canonical)->exists();

            if ($canonicalExists) {
                DB::table('settings')->where('key', $legacy)->delete();

                continue;
            }

            DB::table('settings')->where('key', $legacy)->update(['key' => $canonical]);
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->aliases, true) as $legacy => $canonical) {
            $legacyExists = DB::table('settings')->where('key', $legacy)->exists();

            if (! $legacyExists) {
                DB::table('settings')->where('key', $canonical)->update(['key' => $legacy]);
            }
        }
    }
};
