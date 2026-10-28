<?php

namespace App\Models\Enrollment;

use App\Enums\Enrollment;
use App\Models\BaseModel;
use Illuminate\Support\Carbon;

class OfferingSchedule extends BaseModel
{
    protected $guarded = ['id'];

    public function offering()
    {
        return $this->belongsTo(Offering::class);
    }

    public function overlaps(self $other): bool
    {
        return (int) $this->day === (int) $other->day
            && $this->start_time < $other->end_time
            && $other->start_time < $this->end_time;
    }

    public function getLabelAttribute(): string
    {
        $day = ___('enrollment.day_short_' . Enrollment::DAYS[$this->day]);

        return $day . ' ' . Carbon::parse($this->start_time)->format('g:i') . '–' . Carbon::parse($this->end_time)->format('g:i A');
    }
}
