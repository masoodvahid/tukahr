@extends('layouts.app', [
    'heading' => 'دوره‌های حقوق',
    'subheading' => 'ایجاد ماه جدید، کپی ساختار ماه قبل و بارگذاری پرسنل',
])

@section('content')
    <div class="grid gap-6 xl:grid-cols-[400px_1fr]">
        <form class="card h-fit space-y-4 p-5" method="post" action="{{ route('periods.store') }}">
            @csrf

            <h2 class="font-bold">دوره جدید</h2>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="label" for="year">سال شمسی</label>
                    <select class="field" id="year" name="year" required>
                        @foreach ($years as $year)
                            <option value="{{ $year }}" @selected((int) old('year', $currentYear) === $year)>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label" for="month">ماه</label>
                    <select class="field" id="month" name="month" required>
                        @foreach ($months as $monthNumber => $monthName)
                            <option value="{{ $monthNumber }}" @selected((int) old('month', $currentMonth) === $monthNumber)>
                                {{ $monthName }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="label" for="title">عنوان</label>
                <input
                    class="field"
                    id="title"
                    name="title"
                    value="{{ old('title') }}"
                    placeholder="مثلاً حقوق شهریور ۱۴۰۵"
                    required
                >
            </div>

            <div>
                <label class="label" for="copy_from">کپی ساختار از ماه قبل</label>
                <select class="field" id="copy_from" name="copy_from">
                    <option value="">بدون کپی</option>
                    @foreach ($periods as $period)
                        <option value="{{ $period->id }}" @selected((string) old('copy_from') === (string) $period->id)>
                            {{ $period->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <label class="flex items-center gap-2 text-sm text-neutral-600">
                <input type="checkbox" name="copy_values" value="1" @checked(old('copy_values'))>
                مقادیر ماه قبل نیز کپی شوند
            </label>

            <button class="btn-primary w-full">ایجاد دوره</button>
        </form>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>دوره</th>
                        <th>ستون‌ها</th>
                        <th>پروژه‌ها</th>
                        <th>مهلت</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($periods as $period)
                        <tr>
                            <td>{{ $period->title }}</td>
                            <td>{{ $period->columns_count }}</td>
                            <td>{{ $period->sheets_count }}</td>
                            <td dir="ltr">{{ $period->edit_deadline_at }}</td>
                            <td>
                                <a class="underline" href="{{ route('periods.show', $period) }}">مدیریت</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
