<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PayrollCell extends Model
{
    public $incrementing=false;
    protected $primaryKey=null;
    protected $fillable=['payroll_row_id','payroll_column_id','period_id','value_type','number_value','text_value','date_value','boolean_value','version','updated_by'];
    protected function casts(): array{return ['date_value'=>'date','boolean_value'=>'boolean','number_value'=>'decimal:4'];}
}
