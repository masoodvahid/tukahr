@extends('layouts.app', ['heading' => $sheet->project_name_snapshot, 'subheading' => $sheet->period->title.' — نسخه '.$sheet->content_revision])
@section('content')
@php
    $role = auth()->user()->activeAssignment?->role_code;
    $stage = in_array($role, ['project_manager','hr_operator','hr_manager','ceo','finance_manager'], true) ? $role : null;
    $currentSnapshotId = ($sheet->latestSnapshot && (int)$sheet->latestSnapshot->content_revision === (int)$sheet->content_revision) ? $sheet->latestSnapshot->id : null;
    $stageLabel = [
        'project_manager' => 'تأیید نهایی مدیریت پروژه',
        'hr_operator' => 'تأیید منابع انسانی',
        'hr_manager' => 'تأیید مدیر منابع انسانی',
        'ceo' => 'تأیید مدیرعامل',
        'finance_manager' => 'تأیید مدیر مالی',
    ][$stage] ?? null;
@endphp

<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div class="flex flex-wrap gap-2">
        <span class="badge">{{ $canEdit ? 'قابل ویرایش' : 'فقط مشاهده' }}</span>
        <span class="badge">نسخه {{ $sheet->content_revision }}</span>
        @if($sheet->period->isEditingExpired())<span class="badge">مهلت ویرایش پایان یافته</span>@endif
        @if($sheet->finalized_at)<span class="badge">نهایی مالی</span>@endif
    </div>
    <div class="flex gap-2">
        @if($sheet->finalized_at)
            <a class="btn-secondary" href="{{ route('payroll.export.xlsx', $sheet) }}">دانلود Excel</a>
            <a class="btn-secondary" href="{{ route('payroll.export.pdf', $sheet) }}">دانلود PDF</a>
        @endif
    </div>
</div>

<div class="card mb-5 p-5">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="font-bold">گردش تأیید</h2>
            <p class="mt-1 text-sm text-neutral-500">هر تأیید به نسخه دقیق اطلاعات متصل است.</p>
        </div>
        <div class="flex flex-wrap gap-2 text-xs">
            @foreach(['project_manager'=>'مدیر پروژه','hr_operator'=>'منابع انسانی','hr_manager'=>'مدیر منابع انسانی','ceo'=>'مدیرعامل','finance_manager'=>'مدیر مالی'] as $key=>$label)
                @php($approved = $sheet->approvals->where('stage',$key)->where('snapshot_id',$currentSnapshotId)->sortByDesc('id')->first())
                <span class="badge">{{ $label }}: {{ $approved ? 'تأیید شده' : 'در انتظار' }}</span>
            @endforeach
        </div>
    </div>

    @if($stage && !$sheet->finalized_at)
        <form class="mt-5 grid gap-3 lg:grid-cols-[1fr_auto]" method="post" action="{{ route('payroll.approval.request', $sheet) }}">
            @csrf
            <input type="hidden" name="stage" value="{{ $stage }}">
            <div class="space-y-3">
                @if(in_array($stage,['ceo','finance_manager'],true))
                    <textarea class="field" name="comment" rows="3" placeholder="{{ $stage==='ceo' ? 'نظریه مدیرعامل (اختیاری)' : 'نظریه واحد مالی (اختیاری)' }}"></textarea>
                @endif
                @if(in_array($stage,['hr_operator','hr_manager'],true))
                    <label class="flex items-start gap-2 rounded-xl bg-neutral-50 p-3 text-sm text-neutral-600">
                        <input class="mt-1" type="checkbox" name="override" value="1">
                        <span>در صورت ناقص بودن تأیید مراحل قبلی، با آگاهی از قفل شدن دسترسی سطوح پایین‌تر ادامه می‌دهم.</span>
                    </label>
                @endif
            </div>
            <button class="btn-primary self-end" data-confirm="برای ثبت این تأیید، کد OTP به شماره موبایل شما ارسال می‌شود. ادامه می‌دهید؟">{{ $stageLabel }}</button>
        </form>
    @endif

    @if(session('approval_context.challenge_id'))
        <form class="mt-4 flex max-w-md gap-2" method="post" action="{{ route('payroll.approval.confirm', $sheet) }}">
            @csrf
            <input class="field text-center tracking-[.3em]" name="code" maxlength="6" inputmode="numeric" dir="ltr" placeholder="کد ۶ رقمی" required>
            <button class="btn-primary shrink-0">ثبت OTP</button>
        </form>
    @endif
</div>

@if($canEdit)
<div class="mb-3 flex items-center justify-between gap-3">
    <p class="text-xs text-neutral-400">تغییر هر سلول به‌صورت امن ذخیره می‌شود؛ دکمه ذخیره، ویرایش فعال را نیز ثبت می‌کند.</p>
    <div class="flex gap-2">
        <button id="save-grid" type="button" class="btn-secondary">ذخیره</button>
        @if(auth()->user()->hasRole('project_operator'))
            <form method="post" action="{{ route('payroll.submit', $sheet) }}">@csrf
                <button class="btn-primary" data-confirm="پس از ثبت نهایی، همه اپراتورهای این پروژه امکان ویرایش نخواهند داشت. ادامه می‌دهید؟">ذخیره و تأیید نهایی</button>
            </form>
        @endif
    </div>
