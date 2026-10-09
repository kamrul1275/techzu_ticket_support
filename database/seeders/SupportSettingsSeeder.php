<?php

namespace Database\Seeders;

use App\Models\SlaPolicy;
use App\Models\BusinessHour;
use Illuminate\Database\Seeder;

class SupportSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. SLA Policies
        $policies = [
            ['low', 2880, 120],
            ['medium', 1440, 60],
            ['high', 480, 30],
            ['critical', 120, 15],
        ];

        foreach ($policies as [$priority, $minutes, $warning]) {
            SlaPolicy::updateOrCreate(
                ['priority' => $priority],
                [
                    'resolution_minutes' => $minutes,
                    'warning_before_minutes' => $warning,
                    'is_active' => true,
                ]
            );
        }

        // 2. Business Hours
        // 0 = Sunday, 6 = Saturday
        for ($day = 0; $day <= 6; $day++) {
            $isWorkingDay = $day <= 4;

            BusinessHour::updateOrCreate(
                ['day_of_week' => $day],
                [
                    'start_time' => $isWorkingDay ? '09:00:00' : null,
                    'end_time' => $isWorkingDay ? '18:00:00' : null,
                    'is_working_day' => $isWorkingDay,
                ]
            );
        }
    }
}