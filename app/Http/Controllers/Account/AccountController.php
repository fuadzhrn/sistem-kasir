<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        return $request->user()
            ? redirect()->route('account.index')
            : redirect()->route('login');
    }

    public function index(Request $request): View
    {
        return view('pages.account.index', [
            'user' => $request->user()->loadMissing(['role', 'branch']),
        ]);
    }
}
