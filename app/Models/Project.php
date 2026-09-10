<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Project extends Model
{
    protected $fillable=['code','name','archived_at','created_by'];
    protected function casts(): array { return ['archived_at'=>'datetime']; }
    public function scopeActive($q){ return $q->whereNull('archived_at'); }
    public function assignments(){ return $this->hasMany(RoleAssignment::class); }
}
