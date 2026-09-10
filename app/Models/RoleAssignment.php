<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RoleAssignment extends Model
{
    protected $fillable=['user_id','role_code','project_id','started_at','ended_at','assigned_by'];
    protected function casts(): array { return ['started_at'=>'datetime','ended_at'=>'datetime']; }
    public function user(){return $this->belongsTo(User::class);} public function project(){return $this->belongsTo(Project::class);}
}
