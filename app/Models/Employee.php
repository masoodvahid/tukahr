<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Employee extends Model
{
    protected $fillable=['personnel_number','national_id','first_name','last_name','default_project_id','is_active'];
    protected function casts(): array { return ['is_active'=>'boolean']; }
    public function defaultProject(){return $this->belongsTo(Project::class,'default_project_id');}
}
