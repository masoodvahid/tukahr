<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PayrollRow extends Model
{
    protected $fillable=['period_id','sheet_id','employee_id','first_name_snapshot','last_name_snapshot','personnel_number_snapshot','national_id_snapshot','sort_order','created_by'];
    public function sheet(){return $this->belongsTo(PayrollSheet::class,'sheet_id');}
    public function cells(){return $this->hasMany(PayrollCell::class);}
}
