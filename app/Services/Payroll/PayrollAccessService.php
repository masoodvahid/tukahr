<?php
namespace App\Services\Payroll;

use App\Enums\EditBarrier;
use App\Enums\RoleCode;
use App\Models\PayrollSheet;
use App\Models\User;

class PayrollAccessService
{
    public function canView(User $user, PayrollSheet $sheet): bool
    {
        if ($user->isSystemAdmin()) {
            return true;
        }

        $assignment = $user->activeAssignment;
        if (! $assignment) {
            return false;
        }

        if (in_array($assignment->role_code, [RoleCode::ProjectOperator->value, RoleCode::ProjectManager->value], true)) {
            return (int) $assignment->project_id === (int) $sheet->project_id;
        }

        $snapshot = $sheet->latestSnapshot;
        $currentSnapshot = $snapshot && (int) $snapshot->content_revision === (int) $sheet->content_revision ? $snapshot : null;

        if ($assignment->role_code === RoleCode::Ceo->value) {
            return $currentSnapshot && $sheet->approvals()->where('stage', 'hr_manager')->where('snapshot_id', $currentSnapshot->id)->exists();
        }

        if ($assignment->role_code === RoleCode::FinanceManager->value) {
            return $currentSnapshot && $sheet->approvals()->where('stage', 'ceo')->where('snapshot_id', $currentSnapshot->id)->exists();
        }

        return true;
    }

    public function canEdit(User $user, PayrollSheet $sheet): bool
    {
        if (! $this->canView($user, $sheet) || $sheet->finalized_at || $sheet->period->isEditingExpired()) {
            return false;
        }

        if ($user->isSystemAdmin()) {
            return true;
        }

        $role = $user->activeAssignment?->role_code;
        $barrier = $sheet->edit_barrier;

        return match ($role) {
            RoleCode::ProjectOperator->value => $barrier === EditBarrier::None,
            RoleCode::ProjectManager->value => in_array($barrier, [EditBarrier::None, EditBarrier::ProjectOperator], true),
            RoleCode::HrOperator->value => in_array($barrier, [EditBarrier::None, EditBarrier::ProjectOperator, EditBarrier::ProjectTeam], true),
            RoleCode::HrManager->value => $barrier !== EditBarrier::All,
            default => false,
        };
    }
}
