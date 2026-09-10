<?php
namespace App\Enums;

enum EditBarrier: string
{
    case None = 'none';
    case ProjectOperator = 'project_operator';
    case ProjectTeam = 'project_team';
    case ProjectAndHrOperator = 'project_and_hr_operator';
    case All = 'all';
}
