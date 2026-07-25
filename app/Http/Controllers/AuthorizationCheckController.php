<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\Sale;
use App\Services\Authorization\BranchAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AuthorizationCheckController extends Controller
{
    public function __construct(
        private readonly BranchAccessService $branchAccess,
    ) {}

    public function index(Request $request): View
    {
        $this->ensureAvailableEnvironment();

        $user = $request->user()->loadMissing(['role', 'branch']);
        $branches = Branch::query()
            ->accessibleTo($user)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
        $sales = Sale::query()
            ->accessibleTo($user)
            ->latest('transaction_date')
            ->limit(10)
            ->get(['id', 'branch_id', 'cashier_id', 'invoice_number', 'transaction_date']);
        $expenses = Expense::query()
            ->accessibleTo($user)
            ->latest('expense_date')
            ->limit(10)
            ->get(['id', 'branch_id', 'expense_date', 'description']);
        $profitBranch = $branches->first();

        return view('pages.authorization-check.index', [
            'user' => $user,
            'branches' => $branches,
            'visibleSaleCount' => $sales->count(),
            'visibleExpenseCount' => $expenses->count(),
            'abilities' => [
                'Melihat laba cabang' => $profitBranch !== null
                    && Gate::forUser($user)->allows('view-profit', $profitBranch),
                'Melihat laporan global' => Gate::forUser($user)->allows('view-global-report'),
                'Mengelola cabang' => Gate::forUser($user)->allows('manage-branches'),
                'Mengelola pengguna' => Gate::forUser($user)->allows('manage-users'),
                'Mengelola pengaturan' => Gate::forUser($user)->allows('manage-settings'),
                'Melihat aktivitas' => Gate::forUser($user)->allows('view-activity-logs'),
            ],
        ]);
    }

    public function owner(): View
    {
        return $this->roleResult('Area Owner');
    }

    public function management(): View
    {
        return $this->roleResult('Area Manajemen');
    }

    public function cashier(): View
    {
        return $this->roleResult('Area Kasir');
    }

    public function branch(Branch $branch): View
    {
        $this->ensureAvailableEnvironment();

        return view('pages.authorization-check.result', [
            'title' => 'Akses Cabang Diizinkan',
            'message' => "Anda diizinkan mengakses informasi dasar {$branch->name}.",
        ]);
    }

    public function profit(Request $request, Branch $branch): View
    {
        $this->ensureAvailableEnvironment();

        Gate::authorize('view-profit', $branch);
        abort_unless($this->branchAccess->canAccessBranch($request->user(), $branch), 404);

        return view('pages.authorization-check.result', [
            'title' => 'Akses Laporan Diizinkan',
            'message' => "Akses laporan keuntungan {$branch->name} diizinkan. Tidak ada angka laporan yang ditampilkan pada tahap ini.",
        ]);
    }

    private function roleResult(string $title): View
    {
        $this->ensureAvailableEnvironment();

        return view('pages.authorization-check.result', [
            'title' => $title,
            'message' => 'Pemeriksaan middleware role berhasil.',
        ]);
    }

    private function ensureAvailableEnvironment(): void
    {
        abort_unless(app()->environment(['local', 'testing']), 404);
    }
}
