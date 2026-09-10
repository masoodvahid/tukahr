<?php

namespace App\Http\Controllers;

use App\Models\PayrollCell;
use App\Models\PayrollColumn;
use App\Models\PayrollPeriod;
use App\Models\PayrollRow;
use App\Models\PayrollSheet;
use App\Models\Project;
use App\Support\JalaliDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PeriodController extends Controller
{
    public function index()
    {
        $currentYear = JalaliDate::currentYear();
        $currentMonth = JalaliDate::currentMonth();

        return view('periods.index', [
            'periods' => PayrollPeriod::withCount(['columns', 'sheets'])
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->get(),
            'years' => range($currentYear - 2, $currentYear + 2),
            'months' => [
                1 => 'فروردین',
                2 => 'اردیبهشت',
                3 => 'خرداد',
                4 => 'تیر',
                5 => 'مرداد',
                6 => 'شهریور',
                7 => 'مهر',
                8 => 'آبان',
                9 => 'آذر',
                10 => 'دی',
                11 => 'بهمن',
                12 => 'اسفند',
            ],
            'currentYear' => $currentYear,
            'currentMonth' => $currentMonth,
        ]);
    }

    public function store(Request $request)
    {
        $currentYear = JalaliDate::currentYear();

        $data = $request->validate([
            'year' => ['required', 'integer', Rule::in(range($currentYear - 2, $currentYear + 2))],
            'month' => ['required', 'integer', 'between:1,12'],
            'title' => ['required', 'string', 'max:255'],
            'copy_from' => ['nullable', 'exists:payroll_periods,id'],
            'copy_values' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($data, $request) {
            $period = PayrollPeriod::create([
                'calendar_type' => 'jalali',
                'year' => $data['year'],
                'month' => $data['month'],
                'title' => $data['title'],
                'business_timezone' => 'Asia/Tehran',
                'edit_deadline_at' => JalaliDate::editDeadline($data['year'], $data['month']),
                'source_period_id' => $data['copy_from'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            $columnMap = [];

            if (! empty($data['copy_from'])) {
                $source = PayrollPeriod::with(['columns', 'sheets.rows.cells'])->findOrFail($data['copy_from']);

                foreach ($source->columns as $column) {
                    $new = PayrollColumn::create([
                        'period_id' => $period->id,
                        'item_definition_id' => $column->item_definition_id,
                        'column_key' => $column->column_key,
                        'title' => $column->title,
                        'value_type' => $column->value_type,
                        'unit' => $column->unit,
                        'decimal_places' => $column->decimal_places,
                        'sort_order' => $column->sort_order,
                        'is_required' => $column->is_required,
                        'validation_rules' => $column->validation_rules,
                        'copy_policy' => $column->copy_policy,
                    ]);
                    $columnMap[$column->id] = $new->id;
                }

                foreach ($source->sheets as $sourceSheet) {
                    $newSheet = PayrollSheet::create([
                        'period_id' => $period->id,
                        'project_id' => $sourceSheet->project_id,
                        'project_name_snapshot' => $sourceSheet->project_name_snapshot,
                    ]);

                    foreach ($sourceSheet->rows as $sourceRow) {
                        $newRow = PayrollRow::create([
                            'period_id' => $period->id,
                            'sheet_id' => $newSheet->id,
                            'employee_id' => $sourceRow->employee_id,
                            'first_name_snapshot' => $sourceRow->first_name_snapshot,
                            'last_name_snapshot' => $sourceRow->last_name_snapshot,
                            'personnel_number_snapshot' => $sourceRow->personnel_number_snapshot,
                            'national_id_snapshot' => $sourceRow->national_id_snapshot,
                            'sort_order' => $sourceRow->sort_order,
                            'created_by' => $request->user()->id,
                        ]);

                        if (! empty($data['copy_values'])) {
                            foreach ($sourceRow->cells as $cell) {
                                if (! isset($columnMap[$cell->payroll_column_id])) {
                                    continue;
                                }

                                PayrollCell::create([
                                    'payroll_row_id' => $newRow->id,
                                    'payroll_column_id' => $columnMap[$cell->payroll_column_id],
                                    'period_id' => $period->id,
                                    'value_type' => $cell->value_type,
                                    'number_value' => $cell->number_value,
                                    'text_value' => $cell->text_value,
                                    'date_value' => $cell->date_value,
                                    'boolean_value' => $cell->boolean_value,
                                    'version' => 1,
                                    'updated_by' => $request->user()->id,
                                ]);
                            }
                        }
                    }
                }
            }

            foreach (Project::active()->get() as $project) {
                PayrollSheet::firstOrCreate(
                    ['period_id' => $period->id, 'project_id' => $project->id],
                    ['project_name_snapshot' => $project->name]
                );
            }
        });

        return back()->with('status', 'دوره جدید ایجاد شد.');
    }

    public function show(PayrollPeriod $period)
    {
        $period->load('columns');

        return view('periods.show', ['period' => $period]);
    }

    public function addColumn(Request $request, PayrollPeriod $period)
    {
        abort_if($period->structure_locked_at, 422, 'ساختار این دوره قفل شده است.');

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'column_key' => 'required|alpha_dash|max:80',
            'value_type' => 'required|in:number,text,date,boolean',
            'unit' => 'nullable|string|max:40',
            'decimal_places' => 'nullable|integer|min:0|max:4',
        ]);

        PayrollColumn::create($data + [
            'period_id' => $period->id,
            'sort_order' => ($period->columns()->max('sort_order') ?? 0) + 1,
        ]);

        return back()->with('status', 'ستون اضافه شد.');
    }

    public function deleteColumn(PayrollPeriod $period, PayrollColumn $column)
    {
        abort_if(
            $period->structure_locked_at || $column->period_id !== $period->id,
            422,
            'حذف این ستون مجاز نیست.'
        );

        $column->delete();

        return back()->with('status', 'ستون حذف شد.');
    }

    public function publish(PayrollPeriod $period)
    {
        abort_if($period->columns()->count() === 0, 422, 'حداقل یک ستون حقوق تعریف کنید.');

        $missingManager = $period->sheets()
            ->whereHas('rows')
            ->whereDoesntHave('project.assignments', fn ($q) => $q
                ->where('role_code', 'project_manager')
                ->whereNull('ended_at'))
            ->exists();

        abort_if(
            $missingManager,
            422,
            'برای همه پروژه‌های دارای پرسنل باید مدیر پروژه فعال تعیین شده باشد.'
        );

        $period->update([
            'published_at' => $period->published_at ?? now(),
            'structure_locked_at' => $period->structure_locked_at ?? now(),
        ]);

        return back()->with('status', 'دوره منتشر و ساختار ستون‌ها قفل شد.');
    }
}
