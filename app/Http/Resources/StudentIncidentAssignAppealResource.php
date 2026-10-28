<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\BehaviourRecord\Entities\StudentIncidentAssignAppeal;

class StudentIncidentAssignAppealResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $appeal = StudentIncidentAssignAppeal::with(['messages' => function ($q) {
            $q->where('sender_id', '!=', auth()->id())->latest();
        }])->find($this->id);
        $lastMessage = $appeal->messages->first(); // latest not from me

        return [
            'id'          => $this->id,
            'reciever_id' => @$lastMessage->sender_id ?? 1,
            'title'       => $this->title,
            'description' => $this->description,
            'status'      => $this->status,
            'created_at'  => dateFormat( $this->created_at),
            'updated_at'  =>  dateFormat($this->updated_at),
            'student_name'     => $this->student->first_name . ' ' . $this->student->last_name,
            'student_image'    => @globalAsset(@$this->student->user->upload->path, 'user.png'),
            'appealedBy' => @$this->requestBy->name,
            'incident' => @$this->behaviourRecord->incident->title .' ('.((@$this->behaviourRecord->incident->type == 'positive') ? '+'.@$this->behaviourRecord->incident->points : '-'.@$this->behaviourRecord->incident->points) .')',
        ];
    }
}
