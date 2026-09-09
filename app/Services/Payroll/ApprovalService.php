<?php
namespace App\Services\Payroll;

use App\Enums\ApprovalStage;
use App\Enums\EditBarrier;
use App\Models\Approval;
use App\Models\AuditEvent;
use App\Models\OtpChallenge;
use App\Models\PayrollSheet;
use App\Models\User;
use App\Services\Otp\OtpService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovalService
{
    public function __construct(
        private SnapshotService $snapshots,
        private OtpService $otp,
        private PayrollAccessService $access,
    ) {}

    private function prerequisites(ApprovalStage $stage): array
    {
        return match ($stage) {
            ApprovalStage::ProjectManager => [],
            ApprovalStage::HrOperator => [ApprovalStage::ProjectManager],
            ApprovalStage::HrManager => [ApprovalStage::ProjectManager, ApprovalStage::HrOperator],
            ApprovalStage::Ceo => [ApprovalStage::HrManager],
            ApprovalStage::FinanceManager => [ApprovalStage::Ceo],
        };
    }

    private function mayOverride(ApprovalStage $stage): bool
    {
        return in_array($stage, [ApprovalStage::HrOperator, ApprovalStage::HrManager], true);
    }

    public function request(User $user, PayrollSheet $sheet, ApprovalStage $stage, ?string $comment = null, bool $override = false): OtpChallenge
    {
        abort_unless($user->hasRole($stage->value) && $this->access->canView($user, $sheet), 403);
        if ($sheet->finalized_at) {
            throw ValidationException::withMessages(['sheet' => 'این لیست نهایی شده است.']);
        }

        $snapshot = $this->snapshots->create($sheet, $user->id);
        $approvedStages = $sheet->approvals()->where('snapshot_id', $snapshot->id)->pluck('stage')->all();
        $missing = array_values(array_filter(
            $this->prerequisites($stage),
            fn (ApprovalStage $required) => ! in_array($required->value, $approvedStages, true)
        ));

        if ($missing && (! $override || ! $this->mayOverride($stage))) {
            throw ValidationException::withMessages([
                'approval' => $this->mayOverride($stage)
                    ? 'تأییدهای مرحله قبل کامل نیست. برای ادامه باید گزینه عبور آگاهانه از مراحل قبلی را تأیید کنید.'
                    : 'تأیید مرحله قبل برای نسخه جاری الزامی است.',
            ]);
        }

        $bypassed = array_map(fn (ApprovalStage $s) => $s->value, $missing);
        $payload = $this->payloadHash($sheet->id, $snapshot->id, $stage, $comment, $override, $bypassed);

        return $this->otp->issue($user, 'approval_'.$stage->value, [
            'role_assignment_id' => $user->activeAssignment?->id,
            'sheet_id' => $sheet->id,
            'snapshot_id' => $snapshot->id,
            'expected_workflow_revision' => $sheet->workflow_revision,
            'authorization_payload_hash' => $payload,
        ]);
    }

    public function confirm(User $user, OtpChallenge $challenge, string $code, ApprovalStage $stage, ?string $comment = null, bool $override = false): Approval
    {
        return DB::transaction(function () use ($user, $challenge, $code, $stage, $comment, $override) {
            $challenge = OtpChallenge::with('snapshot')->lockForUpdate()->findOrFail($challenge->id);
            $sheet = PayrollSheet::with(['period', 'latestSnapshot'])->lockForUpdate()->findOrFail($challenge->sheet_id);
            abort_unless($challenge->user_id === $user->id && $user->hasRole($stage->value) && $this->access->canView($user, $sheet), 403);

            if ($sheet->workflow_revision !== $challenge->expected_workflow_revision || $sheet->content_revision !== $challenge->snapshot?->content_revision) {
                throw ValidationException::withMessages(['code' => 'نسخه یا وضعیت لیست تغییر کرده است؛ کد جدید دریافت کنید.']);
            }

            $approvedStages = $sheet->approvals()->where('snapshot_id', $challenge->snapshot_id)->pluck('stage')->all();
            $missing = array_values(array_filter(
                $this->prerequisites($stage),
                fn (ApprovalStage $required) => ! in_array($required->value, $approvedStages, true)
            ));
            $bypassed = array_map(fn (ApprovalStage $s) => $s->value, $missing);
            $payload = $this->payloadHash($sheet->id, $challenge->snapshot_id, $stage, $comment, $override, $bypassed);
            if (! hash_equals((string) $challenge->authorization_payload_hash, $payload)) {
                throw ValidationException::withMessages(['code' => 'اطلاعات تأیید تغییر کرده است.']);
            }

            $this->otp->verify($challenge, $code);
            $approval = Approval::create([
                'sheet_id' => $sheet->id,
                'snapshot_id' => $challenge->snapshot_id,
                'stage' => $stage->value,
                'approved_by' => $user->id,
                'role_assignment_id' => $user->activeAssignment?->id,
                'actor_name_snapshot' => $user->name,
                'otp_challenge_id' => $challenge->id,
                'comment_text' => $comment,
                'override_used' => (bool) $missing,
                'bypassed_stages' => $bypassed ?: null,
                'approved_at' => now(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            $sheet->edit_barrier = match ($stage) {
                ApprovalStage::ProjectManager, ApprovalStage::HrOperator => EditBarrier::ProjectTeam,
                ApprovalStage::HrManager, ApprovalStage::Ceo => EditBarrier::ProjectAndHrOperator,
                ApprovalStage::FinanceManager => EditBarrier::All,
            };
            $sheet->workflow_revision++;
            if ($stage === ApprovalStage::FinanceManager) {
                $sheet->finalized_at = now();
                $sheet->final_snapshot_id = $challenge->snapshot_id;
            }
            $sheet->save();

            AuditEvent::create([
                'period_id' => $sheet->period_id,
                'sheet_id' => $sheet->id,
                'actor_user_id' => $user->id,
                'actor_assignment_id' => $user->activeAssignment?->id,
                'action' => 'approval.'.$stage->value,
                'entity_type' => Approval::class,
                'entity_id' => $approval->id,
                'content_revision' => $sheet->content_revision,
                'created_at' => now(),
                'ip_address' => request()->ip(),
            ]);

            return $approval;
        }, 3);
    }

    private function payloadHash(int $sheetId, int $snapshotId, ApprovalStage $stage, ?string $comment, bool $override, array $bypassed): string
    {
        return hash('sha256', json_encode([
            'sheet' => $sheetId,
            'snapshot' => $snapshotId,
            'stage' => $stage->value,
            'comment' => $comment,
            'override' => $override,
            'bypassed' => $bypassed,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
