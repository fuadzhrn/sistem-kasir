<?php

namespace App\Http\Controllers\Unit;

use App\Http\Controllers\Controller;
use App\Http\Requests\Unit\StoreUnitRequest;
use App\Http\Requests\Unit\UpdateUnitRequest;
use App\Http\Requests\Unit\UpdateUnitStatusRequest;
use App\Models\Unit;
use App\Services\MasterData\UnitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function __construct(private readonly UnitService $service) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Unit::class);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);
        $search = trim((string) ($filters['search'] ?? ''));
        $status = $filters['status'] ?? null;
        $units = Unit::query()
            ->withCount('products')
            ->when($search !== '', function ($query) use ($search): void {
                $term = $this->likeTerm($search);
                $query->where(fn ($subquery) => $subquery
                    ->where('name', 'like', $term)
                    ->orWhere('symbol', 'like', $term)
                    ->orWhere('slug', 'like', $term));
            })
            ->when($status !== null, fn ($query) => $query->where('is_active', $status === 'active'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();
        $summary = [
            'total' => Unit::query()->count(),
            'active' => Unit::query()->where('is_active', true)->count(),
            'inactive' => Unit::query()->where('is_active', false)->count(),
        ];

        return view('pages.units.index', compact('units', 'search', 'status', 'summary'));
    }

    public function create(): View
    {
        Gate::authorize('create', Unit::class);

        return view('pages.units.create');
    }

    public function store(StoreUnitRequest $request): RedirectResponse
    {
        Gate::authorize('create', Unit::class);
        $unit = $this->service->create($request->validated());

        return redirect()->route('units.show', $unit)->with('status', 'Satuan berhasil ditambahkan.');
    }

    public function show(Unit $unit): View
    {
        Gate::authorize('view', $unit);
        $unit->loadCount('products');

        return view('pages.units.show', compact('unit'));
    }

    public function edit(Unit $unit): View
    {
        Gate::authorize('update', $unit);

        return view('pages.units.edit', compact('unit'));
    }

    public function update(UpdateUnitRequest $request, Unit $unit): RedirectResponse
    {
        Gate::authorize('update', $unit);
        $unit = $this->service->update($unit, $request->validated());

        return redirect()->route('units.show', $unit)->with('status', 'Satuan berhasil diperbarui.');
    }

    public function updateStatus(UpdateUnitStatusRequest $request, Unit $unit): RedirectResponse
    {
        Gate::authorize('updateStatus', $unit);
        $unit = $this->service->updateStatus($unit, $request->boolean('is_active'));

        return back()->with('status', $unit->is_active
            ? 'Satuan berhasil diaktifkan.'
            : 'Satuan berhasil dinonaktifkan. Produk lama tetap terhubung.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        Gate::authorize('delete', $unit);
        $this->service->deleteIfUnused($unit);

        return redirect()->route('units.index')->with('status', 'Satuan berhasil dihapus.');
    }

    private function likeTerm(string $search): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
    }
}
