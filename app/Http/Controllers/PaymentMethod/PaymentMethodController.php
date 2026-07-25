<?php

namespace App\Http\Controllers\PaymentMethod;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentMethod\StorePaymentMethodRequest;
use App\Http\Requests\PaymentMethod\UpdatePaymentMethodRequest;
use App\Http\Requests\PaymentMethod\UpdatePaymentMethodStatusRequest;
use App\Models\PaymentMethod;
use App\Services\MasterData\PaymentMethodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentMethodController extends Controller
{
    public function __construct(private readonly PaymentMethodService $service) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', PaymentMethod::class);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'type' => ['nullable', Rule::in(['cash', 'non_cash', 'other'])],
        ]);
        $search = trim((string) ($filters['search'] ?? ''));
        $status = $filters['status'] ?? null;
        $type = $filters['type'] ?? null;
        $paymentMethods = PaymentMethod::query()
            ->withCount('sales')
            ->when($search !== '', function ($query) use ($search): void {
                $term = $this->likeTerm($search);
                $query->where(fn ($subquery) => $subquery
                    ->where('code', 'like', $term)
                    ->orWhere('name', 'like', $term));
            })
            ->when($status !== null, fn ($query) => $query->where('is_active', $status === 'active'))
            ->when($type !== null, fn ($query) => $query->where('type', $type))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();
        $summary = [
            'total' => PaymentMethod::query()->count(),
            'active' => PaymentMethod::query()->where('is_active', true)->count(),
            'inactive' => PaymentMethod::query()->where('is_active', false)->count(),
        ];

        return view('pages.payment-methods.index', compact(
            'paymentMethods',
            'search',
            'status',
            'type',
            'summary',
        ));
    }

    public function create(): View
    {
        Gate::authorize('create', PaymentMethod::class);

        return view('pages.payment-methods.create');
    }

    public function store(StorePaymentMethodRequest $request): RedirectResponse
    {
        Gate::authorize('create', PaymentMethod::class);
        $paymentMethod = $this->service->create($request->validated());

        return redirect()
            ->route('payment-methods.show', $paymentMethod)
            ->with('status', 'Metode pembayaran berhasil ditambahkan.');
    }

    public function show(PaymentMethod $paymentMethod): View
    {
        Gate::authorize('view', $paymentMethod);
        $paymentMethod->loadCount('sales');

        return view('pages.payment-methods.show', compact('paymentMethod'));
    }

    public function edit(PaymentMethod $paymentMethod): View
    {
        Gate::authorize('update', $paymentMethod);

        return view('pages.payment-methods.edit', compact('paymentMethod'));
    }

    public function update(UpdatePaymentMethodRequest $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        Gate::authorize('update', $paymentMethod);
        $paymentMethod = $this->service->update($paymentMethod, $request->validated());

        return redirect()
            ->route('payment-methods.show', $paymentMethod)
            ->with('status', 'Metode pembayaran berhasil diperbarui.');
    }

    public function updateStatus(
        UpdatePaymentMethodStatusRequest $request,
        PaymentMethod $paymentMethod,
    ): RedirectResponse {
        Gate::authorize('updateStatus', $paymentMethod);
        $paymentMethod = $this->service->updateStatus($paymentMethod, $request->boolean('is_active'));

        return back()->with('status', $paymentMethod->is_active
            ? 'Metode pembayaran berhasil diaktifkan.'
            : 'Metode pembayaran berhasil dinonaktifkan dan tidak tersedia untuk transaksi baru.');
    }

    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        Gate::authorize('delete', $paymentMethod);
        $this->service->deleteIfUnused($paymentMethod);

        return redirect()
            ->route('payment-methods.index')
            ->with('status', 'Metode pembayaran berhasil dihapus.');
    }

    private function likeTerm(string $search): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
    }
}
