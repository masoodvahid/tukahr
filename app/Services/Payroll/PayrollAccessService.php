<?php
namespace App\Services\Payroll;
use App\Enums\EditBarrier;
use App\Enums\RoleCode;
use App\Models\PayrollSheet;
use App\Models\User;
class PayrollAccessService {
 public function canView(User $u, PayrollSheet $s): bool {
  $a=$u->activeAssignment; if(!$a)return false;
  if(in_array($a->role_code,[RoleCode::ProjectOperator->value,RoleCode::ProjectManager->value],true)) return (int)$a->project_id===(int)$s->project_id;
  $snapshot=$s->latestSnapshot;
  $currentSnapshot=$snapshot && (int)$snapshot->content_revision===(int)$s->content_revision ? $snapshot : null;
  if($a->role_code===RoleCode::Ceo->value) return $currentSnapshot && $s->approvals()->where('stage','hr_manager')->where('snapshot_id',$currentSnapshot->id)->exists();
  if($a->role_code===RoleCode::FinanceManager->value) return $currentSnapshot && $s->approvals()->where('stage','ceo')->where('snapshot_id',$currentSnapshot->id)->exists();
  return true;
 }
 public function canEdit(User $u, PayrollSheet $s): bool {
  if(!$this->canView($u,$s)||$s->finalized_at||$s->period->isEditingExpired()) return false;
  $r=$u->activeAssignment?->role_code; $b=$s->edit_barrier;
  return match($r){
   RoleCode::ProjectOperator->value => $b===EditBarrier::None,
   RoleCode::ProjectManager->value => in_array($b,[EditBarrier::None,EditBarrier::ProjectOperator],true),
   RoleCode::HrOperator->value => in_array($b,[EditBarrier::None,EditBarrier::ProjectOperator,EditBarrier::ProjectTeam],true),
   RoleCode::HrManager->value => $b!==EditBarrier::All,
   default => false,
  };
 }
}
