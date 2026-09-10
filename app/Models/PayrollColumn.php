<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PayrollColumn extends Model
{
    protected $fillable=['period_id','item_definition_id','column_key','title','value_type','unit','decimal_places','sort_order','is_required','validation_rules','copy_policy'];
    protected function casts(): array { return ['is_required'=>'boolean','validation_rules'=>'array']; }
    public function period(){return $this->belongsTo(PayrollPeriod::class,'period_id');}
}
