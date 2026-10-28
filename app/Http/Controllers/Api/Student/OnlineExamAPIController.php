<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\Student\ExamTypeResource;
use App\Http\Resources\Student\OnlineExamQuestionResource;
use App\Http\Resources\Student\OnlineExamResource;
use App\Models\Examination\ExamType;
use App\Models\OnlineExamination\Answer;
use App\Models\OnlineExamination\AnswerChildren;
use App\Models\OnlineExamination\OnlineExam;
use App\Models\OnlineExamination\OnlineExamChildrenQuestions;
use App\Traits\CommonHelperTrait;
use App\Traits\ReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OnlineExamAPIController extends Controller
{
    use CommonHelperTrait, ReturnFormatTrait;

    public function index()
    {
        try {

            if (! sessionClassStudent()) {
                return $this->responseWithError(___('alert.Student not found'));
            }

            $sessionClassStudent = sessionClassStudent();

            $onlineExams = OnlineExam::active()
                ->when(request()->filled('exam_type_id'), function ($query) {
                    return $query->where('exam_type_id', request('exam_type_id'));
                })
                ->where('session_id', $sessionClassStudent->session_id)
                ->where('classes_id', $sessionClassStudent->classes_id)
                ->where('section_id', $sessionClassStudent->section_id)
                                    // ->where('published', '<=', now())
                ->with('answer')
                ->get();

            $data = OnlineExamResource::collection($onlineExams);

            return $this->responseWithSuccess(___('alert.success'), $data);

        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong'), $th->getMessage());
        }
    }

    public function onlineExamQuestions($online_exam_id)
    {
        try {
            if (! sessionClassStudent()) {
                return $this->responseWithError(___('alert.Student not found'));
            }

            $request = request();
            $request->request->add(['is_question' => true]);

            $sessionClassStudent = sessionClassStudent();

            $onlineExam = OnlineExam::active()
                ->where('id', $online_exam_id)
                ->where('session_id', $sessionClassStudent->session_id)
                ->where('classes_id', $sessionClassStudent->classes_id)
                ->where('section_id', $sessionClassStudent->section_id)
                                    // ->where('published', '<=', now())
                ->with('examQuestions');

            $examInfo = OnlineExamResource::collection($onlineExam->take(1)->get());

            $data['exam_info'] = @$examInfo[0];
            $data['questions'] = OnlineExamQuestionResource::collection($onlineExam->first()?->examQuestions ?? []);

            return $this->responseWithSuccess(___('alert.success'), $data);

        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong'), $th->getMessage());
        }
    }

    public function store(Request $request, $online_exam_id)
    {
        try {

            if (! sessionClassStudent()) {
                return $this->responseWithError(___('alert.Student not found'));
            }

            $student_id = @sessionClassStudent()->student_id;
            $isParticipant = Answer::where([
                'online_exam_id' => $online_exam_id,
                'student_id' => $student_id,
            ])
                ->first();

            if ($isParticipant) {
                return $this->responseWithError(___('alert.already_participant'), []);
            }

            DB::transaction(function () use ($request, $online_exam_id, $student_id) {
                $answer = Answer::create([
                    'online_exam_id' => $online_exam_id,
                    'student_id' => $student_id,
                ]);

                foreach ($request->data ?? [] as $value) {
                    $question_bank_id = OnlineExamChildrenQuestions::where('id', $value['question_ids'])->first()?->question_bank_id;

                    if ($question_bank_id) {
                        AnswerChildren::create([
                            'answer_id' => $answer->id,
                            'question_bank_id' => $question_bank_id,
                            'answer' => $value['answers'],
                        ]);
                    }
                }
            });

            return $this->responseWithSuccess(___('alert.submitted_successfully'), []);

        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong'), $th->getMessage());
        }
    }

    public function result($online_exam_id)
    {
        try {

            if (! sessionClassStudent()) {
                return $this->responseWithError(___('alert.Student not found'));
            }

            $request = request();
            $request->request->add(['is_result' => true]);

            $sessionClassStudent = sessionClassStudent();

            $onlineExam = OnlineExam::active()
                ->where('id', $online_exam_id)
                ->where('session_id', $sessionClassStudent->session_id)
                ->where('classes_id', $sessionClassStudent->classes_id)
                ->where('section_id', $sessionClassStudent->section_id)
                ->where('published', '<=', now())
                ->with('examQuestions');

            $examInfo = OnlineExamResource::collection($onlineExam->take(1)->get());

            $data['exam_info'] = @$examInfo[0];
            $data['questions'] = OnlineExamQuestionResource::collection(@$onlineExam->first()->examQuestions ?? []);

            return $this->responseWithSuccess(___('alert.success'), $data);

        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong'), $th->getMessage());
        }
    }

    public function onlineExamTypes()
    {
        try {

            if (! sessionClassStudent()) {
                return $this->responseWithError(___('alert.Student not found'));
            }

            $sessionClassStudent = sessionClassStudent();

            $examTypes = ExamType::active()
                ->whereHas('onlineExams', function ($q) use ($sessionClassStudent) {
                    $q->where('session_id', $sessionClassStudent->session_id)
                        ->where('classes_id', $sessionClassStudent->classes_id)
                        ->where('section_id', $sessionClassStudent->section_id);
                })
                ->get();

            $data = ExamTypeResource::collection($examTypes);

            return $this->responseWithSuccess(___('alert.success'), $data);

        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong'), $th->getMessage());
        }
    }

    public function uploadFile(Request $request)
    {
        try {
            $request->validate([
                'answer_file' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
            ]);

            $upload_id = $this->UploadImageCreate($request->answer_file, 'backend/uploads/onlineexam/answer/');
            if (! $upload_id) {
                return $this->responseWithError(___('alert.something_went_wrong'));
            }

            return $this->responseWithSuccess(___('alert.submitted_successfully'), ['upload_id' => $upload_id]);

        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong'), $th->getMessage());
        }
    }
}
