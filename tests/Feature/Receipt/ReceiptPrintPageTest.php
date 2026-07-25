<?php

namespace Tests\Feature\Receipt;

use App\Models\Sale;
use App\Models\Setting;
use Illuminate\Support\Facades\Route;

class ReceiptPrintPageTest extends ReceiptPrintTestCase
{
    public function test_print_route_is_get_only_and_has_required_middleware(): void
    {
        $route = Route::getRoutes()->getByName('receipts.print');

        $this->assertNotNull($route);
        $this->assertSame(['GET', 'HEAD'], $route->methods());
        $this->assertContains('web', $route->middleware());
        $this->assertContains('auth', $route->middleware());
        $this->assertContains('active.user', $route->middleware());
        $this->assertContains('role:owner,admin,cashier', $route->middleware());
        $this->post('/receipts/1/print')->assertMethodNotAllowed();
    }

    public function test_receipt_page_contains_stored_transaction_snapshot_and_controls(): void
    {
        Setting::query()->create([
            'key' => 'store_name',
            'value' => 'Toko Agro Makmur',
            'type' => 'string',
            'group' => 'receipt',
            'is_public' => false,
        ]);
        Setting::query()->create([
            'key' => 'receipt_message',
            'value' => 'Terima kasih dan sampai jumpa.',
            'type' => 'string',
            'group' => 'receipt',
            'is_public' => false,
        ]);
        $branch = $this->createBranch('AAA', [
            'address' => 'Jalan Pertanian 10',
            'phone' => '08123456789',
        ]);
        $owner = $this->createUser('owner');
        $cashier = $this->createUser('cashier', $branch, ['name' => 'Kasir Struk']);
        $sale = $this->createSale($branch, $cashier, 'AAA-20260724-0001');

        $this->actingAs($owner)->get($this->printUrl($sale->id))
            ->assertOk()
            ->assertViewIs('pages.receipts.print')
            ->assertSee('Toko Agro Makmur')
            ->assertSee('Cabang AAA')
            ->assertSee('Jalan Pertanian 10')
            ->assertSee('08123456789')
            ->assertSee('AAA-20260724-0001')
            ->assertSee('24/07/2026 14:35')
            ->assertSee('Kasir Struk')
            ->assertSee('Pupuk Snapshot')
            ->assertSee('SNAP-001')
            ->assertSee('50 kg')
            ->assertSee('2,5')
            ->assertSee('Sak')
            ->assertSee('Rp70.000')
            ->assertSee('Rp170.000')
            ->assertSee('Rp5.000')
            ->assertSee('Rp200.000')
            ->assertSee('Rp30.000')
            ->assertSee('Terima kasih dan sampai jumpa.')
            ->assertSee('Cetak Struk')
            ->assertSee('Ukuran Kertas')
            ->assertSee('value="58"', false)
            ->assertSee('value="80"', false)
            ->assertSee('assets/css/print/receipt.css', false)
            ->assertSee('assets/js/pages/receipt.js', false);
    }

    public function test_status_and_copy_labels_are_visual_only_and_unknown_status_is_safe(): void
    {
        $branch = $this->createBranch('AAA');
        $owner = $this->createUser('owner');
        $cashier = $this->createUser('cashier', $branch);
        $requested = $this->createSale($branch, $cashier, 'AAA-20260724-0001', [
            'status' => Sale::STATUS_VOID_REQUESTED,
        ]);
        $voided = $this->createSale($branch, $cashier, 'AAA-20260724-0002', [
            'status' => Sale::STATUS_VOIDED,
        ]);
        $unknown = $this->createSale($branch, $cashier, 'AAA-20260724-0003', [
            'status' => 'legacy_status',
        ]);

        $this->actingAs($owner)->get($this->printUrl($requested->id))
            ->assertSee('MENUNGGU PEMBATALAN');
        $this->get($this->printUrl($voided->id, ['copy' => 1]))
            ->assertSee('TRANSAKSI DIBATALKAN')
            ->assertSee('SALINAN');
        $this->get($this->printUrl($unknown->id, ['copy' => 9]))
            ->assertOk()
            ->assertSee('STATUS TIDAK DIKENAL')
            ->assertDontSee('SALINAN');
    }
}
