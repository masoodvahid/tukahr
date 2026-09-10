<?php
namespace App\Models;

use App\Enums\RoleCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'mobile',
        'password',
        'mobile_verified_at',
        'is_active',
        'is_system_admin',
        'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'mobile_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'is_system_admin' => 'boolean',
        ];
    }

    public function assignments() { return $this->hasMany(RoleAssignment::class); }
    public function activeAssignment() { return $this->hasOne(RoleAssignment::class)->whereNull('ended_at')->latestOfMany(); }
    public function hasRole(RoleCode|string $role): bool { $value=$role instanceof RoleCode?$role->value:$role; return $this->activeAssignment?->role_code === $value; }
    public function isSystemAdmin(): bool { return (bool) $this->is_system_admin; }
}
