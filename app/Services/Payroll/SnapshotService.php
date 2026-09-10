<?php

namespace App\Services\Payroll;

use App\Models\PayrollSheet;
use App\Models\SheetSnapshot;

class SnapshotService
{
    public function create(PayrollSheet $sheet, ?int $userId = null): SheetSnapshot
    {
        // Database defaults are not always hydrated back into an Eloquent model
        // immediately after create(). A snapshot must always bind to the persisted
        // revision, never to an in-memory null/default value.
        if ($sheet->content_revision === null) {
            $sheet->refresh();
        }

        $sheet->load(['period.columns', 'rows.cells']);

        $payload = [
            'period' => [
                'id' => $sheet->period->id,
                'title' => $sheet->period->title,
            ],
            'project' => [
                'id' => $sheet->project_id,
                'name' => $sheet->project_name_snapshot,
            ],
            'columns' => $sheet->period->columns
                ->map->only(['id', 'column_key', 'title', 'value_type', 'unit', 'sort_order'])
                ->values()
                ->all(),
            'rows' => $sheet->rows->map(fn ($row) => [
                'employee_id' => $row->employee_id,
                'first_name' => $row->first_name_snapshot,
                'last_name' => $row->last_name_snapshot,
                'personnel_number' => $row->personnel_number_snapshot,
                'national_id' => $row->national_id_snapshot,
                'cells' => $row->cells
                    ->map->only(['payroll_column_id', 'number_value', 'text_value', 'date_value', 'boolean_value'])
                    ->values()
                    ->all(),
            ])->values()->all(),
        ];

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return SheetSnapshot::firstOrCreate(
            [
                'sheet_id' => $sheet->id,
                'content_revision' => $sheet->content_revision,
            ],
            [
                'payload' => $payload,
                'content_hash' => hash('sha256', $json),
                'created_by' => $userId,
                'created_at' => now(),
            ],
        );
    }
}
