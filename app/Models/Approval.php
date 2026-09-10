<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Approval extends Model
{
    public $timestamps=false;
    protected $fillable=['sheet_id','snapshot_id','stage','approved_by','role_assignment_id','actor_name_snapshot','otp_challenge_id','comment_text','override_used','bypassed_stages','override_reason','approved_at','ip_address','user_agent'];
    protected function casts(): array{return ['override_used'=>'boolean','bypassed_stages'=>'array','approved_at'=>'datetime'];}
    public function snapshot(){ return $this->belongsTo(SheetSnapshot::class, 'snapshot_id'); }
}

