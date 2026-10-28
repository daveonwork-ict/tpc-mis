<?php

namespace App\Http\Resources\Student;

use App\Models\OnlineExamination\QuestionBank;
use DateTime;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class OnlineExamResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {


        $startDate              = new DateTime($this->start);
        $endDate                = new DateTime($this->end);
        $interval               = date_diff($startDate, $endDate);

        $totalMark              = QuestionBank::whereIn('id', $this->examQuestions->pluck('question_bank_id')->toArray())->sum('mark');

        $passPercentage         = examSetting('average_pass_marks');
        $passMark               = ($this->total_mark * $passPercentage) / 100;

        $data = [
            'id'                => $this->id,
            'name'              => $this->name,
            'subject_name'      => @$this->subject->name,
            'subject_code'      => @$this->subject->code,
            'total_question'    => @$this->examQuestions->count() ?? 0,
            'duration'          => $interval->format('%d Day %h Hour %i Minute'),
            'start_time'        => $this->start,
            'end_time'          => $this->end,
            'total_mark'        => (int) $totalMark
        ];

        $studentAnswers = $this->getStudentAnswers($this->id, auth()->user()->student->id ?? request('student_id') );

        if($studentAnswers && $studentAnswers->result >= $passMark){
            $resultStatus = 'Passed';
            $data['score']          =  @$studentAnswers->result ;
        }elseif($studentAnswers && $studentAnswers->result < $passMark){
           $resultStatus = 'Failed';
           $data['score']          =  @$studentAnswers->result ;
        }
        elseif(is_null($studentAnswers)){
            $resultStatus = 'Unsubmitted';
            $data['score']          =  0;
        }
        $data['result_status']  = $resultStatus;

        if (!request('is_question') && !request('is_result')) {
            $data['action_status']  = $this->actionStatus($studentAnswers);
        }

        return $data;
    }

    protected function actionStatus($studentAnswers)
    {
        $actionStatus = null;

        if ($studentAnswers && is_null(@$studentAnswers->result)) {
            $actionStatus = 'Result Pending'; // Result Pending means student already participant, but his/her mark is under reviewing.
        } elseif ($studentAnswers && @$studentAnswers->result) {
            $actionStatus = 'View Details';
        } elseif ($this->start >= now()) {
            $actionStatus = 'Comming Soon'; // Comming Soon means this exam will be start after start time.
        } elseif ($this->start <= now() && $this->end >= now()) {
            $actionStatus = 'Started'; // Started means now student can participant in this exam.
        } elseif ($this->end <= now()) {
            $actionStatus = 'Expired'; // Expired means student did not participant in this exam.
        }

        return $actionStatus;
    }
}
