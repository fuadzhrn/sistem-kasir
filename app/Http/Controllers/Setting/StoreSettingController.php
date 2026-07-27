<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Setting\UpdateBusinessSettingRequest;
use App\Http\Requests\Setting\UpdateReceiptSettingRequest;
use App\Http\Requests\Setting\UpdateStoreGeneralSettingRequest;
use App\Models\PaymentMethod;
use App\Models\Setting;
use App\Services\Setting\StoreLogoService;
use App\Services\Setting\StoreSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Throwable;

class StoreSettingController extends Controller
{
    public function __construct(
        private readonly StoreSettingService $settings,
        private readonly StoreLogoService $logos,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Setting::class);
        $settings = $this->settings->allForOwnerPage();
        $logoUrl = $this->logos->logoUrl();
        $activePaymentMethods = PaymentMethod::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'type', 'sort_order', 'is_active']);
        $paymentMethodSummary = [
            'active' => $activePaymentMethods->count(),
            'inactive' => PaymentMethod::query()->where('is_active', false)->count(),
            'methods' => $activePaymentMethods->take(6),
        ];
        $lastUpdatedSetting = Setting::query()
            ->with('updater:id,name')
            ->whereIn('key', array_keys($settings))
            ->latest('updated_at')
            ->first(['id', 'key', 'updated_by', 'updated_at']);

        return view('pages.settings.store.index', compact(
            'settings',
            'logoUrl',
            'paymentMethodSummary',
            'lastUpdatedSetting',
        ));
    }

    public function updateGeneral(UpdateStoreGeneralSettingRequest $request): RedirectResponse
    {
        Gate::authorize('update', Setting::class);

        try {
            $this->settings->updateGeneral($request->validated(), $request->user());
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->withErrors([
                'settings' => 'Pengaturan gagal disimpan dan tidak ada perubahan yang diterapkan.',
            ]);
        }

        return back()->with('status', 'Informasi toko berhasil diperbarui.');
    }

    public function updateReceipt(UpdateReceiptSettingRequest $request): RedirectResponse
    {
        Gate::authorize('update', Setting::class);

        try {
            $this->settings->updateReceipt($request->validated(), $request->user());
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->withErrors([
                'settings' => 'Pengaturan gagal disimpan dan tidak ada perubahan yang diterapkan.',
            ]);
        }

        return back()->with('status', 'Pengaturan struk berhasil diperbarui. Format nomor nota berlaku untuk transaksi baru.');
    }

    public function updateBusiness(UpdateBusinessSettingRequest $request): RedirectResponse
    {
        Gate::authorize('update', Setting::class);

        try {
            $this->settings->updateBusiness($request->validated(), $request->user());
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->withErrors([
                'settings' => 'Pengaturan gagal disimpan dan tidak ada perubahan yang diterapkan.',
            ]);
        }

        return back()->with('status', 'Aturan bisnis berhasil diperbarui.');
    }
}
