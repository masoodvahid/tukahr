<?php
namespace App\Http\Controllers;

use App\Enums\EditBarrier;
use App\Models\AuditEvent;
use App\Models\PayrollCell;
use App\Models\PayrollSheet;
use App\Services\Payroll\PayrollAccessService;
use App\Services\Payroll\PayrollEditor;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function index(Request $request, PayrollAccessService $access)
    {
        $sheets = PayrollSheet::with(['period', 'project', 'latestSnapshot', 'approvals'])->latest()->get()->filter(fn ($sheet) => $access->canView($request->user(), $sheet));
        return view('payroll.index', ['sheets' => $sheets]);
    }

    public function show(Request $request, PayrollSheet $sheet, PayrollAccessService $access)
    {
        abort_unless($access->canView($request->user(), $sheet), 403);
        $sheet->load(['period.columns', 'rows.cells', 'project', 'approvals', 'latestSnapshot']);
        $audit = $request->user()->hasRole('project_operator') ? collect() : AuditEvent::with('actor')->where('sheet_id', $sheet->id)->latest('id')->limit(100)->get();
        return view('payroll.show', ['sheet' => $sheet, 'canEdit' => $access->canEdit($request->user(), $sheet), 'audit' => $audit]);
    }

    public function updateCell(Request $request, PayrollSheet $sheet, PayrollEditor $editor)
    {
        $data = $request->validate(['row_id' => 'required|integer', 'column_id' => 'required|integer', 'value' => 'nullable', 'version' => 'required|integer|min:0']);
        $cell = $editor->updateCell($request->user(), $sheet, $data['row_id'], $data['column_id'], $data['value'], $data['version']);
        return response()->json(['ok' => true, 'version' => $cell->version, 'revision' => $sheet->fresh()->content_revision]);
    }

    public function changes(Request $request, PayrollSheet $sheet, PayrollAccessService $access)
    {
        abort_unless($access->canView($request->user(), $sheet), 403);
        $after = (int) $request->integer('after_revision', 0);
        $events = AuditEvent::where('sheet_id', $sheet->id)->where('action', 'cell.updated')->where('content_revision', '>', $after)->orderBy('id')->limit(500)->get();
        $cells = $events->map(function ($event) {
            $cell = PayrollCell::where('payroll_row_id', $event->payroll_row_id)->where('payroll_column_id', $event->payroll_column_id)->first();
            if (! $cell) return null;
            $value = $cell->{match ($cell->value_type) {'number' => 'number_value', 'date' => 'date_value', 'boolean' => 'boolean_value', default => 'text_value'}};
            return ['row_id' => $cell->payroll_row_id, 'column_id' => $cell->payroll_column_id, 'value' => $value, 'version' => $cell->version];
        })->filter()->values();
        return response()->json(['revision' => $sheet->fresh()->content_revision, 'cells' => $cells]);
    }

    public function submit(Request $request, PayrollSheet $sheet, PayrollAccessService $access)
    {
        abort_unless($request->user()->hasRole('project_operator') && $access->canEdit($request->user(), $sheet), 403);
        $sheet->update(['edit_barrier' => EditBarrier::ProjectOperator, 'operator_submitted_at' => now(), 'workflow_revision' => $sheet->workflow_revision + 1]);
        AuditEvent::create(['period_id' => $sheet->period_id, 'sheet_id' => $sheet->id, 'actor_user_id' => $request->user()->id, 'actor_assignment_id' => $request->user()->activeAssignment?->id, 'action' => 'operator.submitted', 'content_revision' => $sheet->content_revision, 'created_at' => now(), 'ip_address' => $request->ip()]);
        return back()->with('status', 'ثبت نهایی اپراتور انجام شد.');
    }
}
