<?php
namespace App\Enums;

enum ApprovalStage: string
{
    case ProjectManager = 'project_manager';
    case HrOperator = 'hr_operator';
    case HrManager = 'hr_manager';
    case Ceo = 'ceo';
    case FinanceManager = 'finance_manager';
}
