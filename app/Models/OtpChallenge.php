<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class OtpChallenge extends Model
{
    protected $fillable=['user_id','role_assignment_id','mobile_snapshot','purpose','sheet_id','snapshot_id','expected_workflow_revision','authorization_payload_hash','code_hash','expires_at','attempt_count','consumed_at','invalidated_at','provider_message_id','delivery_status'];
    protected function casts(): array{return ['expires_at'=>'datetime','consumed_at'=>'datetime','invalidated_at'=>'datetime'];}
    public function user(){ return $this->belongsTo(User::class); }
    public function snapshot(){ return $this->belongsTo(SheetSnapshot::class, 'snapshot_id'); }
}

