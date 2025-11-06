<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\TimetableEntry;
use App\Services\ScheduleResolver;
use App\Services\ConflictDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TimetableEntryController extends Controller
{
    public function __construct(
        private ScheduleResolver $resolver,
        private ConflictDetector $conflicts
    ) {}

    public function index(Request $req)
    {
        $q = TimetableEntry::query()
            ->with(['course','lecturer','classroom','day','period']);

        if ($req->filled('schedule_id')) $q->where('schedule_id', (int)$req->schedule_id);
        if ($req->filled('day_id'))      $q->where('day_id', (int)$req->day_id);
        if ($req->filled('period_id'))   $q->where('period_id', (int)$req->period_id);
        if ($req->filled('group_id'))    $q->where('group_id', (int)$req->group_id);
        if ($req->filled('lecturer_id')) $q->where('lecturer_id', (int)$req->lecturer_id);

        $rows = $q->orderBy('day_id')->orderBy('period_id')->get();
        return response()->json(['data' => $rows]);
    }

    public function store(Request $req)
    {
        $data = $req->validate([
            'course_id'     => 'required|integer|exists:courses,course_id',
            'lecturer_id'   => 'required|integer|exists:lecturers,lecturer_id',
            'group_id'      => 'required|integer|exists:student_groups,group_id',
            'classroom_id'  => 'required|integer|exists:classrooms,classroom_id',
            'day_id'        => 'required|integer|exists:days,day_id',
            'period_id'     => 'required|integer|exists:periods,period_id',
            'lecture_type'  => 'nullable|integer|min:0|max:5',
            'status'        => 'nullable|integer|min:0|max:2',
            'gender_type'   => 'nullable|integer|min:0|max:2',
            'lecture_hours' => 'nullable|numeric|min:0',
            'notes'         => 'nullable|string|max:255',

            // لتحديد/استنتاج الجدول
            'schedule_id'   => 'nullable|integer|exists:timetable_sets,schedule_id',
            'college_id'    => 'required_without:schedule_id|integer|exists:colleges,college_id',
            'department_id' => 'required_without:schedule_id|integer|exists:departments,department_id',
            'semester_id'   => 'nullable|integer|exists:semesters,semester_id',
            'start_date'    => 'nullable|date',
            'end_date'      => 'nullable|date|after_or_equal:start_date',
        ]);

        // حدد schedule_id
        $scheduleId = $data['schedule_id'] ?? null;
        if (!$scheduleId) {
            $set = $this->resolver->resolveOrCreate([
                'college_id'    => $data['college_id'],
                'department_id' => $data['department_id'],
                'semester_id'   => $req->input('semester_id'),
                'course_id'     => $data['course_id'],
                'start_date'    => $req->input('start_date'),
                'end_date'      => $req->input('end_date'),
            ]);
            $scheduleId = $set->schedule_id;
        }

        // كشف التعارضات داخل نفس الجدول
        $conflicts = $this->conflicts->findConflicts($scheduleId, $data);
        if (!empty($conflicts)) {
            return response()->json(['message' => 'conflict', 'conflicts' => $conflicts], 409);
        }

        $entry = TimetableEntry::create([
            'schedule_id'   => $scheduleId,
            'course_id'     => (int)$data['course_id'],
            'lecturer_id'   => (int)$data['lecturer_id'],
            'group_id'      => (int)$data['group_id'],
            'classroom_id'  => (int)$data['classroom_id'],
            'day_id'        => (int)$data['day_id'],
            'period_id'     => (int)$data['period_id'],
            'lecture_type'  => (int)($data['lecture_type'] ?? 0),
            'status'        => (int)($data['status'] ?? 1),
            'gender_type'   => (int)($data['gender_type'] ?? 0),
            'lecture_hours' => (float)($data['lecture_hours'] ?? 2.0),
            'notes'         => $data['notes'] ?? null,
        ]);

        // لأجل التوافق مع الواجهة (تعيد timetable_id)
        return response()->json([
            'timetable_id' => $entry->entry_id,
            'entry' => $entry,
        ], 201);
    }

    public function bulk(Request $req)
    {
        $validated = $req->validate([
            'rows' => 'required|array|min:1',
            'rows.*.course_id'     => 'required|integer|exists:courses,course_id',
            'rows.*.lecturer_id'   => 'required|integer|exists:lecturers,lecturer_id',
            'rows.*.group_id'      => 'required|integer|exists:student_groups,group_id',
            'rows.*.classroom_id'  => 'required|integer|exists:classrooms,classroom_id',
            'rows.*.day_id'        => 'required|integer|exists:days,day_id',
            'rows.*.period_id'     => 'required|integer|exists:periods,period_id',
            'rows.*.lecture_type'  => 'nullable|integer|min:0|max:5',
            'rows.*.status'        => 'nullable|integer|min:0|max:2',
            'rows.*.gender_type'   => 'nullable|integer|min:0|max:2',
            'rows.*.lecture_hours' => 'nullable|numeric|min:0',
            'rows.*.notes'         => 'nullable|string|max:255',

            'rows.*.schedule_id'   => 'nullable|integer|exists:timetable_sets,schedule_id',
            'rows.*.college_id'    => 'required_without:rows.*.schedule_id|integer|exists:colleges,college_id',
            'rows.*.department_id' => 'required_without:rows.*.schedule_id|integer|exists:departments,department_id',
            'rows.*.semester_id'   => 'nullable|integer|exists:semesters,semester_id',
            'rows.*.start_date'    => 'nullable|date',
            'rows.*.end_date'      => 'nullable|date|after_or_equal:rows.*.start_date',
        ]);

        $rows = $validated['rows'];
        $inserted = 0;
        $conflictsOut = [];
        $entriesOut = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                try {
                    $scheduleId = $row['schedule_id'] ?? null;
                    if (!$scheduleId) {
                        $set = $this->resolver->resolveOrCreate([
                            'college_id'    => $row['college_id'],
                            'department_id' => $row['department_id'],
                            'semester_id'   => $row['semester_id'] ?? null,
                            'course_id'     => $row['course_id'],
                            'start_date'    => $row['start_date'] ?? null,
                            'end_date'      => $row['end_date'] ?? null,
                        ]);
                        $scheduleId = $set->schedule_id;
                    }

                    $rowConf = $this->conflicts->findConflicts($scheduleId, $row);
                    if (!empty($rowConf)) {
                        $conflictsOut[] = array_merge($row, ['conflicts' => $rowConf]);
                        continue;
                    }

                    $entry = TimetableEntry::create([
                        'schedule_id'   => $scheduleId,
                        'course_id'     => (int)$row['course_id'],
                        'lecturer_id'   => (int)$row['lecturer_id'],
                        'group_id'      => (int)$row['group_id'],
                        'classroom_id'  => (int)$row['classroom_id'],
                        'day_id'        => (int)$row['day_id'],
                        'period_id'     => (int)$row['period_id'],
                        'lecture_type'  => (int)($row['lecture_type'] ?? 0),
                        'status'        => (int)($row['status'] ?? 1),
                        'gender_type'   => (int)($row['gender_type'] ?? 0),
                        'lecture_hours' => (float)($row['lecture_hours'] ?? 2.0),
                        'notes'         => $row['notes'] ?? null,
                    ]);

                    $inserted++;
                    $entriesOut[] = $entry;
                } catch (\Throwable $e) {
                    $conflictsOut[] = array_merge($row, ['error' => $e->getMessage()]);
                }
            }

            DB::commit();
            return response()->json([
                'inserted'   => $inserted,
                'conflicts'  => $conflictsOut,
                'timetables' => $entriesOut, // الواجهة تقرأه لتحديث الشبكة
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'bulk_error', 'error' => $e->getMessage()], 500);
        }
    }

    // Aliases متوافقة مع الواجهة الحالية
    public function storeAlias(Request $r)  { return $this->store($r); }
    public function bulkAlias(Request $r)   { return $this->bulk($r); }
}