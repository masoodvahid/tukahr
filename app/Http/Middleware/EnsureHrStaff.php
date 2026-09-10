<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureHrStaff
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        abort_unless($user && ($user->isSystemAdmin() || $user->hasRole('hr_operator') || $user->hasRole('hr_manager')), 403);

        return $next($request);
    }
}
