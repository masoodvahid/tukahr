<?php
namespace App\Http\Middleware;
use Closure;use Illuminate\Http\Request;
class EnsureHrStaff { public function handle(Request $request, Closure $next){ abort_unless($request->user() && ($request->user()->hasRole('hr_operator') || $request->user()->hasRole('hr_manager')),403); return $next($request); } }
