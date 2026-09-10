@extends('layouts.app', [
    'heading' => 'داشبورد',
    'subheading' => 'نمای کلی سامانه حقوق و دستمزد',
])

@section('content')
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['پروژه‌های فعال', $projects],
            ['کاربران فعال', $users],
            ['دوره‌های حقوق', $periods],
            ['لیست‌های در جریان', $pending],
        ] as [$label, $value])
            <div class="card p-5">
                <div class="text-sm text-neutral-500">{{ $label }}</div>
                <div class="mt-3 text-3xl font-black">{{ number_format($value) }}</div>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <div class="card p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="font-bold">وضعیت پیامک کاوه‌نگار</h2>
                    <p class="mt-2 text-sm leading-7 text-neutral-500">
                        {{ $smsStatus['message'] }}
                    </p>
                </div>

                <span class="badge whitespace-nowrap">
                    @if ($smsStatus['severity'] === 'ok')
                        سالم
                    @elseif ($smsStatus['severity'] === 'warning')
                        نیازمند بررسی
                    @else
                        اختلال
                    @endif
                </span>
            </div>

            @if (! is_null($smsStatus['credit']))
                <div class="mt-5 border-t border-neutral-100 pt-4">
                    <div class="text-xs text-neutral-400">اعتبار باقی‌مانده</div>
                    <div class="mt-1 text-lg font-bold">
                        {{ number_format($smsStatus['credit']) }} ریال
                    </div>
                </div>
            @endif

            @if ($smsStatus['severity'] !== 'ok')
                <div class="mt-5 rounded-xl bg-neutral-100 px-4 py-3 text-sm leading-6 text-neutral-700">
                    تا رفع این وضعیت، ورود با رمز عبور همچنان قابل استفاده است؛ اما عملیات‌هایی که به تأیید OTP نیاز دارند ممکن است انجام نشوند.
                </div>
            @endif
        </div>

        <div class="card p-6">
            <h2 class="font-bold">شروع کار</h2>
            <p class="mt-2 text-sm leading-7 text-neutral-500">
                ابتدا پروژه‌ها و کاربران را تعریف کنید، سپس دوره حقوق ماهانه را ایجاد و اطلاعات پرسنل را بارگذاری کنید.
            </p>
        </div>
    </div>
@endsection
