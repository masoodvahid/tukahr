<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('role_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('role_code', 40);
            $table->unsignedBigInteger('project_id')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedBigInteger('assigned_by')->nullable();

            // MySQL does not support partial unique indexes. Generated columns
            // expose only the active assignment values; UNIQUE indexes can then
            // enforce the business rules while allowing multiple NULL values.
            $table->unsignedBigInteger('active_assignment_user_id')
                ->storedAs('CASE WHEN ended_at IS NULL THEN user_id ELSE NULL END');
            $table->unsignedBigInteger('active_manager_project_id')
                ->storedAs("CASE WHEN role_code = 'project_manager' AND ended_at IS NULL THEN project_id ELSE NULL END");
            $table->string('active_singleton_role', 40)
                ->storedAs("CASE WHEN role_code IN ('hr_manager','ceo','finance_manager') AND ended_at IS NULL THEN role_code ELSE NULL END");

            $table->timestamps();

            $table->unique('active_assignment_user_id', 'uq_active_assignment_user');
            $table->unique('active_manager_project_id', 'uq_active_manager_project');
            $table->unique('active_singleton_role', 'uq_active_singleton_role');
            $table->index(['role_code', 'project_id', 'ended_at']);

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('project_id')->references('id')->on('projects')->restrictOnDelete();
            $table->foreign('assigned_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_assignments');
        Schema::dropIfExists('projects');
    }
};
