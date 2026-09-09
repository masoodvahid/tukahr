<?php
namespace App\Services\Payroll;

use App\Models\AuditEvent;
use App\Models\PayrollCell;
use App\Models\PayrollColumn;
use App\Models\PayrollRow;
use App\Models\PayrollSheet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollEditor
{
    public function __construct(private PayrollAccessService $access) {}

    public function updateCell(User $user, PayrollSheet $sheet, int $rowId, int $columnId, mixed $value, int $expectedVersion): PayrollCell
    {
        if (! $this->access->canEdit($user, $sheet)) abort(403);

        return DB::transaction(function () use ($user, $sheet, $rowId, $columnId, $value, $expectedVersion) {
            $locked = PayrollSheet::with('period')->lockForUpdate()->findOrFail($sheet->id);
            if (! $this->access->canEdit($user, $locked)) abort(403);

            $row = PayrollRow::where('sheet_id', $locked->id)->whereKey($rowId)->firstOrFail();
            $column = PayrollColumn::where('period_id', $locked->period_id)->whereKey($columnId)->firstOrFail();
            $query = DB::table('payroll_cells')->where('payroll_row_id', $row->id)->where('payroll_column_id', $column->id);
            $cell = $query->lockForUpdate()->first();
            $currentVersion = (int) ($cell->version ?? 0);

            if ($currentVersion !== $expectedVersion) {
                throw ValidationException::withMessages(['version' => 'این سلول توسط کاربر دیگری تغییر کرده است. صفحه را تازه‌سازی کنید.']);
            }

            $old = $cell ? [
                'number_value' => $cell->number_value, 'text_value' => $cell->text_value,
                'date_value' => $cell->date_value, 'boolean_value' => $cell->boolean_value,
            ] : null;

            $field = match ($column->value_type) {
                'number' => 'number_value', 'date' => 'date_value', 'boolean' => 'boolean_value', default => 'text_value',
            };
            $data = [
                'period_id' => $locked->period_id, 'value_type' => $column->value_type,
                'number_value' => null, 'text_value' => null, 'date_value' => null, 'boolean_value' => null,
                $field => $this->normalizeValue($column->value_type, $value),
                'version' => $currentVersion + 1, 'updated_by' => $user->id, 'updated_at' => now(),
            ];

            if ($cell) {
                $query->update($data);
            } else {
                DB::table('payroll_cells')->insert($data + [
                    'payroll_row_id' => $row->id, 'payroll_column_id' => $column->id, 'created_at' => now(),
                ]);
            }

            $locked->increment('content_revision');
            $revision = (int) $locked->fresh()->content_revision;
            AuditEvent::create([
                'period_id' => $locked->period_id, 'sheet_id' => $locked->id, 'actor_user_id' => $user->id,
                'actor_assignment_id' => $user->activeAssignment?->id, 'action' => 'cell.updated',
                'entity_type' => PayrollCell::class, 'payroll_row_id' => $row->id, 'payroll_column_id' => $column->id,
                'old_value' => $old, 'new_value' => [$field => $data[$field]], 'content_revision' => $revision,
                'ip_address' => request()->ip(), 'created_at' => now(),
            ]);

            return PayrollCell::where('payroll_row_id', $row->id)->where('payroll_column_id', $column->id)->firstOrFail();
        }, 3);
    }

    private function normalizeValue(string $type, mixed $value): mixed
    {
        if ($value === '' || $value === null) return null;
        return match ($type) {
            'number' => is_numeric($value) ? $value : throw ValidationException::withMessages(['value' => 'مقدار باید عددی باشد.']),
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? throw ValidationException::withMessages(['value' => 'مقدار بله/خیر معتبر نیست.']),
            'date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value) ? $value : throw ValidationException::withMessages(['value' => 'فرمت تاریخ باید YYYY-MM-DD باشد.']),
            default => mb_substr((string) $value, 0, 5000),
        };
    }
}
