<?php

namespace App\Http\Controllers\Api\v1;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TimetableSet;

class TimetableSetController extends Controller
{
// GET /api/v1/timetable-sets
// يدعم الفلترة بـ college_id (إجباري عادة في الاستخدام العملي)، ويدعم الفلترة الاختيارية بـ semester_id/department_id/status/is_primary
public function index(Request $req)
{
$q = TimetableSet::query();

    if ($req->filled('college_id')) {
        $q->where('college_id', (int) $req->college_id);
    }

    // ندعم إرسال null كنص لاختيار الجداول العامة (بدون ترم/قسم)
    if ($req->has('semester_id')) {
        $val = $req->semester_id;
        if ($val === null || $val === 'null' || $val === '') {
            $q->whereNull('semester_id');
        } else {
            $q->where('semester_id', (int) $val);
        }
    }

    if ($req->has('department_id')) {
        $val = $req->department_id;
        if ($val === null || $val === 'null' || $val === '') {
            $q->whereNull('department_id');
        } else {
            $q->where('department_id', (int) $val);
        }
    }

    if ($req->filled('status')) {
        $q->where('status', $req->status);
    }

    if ($req->has('is_primary') && $req->boolean('is_primary')) {
        $q->where('is_primary', true);
    }

    $items = $q->orderByDesc('schedule_id')->get();

    return response()->json(['data' => $items]);
}

// POST /api/v1/timetable-sets
// إنشاء جدول عام للكلية (semester_id و department_id اختياريان/nullable) كما طلبت
public function store(Request $req)
{
    $data = $req->validate([
        'college_id'    => 'required|integer|exists:colleges,college_id',
        'name'          => 'required|string|max:100',
        'start_date'    => 'required|date',
        'end_date'      => 'required|date|after_or_equal:start_date',
        'weeks_count'   => 'nullable|integer|min:1|max:30',
        'status'        => 'nullable|in:draft,published,archived',
        'is_primary'    => 'nullable|boolean',
        // كلاهما اختياريان الآن
        'semester_id'   => 'nullable|integer|exists:semesters,semester_id',
        'department_id' => 'nullable|integer|exists:departments,department_id',
        'notes'         => 'nullable|string|max:255',
    ]);

    // إذا كان هذا الجدول Primary، عطّل primary عن بقية الجداول في نفس النطاق (نفس الكلية + نفس (الترم/القسم) أو null)
    if (!empty($data['is_primary'])) {
        TimetableSet::where('college_id', $data['college_id'])
            ->when(array_key_exists('semester_id', $data), function ($q) use ($data) {
                return is_null($data['semester_id'])
                    ? $q->whereNull('semester_id')
                    : $q->where('semester_id', $data['semester_id']);
            })
            ->when(array_key_exists('department_id', $data), function ($q) use ($data) {
                return is_null($data['department_id'])
                    ? $q->whereNull('department_id')
                    : $q->where('department_id', $data['department_id']);
            })
            ->update(['is_primary' => false]);
    }

    $set = TimetableSet::create([
        'college_id'    => $data['college_id'],
        'semester_id'   => $data['semester_id'] ?? null,      // اختياري
        'department_id' => $data['department_id'] ?? null,    // اختياري
        'name'          => $data['name'],
        'start_date'    => $data['start_date'],
        'end_date'      => $data['end_date'],
        'weeks_count'   => $data['weeks_count'] ?? 12,
        'status'        => $data['status'] ?? 'draft',
        'is_primary'    => (bool) ($data['is_primary'] ?? false),
        'notes'         => $data['notes'] ?? null,
    ]);

    return response()->json($set, 201);
}
}





















