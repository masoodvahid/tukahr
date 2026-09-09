<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AuditEvent extends Model
{
    public $timestamps=false;
    protected $fillable=['period_id','sheet_id','actor_user_id','actor_assignment_id','action','entity_type','entity_id','payroll_row_id','payroll_column_id','old_value','new_value','content_revision','reason','batch_id','request_id','ip_address','created_at'];
    protected function casts(): array{return ['old_value'=>'array','new_value'=>'array','created_at'=>'datetime'];}
    public function actor(){ return $this->belongsTo(User::class, 'actor_user_id'); }
}

