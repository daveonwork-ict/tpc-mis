<?php

namespace App\Models\Examination;

use App\Models\BaseModel;
use App\Models\OnlineExamination\OnlineExam;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExamType extends BaseModel
{
    use HasFactory;

    public function scopeActive($query)
    {
        return $query->where('status', \App\Enums\Status::ACTIVE);
    }

    public function onlineExams()
    {
        return $this->hasMany(OnlineExam::class, 'exam_type_id', 'id');
    }
}
