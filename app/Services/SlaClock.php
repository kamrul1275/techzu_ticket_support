<?php

namespace App\Services;

use App\Models\BusinessHoliday;
use App\Models\BusinessHour;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use RuntimeException;

class SlaClock
{
    private ?array $hours = null;
    private ?array $holidays = null;

    /**
     * Calculate a deadline using working time only.
     * The returned instant is in UTC.
     */
    public function addWorkingMinutes(
        CarbonInterface $start,
        int $minutes
    ): CarbonImmutable {

        if ($minutes <= 0) {
            throw new RuntimeException('SLA duration must be positive.');
        }

        $timezone = config('supportdesk.timezone');

        $cursor = CarbonImmutable::instance($start)
            ->setTimezone($timezone);

        $remainingSeconds = $minutes * 60;

        // Safety limit prevents infinite loops if calendar is invalid.
        for ($dayCount = 0; $dayCount < 3660; $dayCount++) {

            $window = $this->workingWindow($cursor->startOfDay());

            if ($window !== null) {

                [$opening, $closing] = $window;

                // Move to opening time if ticket arrived early.
                if ($cursor->lessThan($opening)) {
                    $cursor = $opening;
                }

                if ($cursor->lessThan($closing)) {

                    $availableSeconds =
                        $closing->getTimestamp() - $cursor->getTimestamp();

                    if ($remainingSeconds <= $availableSeconds) {
                        return $cursor
                            ->addSeconds($remainingSeconds)
                            ->utc();
                    }

                    $remainingSeconds -= $availableSeconds;
                }
            }

            // Continue from midnight of the next day.
            $cursor = $cursor->addDay()->startOfDay();
        }

        throw new RuntimeException(
            'Unable to calculate SLA deadline. Check business hours.'
        );
    }

    /**
     * Count the number of working seconds between two instants.
     * Useful for detecting approaching SLA breaches.
     */
    public function workingSecondsBetween(
        CarbonInterface $start,
        CarbonInterface $end
    ): int {

        $timezone = config('supportdesk.timezone');

        $from = CarbonImmutable::instance($start)
            ->setTimezone($timezone);

        $until = CarbonImmutable::instance($end)
            ->setTimezone($timezone);

        if ($from->greaterThanOrEqualTo($until)) {
            return 0;
        }

        $seconds = 0;
        $day = $from->startOfDay();

        for ($i = 0; $i < 3660; $i++) {

            if ($day->greaterThan($until)) {
                break;
            }

            $window = $this->workingWindow($day);

            if ($window !== null) {

                [$opening, $closing] = $window;

                $effectiveStart = $from->greaterThan($opening)
                    ? $from
                    : $opening;

                $effectiveEnd = $until->lessThan($closing)
                    ? $until
                    : $closing;

                if ($effectiveStart->lessThan($effectiveEnd)) {
                    $seconds +=
                        $effectiveEnd->getTimestamp()
                        - $effectiveStart->getTimestamp();
                }
            }

            $day = $day->addDay();
        }

        return $seconds;
    }

    /**
     * Return start/end of a working day or null if closed.
     */
    private function workingWindow(
        CarbonImmutable $day
    ): ?array {

        $this->loadCalendar();

        $date = $day->format('Y-m-d');

        // Holiday: full-day closure.
        if (isset($this->holidays[$date])) {
            return null;
        }

        // Carbon weekday: Sunday=0, Saturday=6.
        $hours = $this->hours[$day->dayOfWeek] ?? null;

        if (
            !$hours ||
            !$hours->is_working_day ||
            !$hours->start_time ||
            !$hours->end_time
        ) {
            return null;
        }

        $opening = $day->setTimeFromTimeString(
            $hours->start_time
        );

        $closing = $day->setTimeFromTimeString(
            $hours->end_time
        );

        // This implementation supports same-day office shifts.
        if ($closing->lessThanOrEqualTo($opening)) {
            throw new RuntimeException(
                'Business hours must have an end time after start time.'
            );
        }

        return [$opening, $closing];
    }

    /**
     * Load business calendar once per service instance.
     */
    private function loadCalendar(): void
    {
        if ($this->hours !== null) {
            return;
        }

        $this->hours = BusinessHour::query()
            ->get()
            ->keyBy('day_of_week')
            ->all();

        $dates = BusinessHoliday::query()
            ->get(['holiday_date'])
            ->map(
                fn ($holiday) =>
                    $holiday->holiday_date->format('Y-m-d')
            )
            ->all();

        $this->holidays = array_fill_keys($dates, true);
    }
}
