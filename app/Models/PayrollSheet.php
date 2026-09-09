<?php
namespace App\Models;
use App\Enums\EditBarrier;
use Illuminate\Database\Eloquent\Model;
class PayrollSheet extends Model
{
    protected $fillable=['period_id','project_id','project_name_snapshot','content_revision','workflow_revision','edit_barrier','operator_submitted_at','finalized_at','final_snapshot_id'];
    protected function casts(): array{return ['operator_submitted_at'=>'datetime','finalized_at'=>'datetime','edit_barrier'=>EditBarrier::class];}
    public function period(){return $this->belongsTo(PayrollPeriod::class,'period_id');}
    public function project(){return $this->belongsTo(Project::class);}
    public function rows(){return $this->hasMany(PayrollRow::class,'sheet_id')->orderBy('sort_order');}
    public function approvals(){return $this->hasMany(Approval::class,'sheet_id');} public function latestSnapshot(){return $this->hasOne(SheetSnapshot::class,'sheet_id')->latestOfMany('content_revision');}
}
