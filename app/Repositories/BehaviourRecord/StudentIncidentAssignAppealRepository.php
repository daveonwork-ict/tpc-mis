<?php

namespace App\Repositories\BehaviourRecord;

use App\Http\Resources\StudentIncidentAssignAppealResource;
use App\Repositories\Academic\ClassSetupRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\BehaviourRecord\Entities\AppealConversation;
use Modules\BehaviourRecord\Entities\StudentIncidentAssignAppeal;

class StudentIncidentAssignAppealRepository
{
    public function all()
    {
        return StudentIncidentAssignAppeal::
        with('student:id,first_name,last_name', 'behaviourRecord.requestBy', 'behaviourRecord.incident','requestBy:id,name')
            ->latest()
            ->paginate(10);
    }

    public function reject($id)
    {
        try {
            DB::beginTransaction();
            $reject = StudentIncidentAssignAppeal::find($id);

            send_web_notification('Incident Reject', 'Appeal title: '.  $reject->title, $reject->student->parent->user_id, route('parent-panel.behaviourrecord.index'));
            send_web_notification('Incident Reject', 'Appeal title: '.  $reject->title, $reject->student->user_id, route('student.behaviourrecord.index'));

            StudentIncidentAssignAppeal::find($id)->delete();
            DB::commit();
            return true;
        }catch (\Exception $e){
            return false;
        }
    }

    public function withdraw($id)
    {
        try {
            DB::beginTransaction();
            $data = StudentIncidentAssignAppeal::with('behaviourRecord')->where('student_id', $id)->first();
            $data->status = 'withdraw';
            $record = $data->behaviourRecord;
            $record->status = 'withdraw';
            $data->save();
            $record->save();
            send_web_notification('Incident Withdraw', 'Appeal title: '.  $data->title, $data->student->parent->user_id, route('parent-panel.behaviourrecord.index'));
            send_web_notification('Incident Withdraw', 'Appeal title: '.  $data->title, $data->student->user_id, route('student.behaviourrecord.index'));
            DB::commit();
            return true;
        }catch (\Exception $e){
            DB::rollBack();
            return false;
        }
    }

    public function details($id)
    {
        $appeal = StudentIncidentAssignAppeal::with(['behaviourRecord.incident', 'student', 'requestBy'])
            ->find($id);


        $data['appeal'] = $appeal;

        if ($appeal) {
            $data['messages'] = AppealConversation::with('sender','receiver')->where('appeal_id', $id)
                ->orderBy('created_at', 'asc')
                ->get();
        }

        return $data;
    }

    public function appealDetailsAPI($id)
    {
       $appeal = StudentIncidentAssignAppeal::with(['behaviourRecord.incident', 'student', 'requestBy'])
            ->where('assign_id',$id)->first();

        if ($appeal) {
            $data['appeal'] = new StudentIncidentAssignAppealResource($appeal);
            $messages = AppealConversation::with(['sender', 'receiver'])
                ->where('appeal_id', $appeal->id)
                ->where(function ($q) use ($appeal) {
                    $q->where(function ($q2) use ($appeal) {
                        $q2->where('sender_id', auth()->id())
                            ->orWhere('receiver_id', $appeal->requestBy->id);
                    })->orWhere(function ($q2) use ($appeal) {
                        $q2->where('sender_id', $appeal->requestBy->id)
                            ->where('receiver_id', auth()->id());
                    });
                })
                ->orderBy('created_at', 'asc') // 🔁 Changed to latest first
                ->get()
                ->map(function ($message) {
                    return [
                        'id'         => $message->id,
                        'message'    => $message->message,
                        'sender'     => $message->sender?->name,
                        'sender_avatar' =>  @globalAsset(@$message->sender->upload->path),
                        'receiver'   => $message->receiver?->name,
                        'receiver_avatar'   => @globalAsset(@$message->receiver->upload->path),
                        'created_at' => $message->created_at->diffForHumans(),
                        'is_me'      => $message->sender_id === auth()->id(),
                    ];
                });

            $data['messages'] = $messages;
            return $data;
        }
    }

    public function changeStatus(Request $request, $id)
    {
        try {
            $appeal = StudentIncidentAssignAppeal::find($id);
            $appeal->status = $request->status;
            $appeal->save();

            if ($request->status == 'granted'){
                $record = $appeal->behaviourRecord;
                $record->delete();
            }
            return true;
        }catch (\Exception $e){
            return false;
        }
    }

}
