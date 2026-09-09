<?php
namespace Database\Seeders;
use App\Models\RoleAssignment;use App\Models\User;use Illuminate\Database\Seeder;
class DatabaseSeeder extends Seeder {
 public function run(): void {
  $mobile=env('TUKA_ADMIN_MOBILE'); if(!$mobile)return;
  $u=User::firstOrCreate(['mobile'=>$mobile],['name'=>env('TUKA_ADMIN_NAME','مدیر منابع انسانی'),'is_active'=>true,'mobile_verified_at'=>now()]);
  RoleAssignment::firstOrCreate(['user_id'=>$u->id,'ended_at'=>null],['role_code'=>'hr_manager','started_at'=>now()]);
 }
}
