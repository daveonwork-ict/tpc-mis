<?php

namespace App\Repositories\BehaviourRecord;

use App\Enums\RoleEnum;
use App\Models\StudentInfo\Student;
use Modules\BehaviourRecord\Entities\Incident;
use Modules\BehaviourRecord\Entities\StudentIncidentAssign;

class BehaviourRecordRepository
{

    public function list($request)
    {
        $query = Student::with([
            'behaviourRecord.incident',
            'session_class_student.class',
            'session_class_student.section'
        ]);

        if ($request->filled('class') && ($request->get('class') != 'null')) {
            $query->whereHas('session_class_student', function ($q) use ($request) {
                $q->where('classes_id', $request->class);
            });
        }


        if ($request->filled('section') && ($request->get('section') != "null")) {
            $query->whereHas('session_class_student', function ($q) use ($request) {
                $q->where('section_id', $request->section);
            });
        }

        // Keyword search
        if ($request->filled('keyword') && ($request->get('keyword') != "null")) {
            $query->where(function ($q) use ($request) {
                $keyword = $request->keyword;
                $q->where('first_name', 'like', "%$keyword%")
                    ->orWhere('last_name', 'like', "%$keyword%")
                    ->orWhere('admission_no', 'like', "%$keyword%");
            });
        }

        return $query->paginate(10);
    }

    public function store($request)
    {
        try {
            $assignRecord = new StudentIncidentAssign();
            $assignRecord->incident_id = $request->incident_id;
            $assignRecord->student_id = $request->student_id;
            $assignRecord->request_by = auth()->user()->id;
            if (auth()->user()->role_id == RoleEnum::SUPERADMIN) {
                $assignRecord->approved_by = auth()->user()->id;
                $assignRecord->status = 'approved';
            }
            $assignRecord->short_note = $request->short_note;
            $assignRecord->save();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function recordStore($request)
    {
        try {
            $assignRecord = new StudentIncidentAssign();
            $assignRecord->incident_id = $request->incident_id;
            $assignRecord->student_id = $request->student_id;
            $assignRecord->request_by = auth()->user()->id;
            if (auth()->user()->role_id == RoleEnum::SUPERADMIN) {
                $assignRecord->approved_by = auth()->user()->id;
                $assignRecord->status = 'approved';
            }
            $assignRecord->short_note = $request->short_note;
            $assignRecord->save();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getRecordByStudentId($id)
    {
        return Student::with([
            'behaviourRecord.incident',
            'behaviourRecord.requestBy:id,name',
            'behaviourRecord.approvedBy:id,name',
            'session_class_student.class',
            'session_class_student.section'
        ])->where('id', $id)->first();
    }

    public function getRecordByStudent($id)
    {
        return StudentIncidentAssign::with('student', 'appeal', 'incident')
            ->where('student_id', $id)
            ->latest()
            ->paginate(10);
    }

    public function getRecordAPIByStudent($id)
    {
        $records = StudentIncidentAssign::with([
            'appeal',
            'incident:id,id,title,type,points,created_at',
        ])
            ->where('student_id', $id)
            ->latest()
            ->paginate(10);

        // Calculate gross points (positive - negative) directly from assigned incidents
        $grossPoints = $records->getCollection()->sum(function ($record) {
            if (!$record->incident) return 0;
            return $record->incident->type === 'positive'
                ? $record->incident->points
                : -$record->incident->points;
        });

        // Transform each record
        $records->getCollection()->transform(function ($record) {
            $points = $record->incident ? $record->incident->points : 0;
            $netPoints = $record->incident
                ? ($record->incident->type === 'positive' ? $points : -$points)
                : 0;

            return [
                'id' => $record->id,
                'impression' => ($netPoints >= 0
                    ? ___('behaviour_record.Positive')
                    : ___('behaviour_record.Negative')) . " ($netPoints)",
                'incident' => $record->incident?->title,
                'short_note' => $record->short_note,
                'assigned_date' => dateFormat($record->incident?->created_at),
                'appeal' => $record->appeal?->status,
            ];
        });

        // Append gross_point to paginator data
        return [
            'gross_point' => $grossPoints . ' ' . ($grossPoints >= 0 ? 'Positive' : 'Negative'),
            'data' => $records
        ];
    }

    public function delete($id)
    {
        try {
            StudentIncidentAssign::destroy($id);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function update($request)
    {
        try {
            $assignRecord = StudentIncidentAssign::find($request->id);
            $assignRecord->short_note = $request->short_note;
            $assignRecord->incident_id = $request->title;
            $assignRecord->created_at = $request->date;
            $assignRecord->save();

            return $assignRecord->incident;
        } catch (\Exception $e) {dd($e);
            return false;
        }
    }
}
