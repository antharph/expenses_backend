<?php

namespace App\Enums;

enum BudgetResetType: string
{
    case DateFixed = 'date_fixed';
    case Interval = 'interval';
    case Manual = 'manual';
}