</div>
@endif

@if($sheet->finalized_at)<div class="card p-8 text-center"><h2 class="font-bold">این لیست با تأیید مدیر مالی نهایی شده است.</h2><p class="mt-2 text-sm text-neutral-500">اطلاعات نهایی فقط از طریق فایل‌های PDF و Excel قابل دریافت است.</p><div class="mt-5 flex justify-center gap-2"><a class="btn-primary" href="{{ route('payroll.export.pdf',$sheet) }}">مشاهده PDF</a><a class="btn-secondary" href="{{ route('payroll.export.xlsx',$sheet) }}">دریافت Excel</a></div></div>@else
<div class="table-wrap">
<table class="data-table">
    <thead><tr><th class="sticky right-0 z-10 bg-neutral-50">پرسنلی</th><th>نام و نام خانوادگی</th><th>کد ملی</th>@foreach($sheet->period->columns as $col)<th>{{ $col->title }}@if($col->unit)<div class="mt-1 text-[10px] font-normal text-neutral-400">{{ $col->unit }}</div>@endif</th>@endforeach</tr></thead>
    <tbody>
    @foreach($sheet->rows as $row)
        <tr><td class="sticky right-0 bg-white font-mono">{{ $row->personnel_number_snapshot }}</td><td>{{ $row->first_name_snapshot }} {{ $row->last_name_snapshot }}</td><td dir="ltr">{{ $row->national_id_snapshot }}</td>
        @foreach($sheet->period->columns as $col)
            @php($cell = $row->cells->firstWhere('payroll_column_id',$col->id))
            @php($value = $cell?->{match($col->value_type){'number'=>'number_value','date'=>'date_value','boolean'=>'boolean_value',default=>'text_value'}})
            <td>
            @if($canEdit)
                <input class="min-w-28 rounded-lg border border-neutral-200 px-2 py-1.5 outline-none focus:border-neutral-400" data-cell data-row="{{ $row->id }}" data-column="{{ $col->id }}" data-version="{{ $cell?->version ?? 0 }}" value="{{ $value }}">
            @else
                <span>{{ $value ?? '—' }}</span>
            @endif
            </td>
        @endforeach
        </tr>
    @endforeach
    </tbody>
</table>
</div>
@endif

@if($canEdit)
<script>
let currentRevision = {{ (int)$sheet->content_revision }};
let saving = new Set();
const cells = [...document.querySelectorAll('[data-cell]')];

async function saveCell(input) {
    if (saving.has(input) || input.dataset.savedValue === input.value) return;
    saving.add(input);
    try {
        const response = await axios.patch(@json(route('payroll.cell.update',$sheet)), {
            row_id: +input.dataset.row,
            column_id: +input.dataset.column,
            value: input.value,
            version: +input.dataset.version
        }, {headers: {'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content}});
        input.dataset.version = response.data.version;
        input.dataset.savedValue = input.value;
        currentRevision = Math.max(currentRevision, response.data.revision);
        window.flashCellSaved(input);
    } catch (e) {
        alert(e.response?.data?.errors?.version?.[0] || e.response?.data?.errors?.value?.[0] || e.response?.data?.message || 'ذخیره انجام نشد.');
    } finally { saving.delete(input); }
}

cells.forEach(input => {
    input.dataset.savedValue = input.value;
    input.addEventListener('change', () => saveCell(input));
});
document.getElementById('save-grid')?.addEventListener('click', () => Promise.all(cells.map(saveCell)));

setInterval(async () => {
    if (document.activeElement?.matches?.('[data-cell]') || saving.size) return;
    try {
        const {data} = await axios.get(@json(route('payroll.changes',$sheet)), {params:{after_revision:currentRevision}});
        for (const remote of data.cells) {
            const input = document.querySelector(`[data-cell][data-row="${remote.row_id}"][data-column="${remote.column_id}"]`);
            if (input && !saving.has(input)) {
                input.value = remote.value ?? '';
                input.dataset.savedValue = input.value;
                input.dataset.version = remote.version;
            }
        }
        currentRevision = Math.max(currentRevision, data.revision);
    } catch (_) {}
}, 4000);
</script>
@endif

@if(isset($audit) && $audit->isNotEmpty())
<div class="card mt-6 p-5"><h2 class="font-bold">آخرین تغییرات</h2><div class="mt-4 space-y-3">@foreach($audit as $event)<div class="flex flex-wrap items-center justify-between gap-2 border-b border-neutral-100 pb-3 text-sm"><div><span class="font-semibold">{{ $event->actor?->name ?? 'سیستم' }}</span><span class="mx-2 text-neutral-300">/</span><span class="text-neutral-600">{{ $event->action }}</span></div><div class="text-xs text-neutral-400">نسخه {{ $event->content_revision ?? '—' }} · {{ $event->created_at }}</div></div>@endforeach</div></div>
@endif
@endsection
