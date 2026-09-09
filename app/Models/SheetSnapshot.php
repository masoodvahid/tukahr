<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SheetSnapshot extends Model { public $timestamps=false; protected $fillable=['sheet_id','content_revision','payload','content_hash','created_by','created_at']; protected function casts(): array{return ['payload'=>'array','created_at'=>'datetime'];} }
