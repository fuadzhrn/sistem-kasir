<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Setting\DeleteStoreLogoRequest;
use App\Http\Requests\Setting\UpdateStoreLogoRequest;
use App\Models\Setting;
use App\Services\Setting\StoreLogoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Throwable;

class StoreLogoController extends Controller
{
    public function __construct(private readonly StoreLogoService $logos) {}

    public function update(UpdateStoreLogoRequest $request): RedirectResponse
    {
        Gate::authorize('updateLogo', Setting::class);

        try {
            $this->logos->update($request->file('logo'), $request->user());
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'logo' => 'Pengaturan gagal disimpan dan tidak ada perubahan yang diterapkan.',
            ]);
        }

        return back()->with('status', 'Logo toko berhasil diperbarui.');
    }

    public function destroy(DeleteStoreLogoRequest $request): RedirectResponse
    {
        Gate::authorize('deleteLogo', Setting::class);

        try {
            $this->logos->remove($request->user());
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'logo' => 'Pengaturan gagal disimpan dan tidak ada perubahan yang diterapkan.',
            ]);
        }

        return back()->with('status', 'Logo toko berhasil dihapus.');
    }
}
