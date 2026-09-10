<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_can_login_with_mobile_and_password(): void
    {
        $user = User::create([
            'name' => 'کاربر تست',
            'mobile' => '09120000111',
            'password' => Hash::make('12345678'),
            'is_active' => true,
        ]);

        $response = $this->post(route('login.password'), [
            'mobile' => '09120000111',
            'password' => '12345678',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_is_rejected(): void
    {
        User::create([
            'name' => 'کاربر تست',
            'mobile' => '09120000112',
            'password' => Hash::make('12345678'),
            'is_active' => true,
        ]);

        $response = $this->from(route('login'))->post(route('login.password'), [
            'mobile' => '09120000112',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('mobile');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login_with_password(): void
    {
        User::create([
            'name' => 'کاربر غیرفعال',
            'mobile' => '09120000113',
            'password' => Hash::make('12345678'),
            'is_active' => false,
        ]);

        $this->post(route('login.password'), [
            'mobile' => '09120000113',
            'password' => '12345678',
        ])->assertSessionHasErrors('mobile');

        $this->assertGuest();
    }
}
