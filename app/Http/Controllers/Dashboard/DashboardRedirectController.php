<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        return match (true) {
            $user->isOwner() => redirect()->route('dashboard.owner'),
            $user->isAdmin() => redirect()->route('dashboard.admin'),
            $user->isCashier() => redirect()->route('dashboard.cashier'),
            default => abort(403, 'Role akun tidak memiliki dashboard.'),
        };
    }
}
