<?php

namespace App\Services;

use App\Enums\CafeOpenStatus;
use App\Models\Cafe;
use Carbon\Carbon;

class CafeStatusService
{
    /**
     * Mapping dari Carbon::dayOfWeek (0=Minggu...6=Sabtu)
     * ke konvensi project (0=Senin...6=Minggu).
     * Lihat DATABASE-SCHEMA.md catatan day_of_week & REVIEW-NOTES.md §1.3.
     */
    private function mapCarbonDayToProjectDay(int $carbonDay): int
    {
        // Carbon: 0=Minggu, 1=Senin, 2=Selasa, ..., 6=Sabtu
        // Project: 0=Senin, 1=Selasa, ..., 5=Sabtu, 6=Minggu
        return $carbonDay === 0 ? 6 : $carbonDay - 1;
    }

    public function getOpenStatus(Cafe $cafe, ?Carbon $atTime = null): CafeOpenStatus
    {
        $atTime = $atTime ?? now();
        $projectDayOfWeek = $this->mapCarbonDayToProjectDay($atTime->dayOfWeek);

        $hour = $cafe->operatingHours
            ->firstWhere('day_of_week', $projectDayOfWeek);

        if ($hour === null) {
            return CafeOpenStatus::Closed;
        }

        if ($hour->is_closed) {
            return CafeOpenStatus::Closed;
        }

        $currentTime = $atTime->format('H:i:s');
        $openTime = $hour->open_time;
        $closeTime = $hour->close_time;

        if ($openTime === null || $closeTime === null) {
            return CafeOpenStatus::Closed;
        }

        if ($currentTime >= $openTime && $currentTime <= $closeTime) {
            return CafeOpenStatus::Open;
        }

        return CafeOpenStatus::Closed;
    }
}
