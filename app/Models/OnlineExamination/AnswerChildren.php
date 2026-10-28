<?php

namespace App\Models\OnlineExamination;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Model;
use App\Models\OnlineExamination\QuestionBank;
use App\Models\Upload;
use Symfony\Component\Console\Question\Question;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AnswerChildren extends BaseModel
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'answer' => 'array',
    ];

    public function answer()
    {
        return $this->belongsTo(Answer::class, 'answer_id', 'id');
    }

    public function upload()
    {
        return $this->belongsTo(Upload::class, 'answer', 'id');
    }
}
