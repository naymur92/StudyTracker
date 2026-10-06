<?php

namespace App\Services\StudyTracker;

use Carbon\Carbon;
use Carbon\CarbonInterface;

final class WeekMath
{
    /** First day of the week containing $date, for a week starting on $weekStartsOn (0 = Sunday). */
    public static function weekStart(CarbonInterface $date, int $weekStartsOn): Carbon
    {
        $date = Carbon::parse($date)->startOfDay();
        $back = ($date->dayOfWeek - $weekStartsOn + 7) % 7;

        return $date->subDays($back);
    }
}
