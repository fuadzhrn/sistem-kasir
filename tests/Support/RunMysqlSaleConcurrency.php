<?php

use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$connection = config('database.default');
$configuration = config("database.connections.{$connection}");

if ($configuration['driver'] !== 'mysql') {
    fwrite(STDOUT, json_encode([
        'success' => false,
        'reason' => 'Koneksi aplikasi bukan MySQL.',
    ]));
    exit(2);
}

$database = 'sistem_kasir_stage13_ct_'.getmypid();

if (! preg_match('/\Asistem_kasir_stage13_ct_\d+\z/', $database)) {
    throw new RuntimeException('Nama database sementara tidak aman.');
}

$host = $configuration['host'];
$port = $configuration['port'];
$charset = $configuration['charset'] ?? 'utf8mb4';
$dsn = "mysql:host={$host};port={$port};charset={$charset}";
$server = new PDO(
    $dsn,
    $configuration['username'],
    $configuration['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
);
$exists = $server->prepare('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?');
$exists->execute([$database]);

if ($exists->fetchColumn() !== false) {
    fwrite(STDOUT, json_encode([
        'success' => false,
        'reason' => 'Database sementara sudah ada; pengujian dibatalkan tanpa mengubahnya.',
    ]));
    exit(3);
}

$barrier = sys_get_temp_dir().DIRECTORY_SEPARATOR.$database.'.barrier';
$databaseRemoved = false;

try {
    $server->exec(
        'CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
    );
    config(["database.connections.{$connection}.database" => $database]);
    DB::purge($connection);
    DB::setDefaultConnection($connection);
    Artisan::call('migrate', ['--force' => true]);

    $cashierRole = Role::query()->create([
        'name' => 'Kasir',
        'slug' => 'cashier',
        'description' => null,
        'is_active' => true,
    ]);
    $branch = Branch::query()->create([
        'code' => 'RACE',
        'name' => 'Cabang Concurrency',
        'address' => null,
        'phone' => null,
        'is_active' => true,
    ]);
    $users = collect(['Kasir A', 'Kasir B'])->map(
        fn (string $name, int $index): User => User::query()->create([
            'role_id' => $cashierRole->getKey(),
            'branch_id' => $branch->getKey(),
            'name' => $name,
            'username' => 'concurrency.cashier.'.($index + 1),
            'email' => 'concurrency'.($index + 1).'@example.test',
            'password' => 'temporary-test-password',
            'is_active' => true,
        ]),
    );
    $category = Category::query()->create([
        'name' => 'Kategori Uji',
        'slug' => 'kategori-uji',
        'description' => null,
        'is_active' => true,
    ]);
    $unit = Unit::query()->create([
        'name' => 'Unit',
        'symbol' => 'u',
        'slug' => 'unit',
        'is_active' => true,
    ]);
    $product = Product::query()->create([
        'category_id' => $category->getKey(),
        'unit_id' => $unit->getKey(),
        'code' => 'RACE-001',
        'barcode' => null,
        'name' => 'Produk Stok Terakhir',
        'brand' => null,
        'size' => null,
        'purchase_price' => '99999.00',
        'selling_price' => '20000.00',
        'minimum_stock' => '0.000',
        'image_path' => null,
        'is_active' => true,
    ]);
    BranchStock::query()->create([
        'branch_id' => $branch->getKey(),
        'product_id' => $product->getKey(),
        'quantity' => '1.000',
        'average_cost' => '12500.00',
    ]);
    $payment = PaymentMethod::query()->create([
        'code' => 'CASH',
        'name' => 'Tunai',
        'type' => 'cash',
        'is_active' => true,
        'sort_order' => 1,
    ]);
    DB::disconnect($connection);

    $worker = __DIR__.DIRECTORY_SEPARATOR.'ConcurrentSaleWorker.php';
    $processes = [];

    foreach ($users as $index => $user) {
        $command = [
            PHP_BINARY,
            $worker,
            $database,
            $barrier,
            (string) $user->getKey(),
            (string) $branch->getKey(),
            (string) $product->getKey(),
            (string) $payment->getKey(),
            'concurrency-token-'.str_pad((string) ($index + 1), 24, '0', STR_PAD_LEFT),
        ];
        $pipes = [];
        $process = proc_open($command, [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes, dirname(__DIR__, 2));

        if (! is_resource($process)) {
            throw new RuntimeException('Proses kasir tidak dapat dijalankan.');
        }

        $processes[] = compact('process', 'pipes');
    }

    touch($barrier);
    $results = [];

    foreach ($processes as $running) {
        $stdout = stream_get_contents($running['pipes'][1]);
        $stderr = stream_get_contents($running['pipes'][2]);
        fclose($running['pipes'][1]);
        fclose($running['pipes'][2]);
        $exitCode = proc_close($running['process']);
        $results[] = [
            'exit_code' => $exitCode,
            'output' => json_decode($stdout, true),
            'stderr_empty' => trim($stderr) === '',
        ];
    }

    config(["database.connections.{$connection}.database" => $database]);
    DB::purge($connection);
    DB::setDefaultConnection($connection);
    $summary = [
        'success' => true,
        'workers' => $results,
        'stock_final' => BranchStock::query()->sole()->quantity,
        'sale_count' => DB::table('sales')->count(),
        'sale_item_count' => DB::table('sale_items')->count(),
        'movement_count' => DB::table('stock_movements')->count(),
        'activity_log_count' => DB::table('activity_logs')->count(),
        'negative_stock_count' => DB::table('branch_stocks')->where('quantity', '<', 0)->count(),
    ];
    Artisan::call('migrate:rollback', ['--step' => 1, '--force' => true]);
    $summary['rollback_removed_checkout_token'] = ! Schema::hasColumn('sales', 'checkout_token');
    Artisan::call('migrate', ['--force' => true]);
    $summary['remigrate_restored_checkout_token'] = Schema::hasColumn('sales', 'checkout_token');
} finally {
    DB::disconnect($connection);

    if (is_file($barrier)) {
        unlink($barrier);
    }

    if (preg_match('/\Asistem_kasir_stage13_ct_\d+\z/', $database)) {
        $server->exec('DROP DATABASE IF EXISTS `'.$database.'`');
        $databaseRemoved = true;
    }
}

$summary['temporary_database_removed'] = $databaseRemoved;
fwrite(STDOUT, json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
