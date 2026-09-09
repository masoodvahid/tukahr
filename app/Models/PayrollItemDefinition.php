<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PayrollItemDefinition extends Model { protected $fillable=['code','default_title','default_value_type','default_unit','is_active']; protected function casts(): array{return ['is_active'=>'boolean'];} }
