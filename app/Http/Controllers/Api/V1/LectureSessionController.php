<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\LectureSession;
use Illuminate\Http\Request;

class LectureSessionController extends Controller
{
    public function index(Request $req)
    {
        // توافق: timetable_id == entry_id
        $entryId = $req->input('entry_id') ?? $req->input('timetable_id');
        $q = LectureSession::query();
        if ($entryId) $q->where('entry_id', (int)$entryId);
        if ($req->filled('date')) $q->where('session_date', $req->date);

        $list = $q->orderByDesc('session_date')->get();
        return response()->json(['data' => $list]);
    }

    public function store(Request $req)
    {
        $data = $req->validate([
            'entry_id' => 'nullable|integer|exists:timetable_entries,entry_id',
            'timetable_id' => 'nullable|integer', // alias
            'session_date' => 'required|date',
            'start_time'   => 'required|date_format:H:i',
            'end_time'     => 'required|date_format:H:i|after:start_time',
            'actual_classroom_id'     => 'nullable|integer|exists:classrooms,classroom_id',
            'actual_attendance_count' => 'nullable|integer|min:0',
            'session_code' => 'required|string|max:50|unique:lecture_sessions,session_code',
            'status'       => 'nullable|integer|min:0|max:2',
            'attendance_overage_alert' => 'nullable|boolean',
            'system_attendance_count'  => 'nullable|integer|min:0',
        ]);

        $entryId = $data['entry_id'] ?? $data['timetable_id'] ?? null;
        if (!$entryId) return response()->json(['message' => 'entry_id_required'], 422);

        $session = LectureSession::create([
            'entry_id' => (int)$entryId,
            'session_date' => $data['session_date'],
            'start_time'   => $data['start_time'],
            'end_time'     => $data['end_time'],
            'actual_classroom_id'     => $data['actual_classroom_id'] ?? null,
            'actual_attendance_count' => $data['actual_attendance_count'] ?? null,
            'session_code' => $data['session_code'],
            'status'       => (int)($data['status'] ?? 0),
            'attendance_overage_alert' => (bool)($data['attendance_overage_alert'] ?? false),
            'system_attendance_count'  => (int)($data['system_attendance_count'] ?? 0),
        ]);

        return response()->json($session, 201);
    }
}