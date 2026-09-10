<?php
namespace App\Http\Controllers;

use App\Models\PayrollPeriod;
use App\Models\PayrollSheet;
use App\Models\Project;
use App\Models\User;
use App\Services\Otp\KavenegarStatusService;

class DashboardController extends Controller
{
    public function __invoke(KavenegarStatusService $kavenegar)
    {
        return view('dashboard', [
            'projects' => Project::active()->count(),
            'users' => User::where('is_active', true)->count(),
            'periods' => PayrollPeriod::count(),
            'pending' => PayrollSheet::whereNull('finalized_at')->count(),
            'smsStatus' => $kavenegar->status(),
        ]);
    }
}
