<?php

namespace App\Http\Middleware;

use App\Models\Branch;
use App\Services\Authorization\BranchAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBranchAccess
{
    public function __construct(
        private readonly BranchAccessService $branchAccess,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        $branch = $request->route('branch');

        abort_unless($branch instanceof Branch, 404);
        abort_unless($this->branchAccess->canAccessBranch($user, $branch), 404);

        return $next($request);
    }
}
