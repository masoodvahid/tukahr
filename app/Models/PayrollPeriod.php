<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PayrollPeriod extends Model
{
    protected $fillable=['calendar_type','year','month','title','business_timezone','edit_deadline_at','published_at','structure_locked_at','source_period_id','created_by'];
    protected function casts(): array { return ['edit_deadline_at'=>'datetime','published_at'=>'datetime','structure_locked_at'=>'datetime']; }
    public function columns(){return $this->hasMany(PayrollColumn::class,'period_id')->orderBy('sort_order');}
    public function sheets(){return $this->hasMany(PayrollSheet::class,'period_id');}
    public function isEditingExpired(): bool { return now()->greaterThan($this->edit_deadline_at); }
}
