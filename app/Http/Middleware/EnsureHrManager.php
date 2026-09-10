<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureHrManager
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user() && ($request->user()->isSystemAdmin() || $request->user()->hasRole('hr_manager')), 403);

        return $next($request);
    }
}
