<?php

namespace App\Models\Admissions;

use App\Models\BaseModel;

class Person extends BaseModel
{
    protected $table = 'persons';

    protected $guarded = ['id'];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([$this->first_name, $this->middle_name, $this->last_name, $this->suffix])));
    }

    public function getFormalNameAttribute(): string
    {
        $given = trim(implode(' ', array_filter([$this->first_name, $this->middle_name, $this->suffix])));

        return $this->last_name . ', ' . $given;
    }

    public function getFullAddressAttribute(): string
    {
        return implode(', ', array_filter([$this->address_line, $this->barangay, $this->city_municipality, $this->province, $this->zip_code]));
    }
}
