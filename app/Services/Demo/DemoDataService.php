<?php

namespace App\Services\Demo;

use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Category;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\StockAdjustment;
use App\Models\StockTransfer;
use App\Models\Unit;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Expense\ExpenseApprovalService;
use App\Services\Expense\ExpenseService;
use App\Services\Product\ProductService;
use App\Services\Sale\SaleService;
use App\Services\Sale\SaleVoidService;
use App\Services\Setting\StoreSettingService;
use App\Services\Stock\StockService;
use App\Services\StockAdjustment\StockAdjustmentService;
use App\Services\StockReceipt\StockReceiptService;
use App\Services\StockTransfer\StockTransferService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoDataService
{
    public function __construct(
        private readonly DemoProfileService $profiles,
        private readonly DemoDateService $dates,
        private readonly DemoExecutionContext $context,
        private readonly ProductService $products,
        private readonly StockService $stocks,
        private readonly StockReceiptService $receipts,
        private readonly StockAdjustmentService $adjustments,
        private readonly StockTransferService $transfers,
        private readonly SaleService $sales,
        private readonly SaleVoidService $voids,
        private readonly ExpenseService $expenses,
        private readonly ExpenseApprovalService $expenseApprovals,
        private readonly StoreSettingService $settings,
        private readonly AuditLogService $audit,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function seed(
        string $profileName,
        int $randomSeed,
        string $password,
        Command $output,
        bool $includeSettings = true,
    ): array {
        $profile = $this->profiles->get($profileName);
        $startedAt = microtime(true);
        $today = CarbonImmutable::today(config('app.timezone'));
        mt_srand($randomSeed);
        $previousUser = Auth::user();

        try {
            $output->newLine();
            $output->info('Menyiapkan master data demo');
            [$branches, $owner, $admins, $cashiers] = $this->createIdentityData($profile, $password);
            Auth::login($owner);
            [$categories, $units, $paymentMethods, $expenseCategories] = $this->createMasterData($owner);

            if ($includeSettings) {
                $this->configureSettings($owner);
            }

            $output->info('Membuat katalog produk');
            $catalog = [];
            $output->withProgressBar(range(1, (int) $profile['products']), function (int $index) use (
                &$catalog,
                $categories,
                $units,
                $owner,
            ): void {
                $catalog[] = $this->createProduct($index, $categories, $units, $owner);
            });
            $output->newLine(2);

            $output->info('Membuat stok awal melalui StockService');
            $available = $this->createInitialStocks($branches, collect($catalog), $owner, $output, $today);

            $output->info('Membuat barang masuk historis');
            $this->createReceipts($profile, $branches, collect($catalog), $admins, $output, $today);

            $output->info('Membuat histori perubahan harga');
            $this->createPriceChanges($profile, collect($catalog), $owner, $output, $today);

            $output->info('Membuat transaksi penjualan historis');
            $createdSales = $this->createSales(
                $profile,
                $branches,
                $cashiers,
                $available,
                $paymentMethods,
                $output,
                $today,
                $randomSeed,
            );

            $output->info('Membuat pembatalan transaksi');
            $this->createVoids($profile, $createdSales, $owner, $output, $today);

            $output->info('Membuat pengeluaran historis');
            $this->createExpenses(
                $profile,
                $branches,
                $admins,
                $owner,
                $expenseCategories,
                $output,
                $today,
            );

            $output->info('Membuat penyesuaian dan mutasi stok');
            $this->createAdjustments($profile, $branches, $admins, $output, $today);
            $this->createTransfers($profile, $branches, $admins, $owner, $output, $today);
            $this->tuneFinalStocks($branches, collect($catalog), $owner, $output, $today);

            $this->createReprintActivities($profile, $createdSales, $owner, $output);
            $this->createFailedLoginActivities($owner);
            $this->deactivateProducts(collect($catalog), $owner);

            $statistics = $this->statistics($profileName, $randomSeed, $startedAt);
            $this->writeManifest($statistics);

            return $statistics;
        } finally {
            if ($previousUser instanceof User) {
                Auth::login($previousUser);
            } else {
                Auth::logout();
            }
        }
    }

    /**
     * @param  array<string, int|float|string>  $profile
     * @return array{0: Collection<int, Branch>, 1: User, 2: Collection<int, User>, 3: array<int, Collection<int, User>>}
     */
    private function createIdentityData(array $profile, string $password): array
    {
        $roles = collect([
            'owner' => ['name' => 'Owner', 'description' => 'Pemilik toko demo.'],
            'admin' => ['name' => 'Admin/Kepala Cabang', 'description' => 'Pengelola cabang demo.'],
            'cashier' => ['name' => 'Kasir/Pegawai', 'description' => 'Kasir cabang demo.'],
        ])->mapWithKeys(fn (array $data, string $slug): array => [
            $slug => Role::query()->firstOrCreate(
                ['slug' => $slug],
                [...$data, 'is_active' => true],
            ),
        ]);

        $branchRows = [
            ['DMO1', 'Cabang Demo Utama', 'Jl. Pertanian Utama No. 1', '0812-1000-0001'],
            ['DMO2', 'Cabang Demo Timur', 'Jl. Tani Timur No. 12', '0812-1000-0002'],
            ['DMO3', 'Cabang Demo Barat', 'Jl. Kebun Barat No. 8', '0812-1000-0003'],
            ['DMO4', 'Cabang Demo Selatan', 'Jl. Sawah Selatan No. 15', '0812-1000-0004'],
        ];
        $branches = collect($branchRows)->map(fn (array $row): Branch => Branch::query()->create([
            'code' => $row[0],
            'name' => $row[1],
            'address' => $row[2],
            'phone' => $row[3],
            'is_active' => true,
        ]));
        $owner = User::query()->create([
            'role_id' => $roles['owner']->getKey(),
            'branch_id' => null,
            'name' => 'Owner Demo',
            'username' => 'demo_owner',
            'email' => 'demo_owner@example.test',
            'password' => $password,
            'is_active' => true,
        ]);
        Auth::login($owner);
        $admins = collect();
        $cashiers = [];
        $cashiersPerBranch = (int) ceil(((int) $profile['cashiers']) / 4);

        foreach ($branches as $branchIndex => $branch) {
            $suffix = mb_strtolower($branch->code);
            $admins->push(User::query()->create([
                'role_id' => $roles['admin']->getKey(),
                'branch_id' => $branch->getKey(),
                'name' => 'Admin '.$branch->name,
                'username' => "demo_admin_{$suffix}",
                'email' => "demo_admin_{$suffix}@example.test",
                'password' => $password,
                'is_active' => true,
            ]));
            $cashiers[$branch->getKey()] = collect();

            for ($number = 1; $number <= $cashiersPerBranch; $number++) {
                $currentCount = collect($cashiers)->flatten()->count();

                if ($currentCount >= (int) $profile['cashiers']) {
                    break;
                }

                $username = sprintf('demo_kasir_%s_%02d', $suffix, $number);
                $cashiers[$branch->getKey()]->push(User::query()->create([
                    'role_id' => $roles['cashier']->getKey(),
                    'branch_id' => $branch->getKey(),
                    'name' => sprintf('Kasir %s %02d', $branch->code, $number),
                    'username' => $username,
                    'email' => "{$username}@example.test",
                    'password' => $password,
                    'is_active' => true,
                ]));
            }
        }

        return [$branches, $owner, $admins, $cashiers];
    }

    /**
     * @return array{0: Collection<string, Category>, 1: Collection<string, Unit>, 2: Collection<string, PaymentMethod>, 3: Collection<int, ExpenseCategory>}
     */
    private function createMasterData(User $owner): array
    {
        Auth::login($owner);
        $categoryNames = [
            'Insektisida', 'Herbisida', 'Fungisida', 'Rodentisida', 'Pupuk Cair',
            'Pupuk Padat', 'Benih', 'Zat Pengatur Tumbuh', 'Perekat dan Perata',
            'Alat Semprot', 'Peralatan Pertanian', 'Perlengkapan Toko', 'Lainnya',
        ];
        $categories = collect($categoryNames)->mapWithKeys(function (string $name): array {
            $slug = Str::slug($name);
            $category = Category::query()->firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'description' => 'Kategori katalog demo.', 'is_active' => true],
            );

            if (! $category->is_active) {
                $category->update(['is_active' => true]);
            }

            return [$slug => $category];
        });
        $unitRows = [
            ['Botol', null], ['Bungkus', null], ['Sachet', null], ['Liter', 'L'],
            ['Mililiter', 'ml'], ['Kilogram', 'kg'], ['Gram', 'g'], ['Karung', null],
            ['Dus', null], ['Kaleng', null], ['Jeriken', null], ['Unit', 'unit'], ['Lainnya', null],
        ];
        $units = collect($unitRows)->mapWithKeys(function (array $row): array {
            $slug = Str::slug($row[0]);
            $unit = Unit::query()->firstOrCreate(
                ['slug' => $slug],
                ['name' => $row[0], 'symbol' => $row[1], 'is_active' => true],
            );

            if (! $unit->is_active) {
                $unit->update(['is_active' => true]);
            }

            return [$slug => $unit];
        });
        $paymentMethods = collect([
            ['CASH', 'Tunai', 'cash', 1],
            ['QRIS', 'QRIS', 'non_cash', 2],
            ['TRANSFER', 'Transfer Bank', 'non_cash', 3],
        ])->mapWithKeys(function (array $row): array {
            $payment = PaymentMethod::query()->firstOrCreate(
                ['code' => $row[0]],
                ['name' => $row[1], 'type' => $row[2], 'is_active' => true, 'sort_order' => $row[3]],
            );

            if (! $payment->is_active) {
                $payment->update(['is_active' => true]);
            }

            return [$row[0] => $payment];
        });
        $expenseCategories = collect([
            'Operasional Toko', 'Listrik', 'Air', 'Internet', 'Transportasi', 'Perawatan',
            'Kebersihan', 'Administrasi', 'Perlengkapan', 'Konsumsi', 'Sewa',
            'Gaji dan Upah', 'Lainnya',
        ])->map(fn (string $name): ExpenseCategory => ExpenseCategory::query()->firstOrCreate(
            ['slug' => Str::slug($name)],
            [
                'name' => $name,
                'description' => 'Kategori pengeluaran demo.',
                'is_active' => true,
                'created_by' => $owner->getKey(),
                'updated_by' => $owner->getKey(),
            ],
        ));

        return [$categories, $units, $paymentMethods, $expenseCategories];
    }

    private function configureSettings(User $owner): void
    {
        $this->settings->updateGeneral([
            'store_name' => 'Toko Tani Makmur Demo',
            'store_address' => 'Jl. Pertanian Raya No. 10',
            'store_phone' => '0812-0000-0000',
        ], $owner);
        $this->settings->updateReceipt([
            'receipt_footer_message' => 'Terima kasih telah berbelanja di Toko Tani Makmur.',
            'receipt_additional_information' => 'Simpan nota sebagai bukti transaksi.',
            'default_paper_width' => 80,
            'show_logo' => false,
            'show_store_address' => true,
            'show_store_phone' => true,
            'show_branch_address' => true,
            'show_branch_phone' => true,
            'show_product_code' => true,
            'show_transaction_notes' => true,
            'show_copy_label' => true,
            'number_format' => 'branch_date_sequence',
            'number_prefix' => null,
            'number_separator' => '-',
            'sequence_digits' => 4,
        ], $owner);
        $this->settings->updateBusiness([
            'default_minimum_stock' => '5',
            'maximum_cashier_discount' => '25000',
        ], $owner);
    }

    /**
     * @param  Collection<string, Category>  $categories
     * @param  Collection<string, Unit>  $units
     */
    private function createProduct(int $index, Collection $categories, Collection $units, User $owner): Product
    {
        $patterns = [
            ['insektisida', 'botol', 'Insektisida Protek', '250 ml'],
            ['insektisida', 'botol', 'Insektisida Garda Tani', '500 ml'],
            ['herbisida', 'liter', 'Herbisida Bersih Lahan', '1 liter'],
            ['fungisida', 'bungkus', 'Fungisida Daun Sehat', '500 gram'],
            ['pupuk-cair', 'liter', 'Pupuk Cair Tumbuh Subur', '1 liter'],
            ['pupuk-padat', 'karung', 'Pupuk NPK Tani Makmur', '25 kilogram'],
            ['benih', 'sachet', 'Benih Jagung Hibrida JM', '250 gram'],
            ['benih', 'bungkus', 'Benih Cabai Unggul CU', '100 gram'],
            ['perekat-dan-perata', 'botol', 'Perekat Semprot Prima', '250 ml'],
            ['alat-semprot', 'unit', 'Sprayer Manual Kebun', '16 liter'],
            ['peralatan-pertanian', 'unit', 'Sarung Tangan Pertanian', null],
            ['perlengkapan-toko', 'unit', 'Masker Semprot', null],
            ['rodentisida', 'bungkus', 'Rodentisida Lumbung Aman', '250 gram'],
        ];
        $brands = ['AgroMakmur', 'TaniJaya', 'LahanSubur', 'PanenPrima', 'AgroSentosa', 'KebunMaju', 'TumbuhHijau'];
        $pattern = $patterns[($index - 1) % count($patterns)];
        $purchase = 5000 + (($index * 17500) % 1495000);
        $purchase = (int) (round($purchase / 2500) * 2500);
        $markup = [1.08, 1.15, 1.22, 1.30, 1.40, 1.55][$index % 6];
        $selling = (int) (ceil(($purchase * $markup) / 2500) * 2500);

        return $this->products->create([
            'category_id' => $categories[$pattern[0]]->getKey(),
            'unit_id' => $units[$pattern[1]]->getKey(),
            'code' => sprintf('DMO-%04d', $index),
            'barcode' => $index % 5 === 0 ? null : sprintf('08990%08d', $index),
            'name' => sprintf('%s %02d%s', $pattern[2], $index, $pattern[3] ? ' '.$pattern[3] : ''),
            'brand' => $index % 11 === 0 ? null : $brands[$index % count($brands)],
            'size' => $pattern[3],
            'purchase_price' => (string) $purchase,
            'selling_price' => (string) $selling,
            'minimum_stock' => (string) (3 + ($index % 8)),
        ], $owner);
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return array<int, Collection<int, Product>>
     */
    private function createInitialStocks(
        Collection $branches,
        Collection $products,
        User $owner,
        Command $output,
        CarbonImmutable $today,
    ): array {
        $available = [];
        $work = [];

        foreach ($branches as $branchIndex => $branch) {
            $available[$branch->getKey()] = collect();

            foreach ($products as $productIndex => $product) {
                if (($productIndex + $branchIndex * 3) % 10 === 0) {
                    continue;
                }

                $work[] = [$branch, $product, $branchIndex, $productIndex];
            }
        }

        $this->context->at($today->subDays(365)->setTime(8, 0), function () use (
            $work,
            &$available,
            $owner,
            $output,
        ): void {
            $output->withProgressBar($work, function (array $row) use (&$available, $owner): void {
                [$branch, $product, $branchIndex, $productIndex] = $row;
                Auth::login($owner);
                $quantity = (string) (5000 + (($productIndex * 43 + $branchIndex * 97) % 2000));
                $this->stocks->setInitialStock(
                    $branch,
                    $product,
                    $quantity,
                    'Persediaan awal simulasi toko demo.',
                    $owner,
                );
                $available[$branch->getKey()]->push($product);
            });
        });
        $output->newLine(2);

        return $available;
    }

    /**
     * @param  array<string, int|float|string>  $profile
     * @param  Collection<int, Product>  $products
     * @param  Collection<int, User>  $admins
     */
    private function createReceipts(
        array $profile,
        Collection $branches,
        Collection $products,
        Collection $admins,
        Command $output,
        CarbonImmutable $today,
    ): void {
        $suppliers = [
            'PT Agro Makmur Distribusi', 'CV Tani Jaya Sentosa', 'UD Pupuk Nusantara',
            'CV Benih Unggul', 'PT Sarana Pertanian Indonesia', 'UD Lahan Subur', 'CV Panen Prima',
        ];
        $count = (int) $profile['receipts'];
        $output->withProgressBar(range(0, $count - 1), function (int $index) use (
            $count,
            $branches,
            $products,
            $admins,
            $suppliers,
            $today,
        ): void {
            $branchIndex = $index % $branches->count();
            $branch = $branches[$branchIndex];
            $actor = $admins[$branchIndex];
            $date = $this->dates->historical($index, $count, $today, 350);
            $itemCount = min($products->count(), 3 + ($index % 8));
            $items = [];

            for ($offset = 0; $offset < $itemCount; $offset++) {
                $product = $products[($index * 7 + $offset * 11) % $products->count()];
                $items[] = [
                    'product_id' => $product->getKey(),
                    'quantity' => (string) (25 + (($index + $offset) % 75)),
                    'purchase_price' => (string) round(((float) $product->purchase_price) * (0.95 + (($index + $offset) % 14) / 100)),
                ];
            }

            $this->context->at($date, function () use ($branch, $date, $suppliers, $index, $items, $actor): void {
                Auth::login($actor);
                $this->receipts->create(
                    $branch,
                    $date,
                    $suppliers[$index % count($suppliers)],
                    'Pengadaan berkala untuk kebutuhan operasional demo.',
                    $items,
                    $actor,
                );
            });
        });
        $output->newLine(2);
    }

    /**
     * @param  array<string, int|float|string>  $profile
     * @param  Collection<int, Product>  $products
     */
    private function createPriceChanges(
        array $profile,
        Collection $products,
        User $owner,
        Command $output,
        CarbonImmutable $today,
    ): void {
        $reasons = [
            'Penyesuaian harga dari pemasok.',
            'Kenaikan biaya distribusi.',
            'Program harga promosi berakhir.',
            'Penyesuaian margin penjualan.',
            'Harga beli terbaru berubah.',
        ];
        $count = (int) $profile['price_changes'];
        $output->withProgressBar(range(0, $count - 1), function (int $index) use (
            $products,
            $owner,
            $today,
            $count,
            $reasons,
        ): void {
            $product = $products[($index * 13) % $products->count()];
            $date = $this->dates->historical($index, $count, $today, 340);
            $purchase = (int) (round(((float) $product->purchase_price * (1.01 + ($index % 5) / 100)) / 500) * 500);
            $selling = (int) (ceil(max($purchase * 1.12, (float) $product->selling_price * 1.02) / 500) * 500);

            $this->context->at($date, function () use (
                $product,
                $purchase,
                $selling,
                $reasons,
                $index,
                $owner,
            ): void {
                Auth::login($owner);
                $fresh = $product->refresh();
                $updated = $this->products->update($fresh, [
                    'category_id' => $fresh->category_id,
                    'unit_id' => $fresh->unit_id,
                    'code' => $fresh->code,
                    'barcode' => $fresh->barcode,
                    'name' => $fresh->name,
                    'brand' => $fresh->brand,
                    'size' => $fresh->size,
                    'purchase_price' => (string) $purchase,
                    'selling_price' => (string) $selling,
                    'minimum_stock' => (string) $fresh->minimum_stock,
                    'price_change_reason' => $reasons[$index % count($reasons)],
                ], $owner);
                $product->setRawAttributes($updated->getAttributes(), true);
            });
        });
        $output->newLine(2);
    }

    /**
     * @param  array<string, int|float|string>  $profile
     * @param  array<int, Collection<int, User>>  $cashiers
     * @param  array<int, Collection<int, Product>>  $available
     * @param  Collection<string, PaymentMethod>  $paymentMethods
     * @return Collection<int, Sale>
     */
    private function createSales(
        array $profile,
        Collection $branches,
        array $cashiers,
        array $available,
        Collection $paymentMethods,
        Command $output,
        CarbonImmutable $today,
        int $randomSeed,
    ): Collection {
        $count = (int) $profile['sales'];
        $saleDates = $this->dates->saleDates(
            $count,
            $branches->count(),
            (int) $profile['today_sales_per_branch'],
            $randomSeed,
            $today,
        );
        $created = collect();
        $todayCounters = array_fill(0, 4, 0);
        $branchPattern = [0, 0, 0, 0, 1, 1, 1, 2, 2, 3];

        $output->withProgressBar(range(0, $count - 1), function (int $index) use (
            $saleDates,
            $branches,
            $cashiers,
            $available,
            $paymentMethods,
            &$created,
            &$todayCounters,
            $branchPattern,
            $randomSeed,
        ): void {
            $date = $saleDates[$index];

            if ($date->isToday()) {
                $branchIndex = array_search(min($todayCounters), $todayCounters, true);
                $todayCounters[$branchIndex]++;
            } else {
                $branchIndex = $branchPattern[($index * 7 + $randomSeed) % count($branchPattern)];
            }

            $branch = $branches[$branchIndex];
            $branchCashiers = $cashiers[$branch->getKey()];
            $actor = $branchCashiers[$index % $branchCashiers->count()];
            $pool = $available[$branch->getKey()];
            $itemCount = min($pool->count(), 1 + (($index * 3 + 1) % 8));
            $selected = [];
            $items = [];
            $estimatedSubtotal = 0.0;

            for ($offset = 0; $offset < $itemCount; $offset++) {
                $candidate = (int) floor(pow((($index * 17 + $offset * 29) % 100) / 100, 2) * $pool->count());
                $candidate = min($pool->count() - 1, $candidate);

                while (isset($selected[$pool[$candidate]->getKey()])) {
                    $candidate = ($candidate + 1) % $pool->count();
                }

                $product = $pool[$candidate]->refresh();
                $selected[$product->getKey()] = true;
                $fractional = in_array($product->unit?->slug, ['liter', 'kilogram'], true);
                $quantity = $fractional && ($index + $offset) % 5 === 0
                    ? ['0.500', '1.250', '2.500'][($index + $offset) % 3]
                    : (string) (1 + (($index + $offset) % 4));
                $items[] = ['product_id' => $product->getKey(), 'quantity' => $quantity];
                $estimatedSubtotal += (float) $product->selling_price * (float) $quantity;
            }

            $discount = $index % 5 === 0
                ? (string) min(25000, max(0, ((int) floor($estimatedSubtotal / 10000)) * 5000))
                : '0';
            $paymentRoll = $index % 20;
            $payment = $paymentRoll < 11
                ? $paymentMethods['CASH']
                : ($paymentRoll < 17 ? $paymentMethods['QRIS'] : $paymentMethods['TRANSFER']);
            $estimatedTotal = max(0, $estimatedSubtotal - (float) $discount);
            $amountReceived = $payment->type === 'cash'
                ? (string) (max(1, (int) ceil($estimatedTotal / 50000)) * 50000)
                : null;

            $sale = $this->context->at($date, function () use (
                $actor,
                $branch,
                $items,
                $discount,
                $payment,
                $amountReceived,
                $index,
                $randomSeed,
            ): Sale {
                Auth::login($actor);

                return $this->sales->createSale(
                    $actor,
                    $branch,
                    $items,
                    $discount,
                    $payment,
                    $amountReceived,
                    hash('sha256', "demo:{$randomSeed}:sale:{$index}"),
                    'complete',
                    $index % 9 === 0 ? 'Transaksi pelanggan tetap toko demo.' : null,
                    null,
                    null,
                    '192.0.2.'.(10 + ($index % 100)),
                    'DemoSeeder/1.0',
                );
            });
            $created->push($sale);
        });
        $output->newLine(2);

        return $created;
    }

    /**
     * @param  array<string, int|float|string>  $profile
     * @param  Collection<int, Sale>  $sales
     */
    private function createVoids(
        array $profile,
        Collection $sales,
        User $owner,
        Command $output,
        CarbonImmutable $today,
    ): void {
        $reasons = [
            'Kasir salah memasukkan jumlah produk.',
            'Pelanggan membatalkan seluruh pembelian.',
            'Produk yang dipilih tidak sesuai permintaan pelanggan.',
            'Transaksi tercatat dua kali.',
            'Pembayaran non-tunai gagal diverifikasi.',
        ];
        $count = max(1, (int) round($sales->count() * (float) $profile['void_rate']));
        $candidates = $sales->values();
        $output->withProgressBar(range(0, $count - 1), function (int $index) use (
            $candidates,
            $owner,
            $today,
            $reasons,
        ): void {
            $sale = $candidates[($index * 31 + 7) % $candidates->count()];
            $date = $today->subDays($index % 7)->setTime(17, $index % 60);
            $this->context->at($date, function () use ($sale, $owner, $reasons, $index): void {
                Auth::login($owner);
                $this->voids->voidSale(
                    $sale,
                    $owner,
                    $reasons[$index % count($reasons)],
                    true,
                    '192.0.2.200',
                    'DemoSeeder/1.0',
                );
            });
        });
        $output->newLine(2);
    }

    /**
     * @param  array<string, int|float|string>  $profile
     * @param  Collection<int, User>  $admins
     * @param  Collection<int, ExpenseCategory>  $categories
     */
    private function createExpenses(
        array $profile,
        Collection $branches,
        Collection $admins,
        User $owner,
        Collection $categories,
        Command $output,
        CarbonImmutable $today,
    ): void {
        $count = (int) $profile['expenses'];
        $rejections = [
            'Bukti dan deskripsi pengeluaran belum memadai.',
            'Nominal tidak sesuai dengan catatan pembelian.',
            'Pengeluaran tidak berkaitan dengan operasional cabang.',
            'Pengeluaran telah dicatat pada transaksi lain.',
            'Data perlu diperbaiki sebelum diajukan kembali.',
        ];
        $output->withProgressBar(range(0, $count - 1), function (int $index) use (
            $count,
            $branches,
            $admins,
            $owner,
            $categories,
            $today,
            $rejections,
        ): void {
            $branchIndex = $index % $branches->count();
            $branch = $branches[$branchIndex];
            $actor = $admins[$branchIndex];
            $category = $categories[$index % $categories->count()];
            $date = $this->dates->historical($index, $count, $today, 360);
            $amount = (string) (10000 + (($index * 137500) % 9990000));

            $expense = $this->context->at($date, function () use (
                $branch,
                $category,
                $date,
                $amount,
                $actor,
                $index,
            ): Expense {
                Auth::login($actor);

                return $this->expenses->create(
                    $branch,
                    $category,
                    $date,
                    $amount,
                    "Biaya {$category->name} untuk operasional demo periode ".($index + 1).'.',
                    null,
                    $actor,
                    '192.0.2.'.(50 + ($index % 100)),
                    'DemoSeeder/1.0',
                );
            });

            $approvedTarget = max(1, (int) floor($count * 0.75));
            $pendingTarget = max(1, (int) floor($count * 0.15));

            if ($index < $approvedTarget) {
                $this->context->at($date->addHours(3), function () use ($expense, $owner): void {
                    Auth::login($owner);
                    $this->expenseApprovals->approve($expense, $owner, '192.0.2.201', 'DemoSeeder/1.0');
                });
            } elseif ($index >= $approvedTarget + $pendingTarget) {
                $this->context->at($date->addHours(3), function () use (
                    $expense,
                    $owner,
                    $rejections,
                    $index,
                ): void {
                    Auth::login($owner);
                    $this->expenseApprovals->reject(
                        $expense,
                        $rejections[$index % count($rejections)],
                        $owner,
                        '192.0.2.201',
                        'DemoSeeder/1.0',
                    );
                });
            }
        });
        $output->newLine(2);
    }

    /**
     * @param  array<string, int|float|string>  $profile
     * @param  Collection<int, User>  $admins
     */
    private function createAdjustments(
        array $profile,
        Collection $branches,
        Collection $admins,
        Command $output,
        CarbonImmutable $today,
    ): void {
        $types = [
            StockAdjustment::TYPE_ADDITION,
            StockAdjustment::TYPE_DAMAGED,
            StockAdjustment::TYPE_LOST,
            StockAdjustment::TYPE_SUBTRACTION,
        ];
        $reasons = [
            'Penambahan hasil pemeriksaan stok fisik.',
            'Barang rusak saat penyimpanan dan tidak dapat dijual.',
            'Selisih barang hilang berdasarkan stok opname.',
            'Koreksi selisih pencatatan stok fisik bulanan.',
        ];
        $count = (int) $profile['adjustments'];
        $output->withProgressBar(range(0, $count - 1), function (int $index) use (
            $branches,
            $admins,
            $types,
            $reasons,
            $today,
        ): void {
            $branchIndex = $index % $branches->count();
            $branch = $branches[$branchIndex];
            $stock = BranchStock::query()
                ->where('branch_id', $branch->getKey())
                ->where('quantity', '>', 10)
                ->whereHas('product', fn ($query) => $query
                    ->where('code', 'like', 'DMO-%')
                    ->where('is_active', true))
                ->orderBy('product_id')
                ->get()
                ->values();
            $product = $stock[($index * 19 + 3) % $stock->count()]->product;
            $actor = $admins[$branchIndex];
            $date = $today->subDays(20 - ($index % 20))->setTime(16, $index % 60);
            $this->context->at($date, function () use (
                $branch,
                $product,
                $types,
                $index,
                $reasons,
                $actor,
            ): void {
                Auth::login($actor);
                $this->adjustments->create(
                    $branch,
                    $product,
                    $types[$index % count($types)],
                    (string) (1 + ($index % 3)),
                    null,
                    $reasons[$index % count($reasons)],
                    $actor,
                );
            });
        });
        $output->newLine(2);
    }

    /**
     * @param  array<string, int|float|string>  $profile
     * @param  Collection<int, User>  $admins
     */
    private function createTransfers(
        array $profile,
        Collection $branches,
        Collection $admins,
        User $owner,
        Command $output,
        CarbonImmutable $today,
    ): void {
        $count = (int) $profile['transfers'];
        $output->withProgressBar(range(0, $count - 1), function (int $index) use (
            $branches,
            $admins,
            $owner,
            $today,
        ): void {
            $sourceIndex = $index % $branches->count();
            $destinationIndex = ($sourceIndex + 1 + ($index % 2)) % $branches->count();
            $source = $branches[$sourceIndex];
            $destination = $branches[$destinationIndex];
            $sourceStocks = BranchStock::query()
                ->where('branch_id', $source->getKey())
                ->where('quantity', '>', 10)
                ->whereHas('product', fn ($query) => $query
                    ->where('code', 'like', 'DMO-%')
                    ->where('is_active', true))
                ->with('product')
                ->orderBy('product_id')
                ->get();
            $product = $sourceStocks[($index * 23 + 1) % $sourceStocks->count()]->product;
            $actor = $admins[$sourceIndex];
            $date = $today->subDays(2)->addMinutes($index * 5);

            $this->context->at($date, function () use (
                $source,
                $destination,
                $product,
                $actor,
                $owner,
                $index,
            ): void {
                Auth::login($actor);
                $transfer = $this->transfers->request(
                    $source,
                    $destination,
                    $product,
                    (string) (2 + ($index % 4)),
                    'Pemerataan persediaan antar-cabang demo.',
                    $actor,
                );
                Auth::login($owner);

                if ($index % 6 === 5) {
                    $this->transfers->reject(
                        $transfer,
                        'Stok tujuan masih mencukupi untuk periode berjalan.',
                        $owner,
                    );
                } else {
                    $this->transfers->complete($transfer, $owner);
                }
            });
        });
        $output->newLine(2);
    }

    /**
     * @param  Collection<int, Product>  $products
     */
    private function tuneFinalStocks(
        Collection $branches,
        Collection $products,
        User $owner,
        Command $output,
        CarbonImmutable $today,
    ): void {
        $work = [];

        foreach ($branches as $branchIndex => $branch) {
            foreach ($products as $productIndex => $product) {
                $bucket = ($productIndex + $branchIndex * 2) % 10;

                if ($bucket >= 7) {
                    $work[] = [$branch, $product, $bucket];
                }
            }
        }

        $output->withProgressBar($work, function (array $row) use ($owner, $today): void {
            [$branch, $product, $bucket] = $row;
            $stock = BranchStock::query()
                ->where('branch_id', $branch->getKey())
                ->where('product_id', $product->getKey())
                ->first();

            if ($stock === null) {
                return;
            }

            $target = $bucket === 9 ? '0' : (string) $product->minimum_stock;

            if ((float) $stock->quantity === (float) $target) {
                return;
            }

            $this->context->at($today->subDay()->setTime(18, $product->getKey() % 60), function () use (
                $branch,
                $product,
                $target,
                $owner,
            ): void {
                Auth::login($owner);
                $this->adjustments->create(
                    $branch,
                    $product,
                    StockAdjustment::TYPE_CORRECTION,
                    null,
                    $target,
                    'Koreksi hasil stok opname akhir simulasi demo.',
                    $owner,
                );
            });
        });
        $output->newLine(2);
    }

    /**
     * @param  array<string, int|float|string>  $profile
     * @param  Collection<int, Sale>  $sales
     */
    private function createReprintActivities(
        array $profile,
        Collection $sales,
        User $owner,
        Command $output,
    ): void {
        $count = max(1, (int) round($sales->count() * (float) $profile['reprint_rate']));
        $output->withProgressBar(range(0, $count - 1), function (int $index) use ($sales, $owner): void {
            $sale = $sales[($index * 43 + 5) % $sales->count()]->refresh();
            $this->audit->record(
                'receipt_reprint_requested',
                'receipts',
                "Cetak ulang nota {$sale->invoice_number} diminta untuk simulasi.",
                $owner,
                (int) $sale->branch_id,
                $sale,
                ['invoice_number' => $sale->invoice_number, 'demo' => true],
                '192.0.2.210',
                'DemoSeeder/1.0',
            );
        });
        $output->newLine(2);
    }

    private function createFailedLoginActivities(User $owner): void
    {
        foreach (range(1, 4) as $index) {
            $this->audit->recordSafely(
                'login_failed',
                'authentication',
                'Percobaan login demo gagal.',
                null,
                null,
                null,
                ['identifier_masked' => "demo_k***{$index}", 'reason' => 'credential_mismatch'],
                '198.51.100.'.(20 + $index),
                'DemoSeeder/1.0',
            );
        }

        Auth::login($owner);
    }

    /**
     * @param  Collection<int, Product>  $products
     */
    private function deactivateProducts(Collection $products, User $owner): void
    {
        $count = max(1, (int) floor($products->count() * 0.04));

        foreach ($products->take(-$count) as $product) {
            Auth::login($owner);
            $this->products->updateStatus($product, false, $owner);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function statistics(string $profile, int $randomSeed, float $startedAt): array
    {
        $demoCashierIds = User::query()->where('username', 'like', 'demo_kasir_%')->pluck('id');
        $saleQuery = Sale::query()->whereIn('cashier_id', $demoCashierIds);

        return [
            'profile' => $profile,
            'random_seed' => $randomSeed,
            'started_at' => now()->subSeconds((int) (microtime(true) - $startedAt))->toIso8601String(),
            'finished_at' => now()->toIso8601String(),
            'duration_seconds' => round(microtime(true) - $startedAt, 3),
            'database' => $this->databaseName(),
            'counts' => [
                'branches' => Branch::query()->where('code', 'like', 'DMO%')->count(),
                'users' => User::query()->where('username', 'like', 'demo_%')->count(),
                'products' => Product::query()->where('code', 'like', 'DMO-%')->count(),
                'branch_stocks' => BranchStock::query()
                    ->whereHas('product', fn ($query) => $query->where('code', 'like', 'DMO-%'))
                    ->count(),
                'sales' => (clone $saleQuery)->count(),
                'sale_items' => (clone $saleQuery)->withCount('items')->get()->sum('items_count'),
                'voids' => (clone $saleQuery)->where('status', Sale::STATUS_VOIDED)->count(),
                'expenses' => Expense::query()
                    ->whereHas('creator', fn ($query) => $query->where('username', 'like', 'demo_%'))
                    ->count(),
                'stock_transfers' => StockTransfer::query()
                    ->whereHas('requester', fn ($query) => $query->where('username', 'like', 'demo_%'))
                    ->count(),
            ],
            'status' => 'completed',
        ];
    }

    /**
     * @param  array<string, mixed>  $statistics
     */
    private function writeManifest(array $statistics): void
    {
        Storage::disk('local')->put(
            'demo/demo-seed-manifest.json',
            json_encode($statistics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        );
    }

    private function databaseName(): string
    {
        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        return $database === ':memory:'
            ? $database
            : basename(str_replace('\\', '/', $database));
    }
}
