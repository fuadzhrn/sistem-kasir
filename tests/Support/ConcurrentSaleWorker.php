<?php

use App\Exceptions\Sale\SaleCheckoutException;
use App\Models\Branch;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\Sale\SaleService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

[$script, $database, $barrier, $userId, $branchId, $productId, $paymentMethodId, $token] = $argv;

config(['database.connections.mysql.database' => $database]);
DB::purge('mysql');
DB::setDefaultConnection('mysql');

for ($attempt = 0; $attempt < 1000 && ! is_file($barrier); $attempt++) {
    usleep(10000);
}

if (! is_file($barrier)) {
    fwrite(STDOUT, json_encode(['status' => 'error', 'code' => 'BARRIER_TIMEOUT']));
    exit(2);
}

$startedAt = microtime(true);

try {
    $sale = app(SaleService::class)->createSale(
        actor: User::query()->findOrFail((int) $userId),
        branch: Branch::query()->findOrFail((int) $branchId),
        items: [[
            'product_id' => (int) $productId,
            'quantity' => '1.000',
        ]],
        discountAmount: '0.00',
        paymentMethod: PaymentMethod::query()->findOrFail((int) $paymentMethodId),
        amountReceived: '20000.00',
        checkoutToken: $token,
        paymentAction: 'no_print',
        notes: null,
        expectedSubtotal: '20000.00',
        expectedTotal: '20000.00',
        ipAddress: '127.0.0.1',
        userAgent: 'Stage13ConcurrencyWorker',
    );

    fwrite(STDOUT, json_encode([
        'status' => 'success',
        'sale_id' => $sale->getKey(),
        'started_at' => $startedAt,
        'finished_at' => microtime(true),
    ]));
} catch (SaleCheckoutException $exception) {
    fwrite(STDOUT, json_encode([
        'status' => 'rejected',
        'code' => $exception->errorCode,
        'started_at' => $startedAt,
        'finished_at' => microtime(true),
    ]));
} catch (Throwable $exception) {
    fwrite(STDOUT, json_encode([
        'status' => 'error',
        'code' => 'UNEXPECTED_EXCEPTION',
        'exception' => $exception::class,
    ]));
    exit(1);
}
