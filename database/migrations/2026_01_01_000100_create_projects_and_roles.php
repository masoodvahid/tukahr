<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('projects',function(Blueprint $t){$t->id();$t->string('code',50)->unique();$t->string('name');$t->timestamp('archived_at')->nullable();$t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();$t->timestamps();});
  Schema::create('role_assignments',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->string('role_code',40);$t->foreignId('project_id')->nullable()->constrained()->restrictOnDelete();$t->timestamp('started_at');$t->timestamp('ended_at')->nullable();$t->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();$t->timestamps();$t->index(['role_code','project_id','ended_at']);});
  DB::statement("ALTER TABLE role_assignments ADD COLUMN active_assignment_user_id BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN ended_at IS NULL THEN user_id ELSE NULL END) STORED, ADD UNIQUE KEY uq_active_assignment_user (active_assignment_user_id)");
  DB::statement("ALTER TABLE role_assignments ADD COLUMN active_manager_project_id BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN role_code = 'project_manager' AND ended_at IS NULL THEN project_id ELSE NULL END) STORED, ADD UNIQUE KEY uq_active_manager_project (active_manager_project_id)");
  DB::statement("ALTER TABLE role_assignments ADD COLUMN active_singleton_role VARCHAR(40) GENERATED ALWAYS AS (CASE WHEN role_code IN ('hr_manager','ceo','finance_manager') AND ended_at IS NULL THEN role_code ELSE NULL END) STORED, ADD UNIQUE KEY uq_active_singleton_role (active_singleton_role)");
 }
 public function down(): void {Schema::dropIfExists('role_assignments');Schema::dropIfExists('projects');}
};
