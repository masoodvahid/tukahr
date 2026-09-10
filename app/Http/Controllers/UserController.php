<?php
namespace App\Http\Controllers;

use App\Enums\RoleCode;
use App\Models\Project;
use App\Models\RoleAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return view('users.index', [
            'users' => User::with('activeAssignment.project')->latest()->get(),
            'projects' => Project::active()->orderBy('name')->get(),
            'roles' => RoleCode::cases(),
        ]);
    }

    public function store(Request $request)
    {
        $roles = array_map(fn (RoleCode $role) => $role->value, RoleCode::cases());
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'mobile' => ['required', 'regex:/^09\d{9}$/', 'unique:users,mobile'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'role_code' => ['required', Rule::in($roles)],
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $projectRole = in_array($data['role_code'], ['project_operator', 'project_manager'], true);
        if ($projectRole && ! $data['project_id']) {
            return back()->withErrors(['project_id' => 'انتخاب پروژه برای این نقش الزامی است.'])->withInput();
        }

        if (! $projectRole) {
            $data['project_id'] = null;
        }

        if ($data['role_code'] === 'project_manager' && RoleAssignment::where('role_code', 'project_manager')->where('project_id', $data['project_id'])->whereNull('ended_at')->exists()) {
            return back()->withErrors(['role_code' => 'این پروژه در حال حاضر مدیر فعال دارد.'])->withInput();
        }

        if (in_array($data['role_code'], ['hr_manager', 'ceo', 'finance_manager'], true) && RoleAssignment::where('role_code', $data['role_code'])->whereNull('ended_at')->exists()) {
            return back()->withErrors(['role_code' => 'برای این سطح مدیریتی قبلاً کاربر فعال تعیین شده است.'])->withInput();
        }

        DB::transaction(function () use ($data, $request) {
            $user = User::create([
                'name' => $data['name'],
                'mobile' => $data['mobile'],
                'password' => Hash::make($data['password']),
                'is_active' => true,
            ]);

            RoleAssignment::create([
                'user_id' => $user->id,
                'role_code' => $data['role_code'],
                'project_id' => $data['project_id'],
                'started_at' => now(),
                'assigned_by' => $request->user()->id,
            ]);
        });

        return back()->with('status', 'کاربر ایجاد شد.');
    }
}
