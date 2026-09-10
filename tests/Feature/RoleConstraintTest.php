<?php
namespace Tests\Feature;
use App\Models\Project;use App\Models\RoleAssignment;use App\Models\User;use Illuminate\Database\QueryException;use Illuminate\Foundation\Testing\RefreshDatabase;use Tests\TestCase;
class RoleConstraintTest extends TestCase { use RefreshDatabase;
 public function test_project_can_have_only_one_active_manager(): void {$p=Project::create(['code'=>'P1','name'=>'پروژه یک']);$u1=User::create(['name'=>'A','mobile'=>'09120000001']);$u2=User::create(['name'=>'B','mobile'=>'09120000002']);RoleAssignment::create(['user_id'=>$u1->id,'role_code'=>'project_manager','project_id'=>$p->id,'started_at'=>now()]);$this->expectException(QueryException::class);RoleAssignment::create(['user_id'=>$u2->id,'role_code'=>'project_manager','project_id'=>$p->id,'started_at'=>now()]);}
 public function test_project_allows_multiple_operators(): void {$p=Project::create(['code'=>'P2','name'=>'پروژه دو']);foreach([1,2] as $i){$u=User::create(['name'=>'Op'.$i,'mobile'=>'0912000001'.$i]);RoleAssignment::create(['user_id'=>$u->id,'role_code'=>'project_operator','project_id'=>$p->id,'started_at'=>now()]);}$this->assertSame(2,RoleAssignment::where('role_code','project_operator')->count());}
}
