<?php
namespace Database\Seeders;

use App\Models\RoleAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedTestSystemAdmin();
        $this->seedConfiguredHrManager();
    }

    private function seedTestSystemAdmin(): void
    {
        $enabled = filter_var(env('TUKA_TEST_ADMIN_ENABLED', app()->environment() !== 'production'), FILTER_VALIDATE_BOOL);
        if (! $enabled) {
            return;
        }

        $user = User::updateOrCreate(
            ['mobile' => '09138752587'],
            [
                'name' => 'مدیر سیستم تست',
                'password' => '$2y$12$f1uJLMEsEAfpvWmiKqFM5erljXCCBEyV13j/No.Lb4vM56Q47DNTC',
                'is_active' => true,
                'is_system_admin' => true,
                'mobile_verified_at' => now(),
            ]
        );

        $hasAssignment = RoleAssignment::where('user_id', $user->id)->whereNull('ended_at')->exists();
        $hasHrManager = RoleAssignment::where('role_code', 'hr_manager')->whereNull('ended_at')->exists();

        if (! $hasAssignment && ! $hasHrManager) {
            RoleAssignment::create([
                'user_id' => $user->id,
                'role_code' => 'hr_manager',
                'started_at' => now(),
            ]);
        }
    }

    private function seedConfiguredHrManager(): void
    {
        $mobile = env('TUKA_ADMIN_MOBILE');
        if (! $mobile) {
            return;
        }

        $attributes = [
            'name' => env('TUKA_ADMIN_NAME', 'مدیر منابع انسانی'),
            'is_active' => true,
            'mobile_verified_at' => now(),
        ];

        if ($password = env('TUKA_ADMIN_PASSWORD')) {
            $attributes['password'] = Hash::make($password);
        }

        $user = User::updateOrCreate(['mobile' => $mobile], $attributes);

        $hasAssignment = RoleAssignment::where('user_id', $user->id)->whereNull('ended_at')->exists();
        $hasHrManager = RoleAssignment::where('role_code', 'hr_manager')->whereNull('ended_at')->exists();

        if (! $hasAssignment && ! $hasHrManager) {
            RoleAssignment::create([
                'user_id' => $user->id,
                'role_code' => 'hr_manager',
                'started_at' => now(),
            ]);
        }
    }
}
