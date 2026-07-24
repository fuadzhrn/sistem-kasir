<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class DesignSystemController extends Controller
{
    public function __invoke(): View
    {
        abort_unless(app()->environment('local'), 404);

        return view('pages.design-system.index');
    }
}
