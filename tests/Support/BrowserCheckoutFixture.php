<?php

use App\Models\BranchStock;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$action = $argv[1] ?? '';
$productCode = 'STAGE13-BROWSER-001';
$categorySlug = 'stage13-browser-category';
$unitSlug = 'stage13-browser-unit';

if ($action === 'prepare') {
    $existingFixture = Product::query()->where('code', $productCode)->exists()
        || Category::query()->where('slug', $categorySlug)->exists()
        || Unit::query()->where('slug', $unitSlug)->exists();

    if ($existingFixture) {
        throw new RuntimeException(
            'Fixture browser Tahap 13 sudah ada. Jalankan cleanup sebelum mengulang.',
        );
    }

    $result = DB::transaction(function () use (
        $productCode,
        $categorySlug,
        $unitSlug,
    ): array {
        $cashier = User::query()
            ->where('username', 'test.kasir.a')
            ->where('is_active', true)
            ->firstOrFail();
        $branch = $cashier->branch()->where('is_active', true)->firstOrFail();
        $paymentMethod = PaymentMethod::query()
            ->where('code', 'CASH')
            ->where('is_active', true)
            ->firstOrFail();
        $category = Category::query()->create([
            'name' => 'Kategori Browser Tahap 13',
            'slug' => $categorySlug,
            'description' => 'Fixture sementara pengujian browser.',
            'is_active' => true,
        ]);
        $unit = Unit::query()->create([
            'name' => 'Unit Browser',
            'symbol' => 'ub',
            'slug' => $unitSlug,
            'is_active' => true,
        ]);
        $product = Product::query()->create([
            'category_id' => $category->getKey(),
            'unit_id' => $unit->getKey(),
            'code' => $productCode,
            'barcode' => null,
            'name' => 'Produk Browser Tahap 13',
            'brand' => 'Fixture',
            'size' => '1 unit',
            'purchase_price' => '99999.00',
            'selling_price' => '20000.00',
            'minimum_stock' => '1.000',
            'image_path' => null,
            'is_active' => true,
        ]);
        BranchStock::query()->create([
            'branch_id' => $branch->getKey(),
            'product_id' => $product->getKey(),
            'quantity' => '5.000',
            'average_cost' => '12500.00',
        ]);

        return [
            'cashier_id' => $cashier->getKey(),
            'branch_id' => $branch->getKey(),
            'product_id' => $product->getKey(),
            'payment_method_id' => $paymentMethod->getKey(),
        ];
    });

    fwrite(STDOUT, json_encode(['success' => true, ...$result]));
    exit;
}

if ($action === 'verify') {
    $product = Product::query()->where('code', $productCode)->firstOrFail();
    $saleIds = SaleItem::query()
        ->where('product_id', $product->getKey())
        ->pluck('sale_id');
    $sales = Sale::query()->whereKey($saleIds)->orderBy('id')->get();
    $stock = BranchStock::query()
        ->where('product_id', $product->getKey())
        ->sole();

    fwrite(STDOUT, json_encode([
        'success' => true,
        'sale_count' => $sales->count(),
        'completed_count' => $sales->where('status', Sale::STATUS_COMPLETED)->count(),
        'checkout_token_count' => $sales->pluck('checkout_token')->filter()->unique()->count(),
        'sale_item_count' => DB::table('sale_items')->whereIn('sale_id', $saleIds)->count(),
        'movement_count' => DB::table('stock_movements')
            ->where('reference_type', Sale::class)
            ->whereIn('reference_id', $saleIds)
            ->count(),
        'activity_log_count' => DB::table('activity_logs')
            ->where('action', 'sale_created')
            ->whereIn('reference_id', $saleIds)
            ->count(),
        'stock_final' => $stock->quantity,
        'negative_stock_count' => BranchStock::query()
            ->whereKey($stock->getKey())
            ->where('quantity', '<', 0)
            ->count(),
    ]));
    exit;
}

if ($action === 'cleanup') {
    $result = DB::transaction(function () use (
        $productCode,
        $categorySlug,
        $unitSlug,
    ): array {
        $product = Product::query()->where('code', $productCode)->first();

        if ($product !== null) {
            $saleIds = SaleItem::query()
                ->where('product_id', $product->getKey())
                ->pluck('sale_id');
            DB::table('activity_logs')
                ->where('reference_type', Sale::class)
                ->whereIn('reference_id', $saleIds)
                ->delete();
            DB::table('stock_movements')
                ->where('product_id', $product->getKey())
                ->delete();
            DB::table('sale_items')->whereIn('sale_id', $saleIds)->delete();
            DB::table('sales')->whereIn('id', $saleIds)->delete();
            DB::table('branch_stocks')->where('product_id', $product->getKey())->delete();
            $product->delete();
        }

        Category::query()
            ->where('slug', $categorySlug)
            ->whereDoesntHave('products')
            ->delete();
        Unit::query()
            ->where('slug', $unitSlug)
            ->whereDoesntHave('products')
            ->delete();

        return [
            'product_remaining' => Product::query()->where('code', $productCode)->count(),
            'category_remaining' => Category::query()->where('slug', $categorySlug)->count(),
            'unit_remaining' => Unit::query()->where('slug', $unitSlug)->count(),
        ];
    });

    fwrite(STDOUT, json_encode(['success' => true, ...$result]));
    exit;
}

fwrite(STDERR, 'Gunakan action prepare, verify, atau cleanup.');
exit(2);
