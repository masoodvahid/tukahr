<?php
namespace App\Enums;

enum RoleCode: string
{
    case ProjectOperator = 'project_operator';
    case ProjectManager = 'project_manager';
    case HrOperator = 'hr_operator';
    case HrManager = 'hr_manager';
    case Ceo = 'ceo';
    case FinanceManager = 'finance_manager';

    public function label(): string
    {
        return match($this) {
            self::ProjectOperator => 'اپراتور پروژه',
            self::ProjectManager => 'مدیر پروژه',
            self::HrOperator => 'اپراتور منابع انسانی',
            self::HrManager => 'مدیر منابع انسانی',
            self::Ceo => 'مدیرعامل',
            self::FinanceManager => 'مدیر مالی',
        };
    }
}
