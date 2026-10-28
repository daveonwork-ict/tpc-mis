<?php

namespace App\Repositories\Academic;

use App\Interfaces\Academic\SubjectAssignInterface;
use App\Models\Academic\SubjectAssign;
use App\Models\Academic\SubjectAssignChildren;
use App\Models\Staff\Staff;
use App\Models\StudentInfo\SessionClassStudent;
use App\Traits\ReturnFormatTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubjectAssignRepository implements SubjectAssignInterface
{
    use ReturnFormatTrait;

    private $model;

    private $classSetupRepo;

    public function __construct(SubjectAssign $model, ClassSetupRepository $classSetupRepo)
    {
        $this->model = $model;
        $this->classSetupRepo = $classSetupRepo;
    }

    public function all()
    {
        return $this->model->active()->where('session_id', setting('session'))->get();
    }

    public function getPaginateAll()
    {
        return $this->model::latest()->where('session_id', setting('session'))->paginate(10);
    }

    public function store($request)
    {
        DB::beginTransaction();
        try {

            if ($this->model::where('session_id', setting('session'))->where('classes_id', $request->class)->where('section_id', $request->section)->first()) {
                return $this->responseWithError(___('alert.there_is_already_a_class_for_this_session'), []);
            }

            $subAssign = new $this->model;
            $subAssign->session_id = setting('session');
            $subAssign->classes_id = $request->class;
            $subAssign->section_id = $request->section;
            $subAssign->status = $request->status;
            $subAssign->save();
            foreach ($request->subjects ?? [] as $key => $item) {
                $subAssignChild = new SubjectAssignChildren;
                $subAssignChild->subject_assign_id = $subAssign->id;
                $subAssignChild->subject_id = $item;
                $subAssignChild->staff_id = $request->teachers[$key];
                $subAssignChild->save();


                //create group chat for subject teacher and students
                if (hasModule('Chat') && \Modules\Chat\Entities\ChatSetting::first()->auto_group_create) {
                     \Modules\Chat\Services\GroupChatService::assignSubjectGroupchat($subAssign, $subAssignChild);
                }

            }
            DB::commit();

            return $this->responseWithSuccess(___('alert.created_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollback();
            dd($th);
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function getSubjects($request)
    {

        $result = $this->model->active()->where('session_id', setting('session'))->where('classes_id', $request->classes_id)->where('section_id', $request->section_id)->first();

        return SubjectAssignChildren::with('subject')
            ->where('subject_assign_id', @$result->id)
            ->when(Auth::user()->role_id == 5, function ($query) {
                return $query->where('staff_id', Auth::user()->staff->id);
            })
            ->when($request->is_optional == 1, function ($query) {
                $query->whereHas('subject', function ($q) {
                    $q->where('is_optional', 1);
                });
            })
            ->select('subject_id')
            ->get();
    }

    public function show($id)
    {
        $row = $this->model->find($id);
        $data['row'] = $row;
        $data['assignSubjects'] = array_fill(0, $row->subjectTeacher->count(), 0);
        $data['disabled'] = false;
        $data['redirect'] = $row->subjectTeacher->isEmpty();
        return $data;
    }

    public function update($request, $id)
    {

        DB::beginTransaction();
        try {

            if ($this->model::where('session_id', setting('session'))->where('classes_id', $request->class)->where('section_id', $request->section)->where('id', '!=', $id)->first()) {
                return $this->responseWithError(___('alert.there_is_already_a_class_for_this_session'), []);
            }

            $setup = $this->model->find($id);
            $setup->session_id = setting('session');
            $setup->classes_id = $request->class;
            $setup->section_id = $request->section;
            $setup->status = $request->status;
            $setup->save();

            SubjectAssignChildren::where('subject_assign_id', $setup->id)->delete();

            foreach ($request->subjects ?? [] as $key => $item) {
                $row = new SubjectAssignChildren;
                $row->subject_assign_id = $setup->id;
                $row->subject_id = $item;
                $row->staff_id = $request->teachers[$key];
                $row->save();
            }

            DB::commit();

            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollback();

            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $this->model->find($id)->delete();
            DB::commit();

            return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollback();

            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function checkSection($request)
    {
        if ($request->form_type == 'update') {

            $result = $this->model->active()->where('id', '!=', $request->id)->where('session_id', setting('session'))->where('classes_id', $request->class)->where('section_id', $request->section)->first();
        } else {

            $result = $this->model->active()->where('session_id', setting('session'))->where('classes_id', $request->class)->where('section_id', $request->section)->first();
        }

        $data = [];
        if ($result) {
            $data['message'] = ___('academic.already_assigned_for_this_section');
            $data['status'] = false;
            $data['sections'] = $this->classSetupRepo->getSections($request->class);
        } else {
            $data['message'] = '';
            $data['status'] = true;
            $data['sections'] = true;
        }

        return $data;
    }
}
