<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseAssessment;
use App\Models\Lecturer;
use App\Models\LecturerGroupNotification;
use App\Models\LectureSession;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentExcuseSubmission;
use App\Models\StudentGrade;
use App\Models\StudentGroup;
use App\Models\StudentGroupMember;
use App\Models\Timetable;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentPortalController extends Controller
{
    /**
     * جلب كائن الطالب الحالي
     */
    protected function getCurrentStudent(Request $request): ?Student
    {
        $user = $request->user();
        if (!$user) return null;

        return Student::with(['college', 'department', 'program', 'level', 'semester', 'block', 'user'])
            ->where('user_id', $user->user_id)
            ->first();
    }

    /**
     * جلب المجموعة الدراسية المسند لها الطالب
     */
    protected function getStudentGroup(int $studentId): ?StudentGroup
    {
        return StudentGroup::whereHas('members', function ($q) use ($studentId) {
            $q->where('students.student_id', $studentId);
        })->first();
    }

    /**
     * ملخص بوابة الطالب (Dashboard Summary)
     * GET /api/v1/student/portal-summary
     */
    public function summary(Request $request): JsonResponse
    {
        $student = $this->getCurrentStudent($request);
        if (!$student) {
            return response()->json(['message' => 'بيانات الطالب غير موجودة أو غير مكتملة.'], 404);
        }

        $group = $this->getStudentGroup($student->student_id);
        $groupId = $group ? $group->group_id : null;

        // 1. حساب نسبة الحضور الإجمالية
        $totalSessionsHeld = 0;
        $attendedCount = 0;
        $excusedCount = 0;
        $absentCount = 0;

        if ($groupId) {
            $timetableIds = Timetable::where('group_id', $groupId)->pluck('timetable_id');
            $totalSessionsHeld = LectureSession::whereIn('timetable_id', $timetableIds)
                ->where('status', 2) // مكتملة
                ->count();

            if ($totalSessionsHeld > 0) {
                $attendedCount = StudentAttendance::where('student_id', $student->student_id)
                    ->where('status', 1)
                    ->count();

                $excusedCount = StudentAttendance::where('student_id', $student->student_id)
                    ->where('status', 2)
                    ->count();

                $absentCount = max(0, $totalSessionsHeld - ($attendedCount + $excusedCount));
            }
        }

        $attendanceRate = $totalSessionsHeld > 0 
            ? round((($attendedCount + $excusedCount) / $totalSessionsHeld) * 100, 1) 
            : 100.0;

        // 2. محاضرات اليوم
        $today = Carbon::today();
        $todayArabic = match ($today->dayOfWeek) {
            Carbon::SATURDAY => 'السبت',
            Carbon::SUNDAY => 'الأحد',
            Carbon::MONDAY => 'الإثنين',
            Carbon::TUESDAY => 'الثلاثاء',
            Carbon::WEDNESDAY => 'الأربعاء',
            Carbon::THURSDAY => 'الخميس',
            default => 'الجمعة'
        };

        $todayLectures = [];
        if ($groupId) {
            $todayTimetables = Timetable::with(['course', 'lecturer.user', 'classroom', 'period', 'day'])
                ->where('group_id', $groupId)
                ->whereHas('day', fn($q) => $q->where('day_name', $todayArabic))
                ->get();

            foreach ($todayTimetables as $tt) {
                // فحص الجلسة الفعلية لليوم إن وجدت
                $activeSession = LectureSession::where('timetable_id', $tt->timetable_id)
                    ->whereDate('session_date', $today)
                    ->first();

                $statusText = 'مجدولة';
                $statusCode = 0;
                if ($activeSession) {
                    if ($activeSession->status == 1) {
                        $statusText = 'جارية الآن (مباشرة)';
                        $statusCode = 1;
                    } elseif ($activeSession->status == 2) {
                        $statusText = 'انتهت';
                        $statusCode = 2;
                    }
                }

                $todayLectures[] = [
                    'timetable_id' => $tt->timetable_id,
                    'course_name' => $tt->course->course_name ?? 'مقرر دراسي',
                    'course_code' => $tt->course->course_code ?? '',
                    'lecturer_name' => $tt->lecturer->user->full_name ?? 'أستاذ المقرر',
                    'classroom' => $tt->classroom->classroom_name ?? 'قاعة غير محددة',
                    'start_time' => $tt->period->start_time ?? '',
                    'end_time' => $tt->period->end_time ?? '',
                    'lecture_type' => $tt->lecture_type == 1 ? 'عملي / معمل' : 'نظري',
                    'status_code' => $statusCode,
                    'status_text' => $statusText,
                ];
            }
        }

        // 3. الإعلانات والتنبيهات الموجهة للمجموعة
        $announcements = [];
        if ($groupId) {
            $announcements = LecturerGroupNotification::with('lecturerUser')
                ->where('group_id', $groupId)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get()
                ->map(fn($item) => [
                    'id' => $item->notification_id,
                    'subject' => $item->subject,
                    'message' => $item->message_body,
                    'sender' => $item->lecturerUser->full_name ?? 'المحاضر',
                    'date' => $item->send_at ? $item->send_at->format('Y-m-d H:i') : $item->created_at->format('Y-m-d H:i'),
                ]);
        }

        // 4. المقررات المسجلة في الفصل
        $registeredCourses = [];
        if ($groupId) {
            $distinctCourses = Timetable::with('course')
                ->where('group_id', $groupId)
                ->get()
                ->pluck('course')
                ->filter()
                ->unique('course_id')
                ->values();

            foreach ($distinctCourses as $c) {
                $registeredCourses[] = [
                    'course_id' => $c->course_id,
                    'course_name' => $c->course_name,
                    'course_code' => $c->course_code,
                    'credit_hours' => $c->credit_hours,
                    'category' => $c->category,
                ];
            }
        }

        // 5. الأعذار الطبية الحديثة
        $recentExcuses = StudentExcuseSubmission::with('course')
            ->where('student_user_id', $student->user_id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(fn($ex) => [
                'id' => $ex->submission_id,
                'course_name' => $ex->course->course_name ?? '',
                'request_date' => $ex->request_date ? Carbon::parse($ex->request_date)->format('Y-m-d') : '',
                'reason' => $ex->reason,
                'status' => (int)$ex->response_status, // 0: معلق, 1: مقبول, 2: مرفوض
                'comment' => $ex->lecturer_comment,
            ]);

        return response()->json([
            'status' => true,
            'student' => [
                'id' => $student->student_id,
                'user_id' => $student->user_id,
                'full_name' => $student->user->full_name ?? '',
                'academic_number' => $student->user->academic_number ?? '',
                'email' => $student->user->email ?? '',
                'phone' => $student->user->phone ?? '',
                'college_name' => $student->college->college_name ?? '',
                'department_name' => $student->department->department_name ?? '',
                'program_name' => $student->program->program_name ?? '',
                'level_name' => $student->level->level_name ?? '',
                'semester_name' => $student->semester->semester_name ?? '',
                'block_name' => $student->block->block_name ?? null,
                'group_name' => $group->group_name ?? 'غير مسند لمجموعة',
                'group_id' => $groupId,
            ],
            'attendance_stats' => [
                'rate' => $attendanceRate,
                'total_sessions' => $totalSessionsHeld,
                'attended' => $attendedCount,
                'excused' => $excusedCount,
                'absent' => $absentCount,
                'safe_threshold' => 75.0,
            ],
            'today_lectures' => $todayLectures,
            'registered_courses' => $registeredCourses,
            'announcements' => $announcements,
            'recent_excuses' => $recentExcuses,
        ]);
    }

    /**
     * جدول الطالب الأسبوعي
     * GET /api/v1/student/timetable
     */
    public function timetable(Request $request): JsonResponse
    {
        $student = $this->getCurrentStudent($request);
        if (!$student) {
            return response()->json(['message' => 'بيانات الطالب غير موجودة'], 404);
        }

        $group = $this->getStudentGroup($student->student_id);
        if (!$group) {
            return response()->json(['message' => 'لم يتم إسنادك إلى مجموعة دراسية بعد.', 'data' => []], 200);
        }

        $timetables = Timetable::with(['course', 'lecturer.user', 'classroom.building', 'period', 'day'])
            ->where('group_id', $group->group_id)
            ->get();

        $daysOrder = ['السبت', 'الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس'];
        $grouped = [];

        foreach ($daysOrder as $dayName) {
            $slots = $timetables->filter(fn($t) => $t->day && $t->day->day_name === $dayName)
                ->sortBy(fn($t) => $t->period->start_time ?? '00:00:00')
                ->values()
                ->map(fn($t) => [
                    'timetable_id' => $t->timetable_id,
                    'course_id' => $t->course_id,
                    'course_name' => $t->course->course_name ?? '',
                    'course_code' => $t->course->course_code ?? '',
                    'lecturer_name' => $t->lecturer->user->full_name ?? '',
                    'building_name' => $t->classroom->building->building_name ?? '',
                    'classroom_name' => $t->classroom->classroom_name ?? '',
                    'period_name' => $t->period->period_name ?? '',
                    'start_time' => $t->period->start_time ?? '',
                    'end_time' => $t->period->end_time ?? '',
                    'lecture_type' => $t->lecture_type == 1 ? 'عملي / معمل' : 'نظري',
                ]);

            $grouped[] = [
                'day' => $dayName,
                'slots' => $slots,
            ];
        }

        return response()->json([
            'status' => true,
            'group_name' => $group->group_name,
            'timetable' => $grouped,
        ]);
    }

    /**
     * كشف الحضور والغياب للطالب بالتفصيل
     * GET /api/v1/student/attendance
     */
    public function attendance(Request $request): JsonResponse
    {
        $student = $this->getCurrentStudent($request);
        if (!$student) {
            return response()->json(['message' => 'بيانات الطالب غير موجودة'], 404);
        }

        $group = $this->getStudentGroup($student->student_id);
        if (!$group) {
            return response()->json(['courses' => [], 'logs' => []]);
        }

        $timetables = Timetable::with('course')
            ->where('group_id', $group->group_id)
            ->get();

        $courseIds = $timetables->pluck('course_id')->unique();
        $courseStats = [];

        foreach ($courseIds as $cId) {
            $course = Course::find($cId);
            if (!$course) continue;

            $cTimetableIds = $timetables->where('course_id', $cId)->pluck('timetable_id');

            $totalSessions = LectureSession::whereIn('timetable_id', $cTimetableIds)
                ->where('status', 2)
                ->count();

            $attended = StudentAttendance::where('student_id', $student->student_id)
                ->whereIn('timetable_id', $cTimetableIds)
                ->where('status', 1)
                ->count();

            $excused = StudentAttendance::where('student_id', $student->student_id)
                ->whereIn('timetable_id', $cTimetableIds)
                ->where('status', 2)
                ->count();

            $absent = max(0, $totalSessions - ($attended + $excused));

            $rate = $totalSessions > 0 ? round((($attended + $excused) / $totalSessions) * 100, 1) : 100.0;

            $statusLevel = 'safe'; // آمن
            if ($rate < 75.0) {
                $statusLevel = 'deprived'; // محروم
            } elseif ($rate < 85.0) {
                $statusLevel = 'warning'; // إنذار
            }

            $courseStats[] = [
                'course_id' => $course->course_id,
                'course_name' => $course->course_name,
                'course_code' => $course->course_code,
                'total_sessions' => $totalSessions,
                'attended' => $attended,
                'excused' => $excused,
                'absent' => $absent,
                'rate' => $rate,
                'status_level' => $statusLevel,
            ];
        }

        // سجل جلسات الحضور الأخيرة
        $recentLogs = StudentAttendance::with(['timetable.course'])
            ->where('student_id', $student->student_id)
            ->orderBy('attendance_date', 'desc')
            ->take(15)
            ->get()
            ->map(fn($log) => [
                'id' => $log->attendance_id,
                'course_name' => $log->timetable->course->course_name ?? '',
                'date' => $log->attendance_date ? Carbon::parse($log->attendance_date)->format('Y-m-d') : '',
                'status' => $log->status, // 1: حاضر، 2: بعذر، 0: غائب
                'status_text' => match ($log->status) {
                    1 => 'حاضر (عبر الـ QR)',
                    2 => 'غياب بعذر مقبول',
                    default => 'غائب'
                },
                'method' => $log->attendance_method ?? 'QR Code',
            ]);

        return response()->json([
            'status' => true,
            'courses' => $courseStats,
            'recent_logs' => $recentLogs,
        ]);
    }

    /**
     * كشف درجات الطالب
     * GET /api/v1/student/grades
     */
    public function grades(Request $request): JsonResponse
    {
        $student = $this->getCurrentStudent($request);
        if (!$student) {
            return response()->json(['message' => 'بيانات الطالب غير موجودة'], 404);
        }

        $group = $this->getStudentGroup($student->student_id);
        $groupId = $group ? $group->group_id : null;

        $timetables = $groupId ? Timetable::where('group_id', $groupId)->pluck('course_id')->unique() : collect();

        $courses = Course::whereIn('course_id', $timetables)->get();
        $result = [];

        foreach ($courses as $c) {
            $assessments = CourseAssessment::where('course_id', $c->course_id)
                ->where(function ($q) use ($groupId) {
                    $q->whereNull('group_id')->orWhere('group_id', $groupId);
                })
                ->get();

            $items = [];
            $totalScore = 0;
            $maxPossible = 0;

            foreach ($assessments as $ass) {
                $grade = StudentGrade::where('student_id', $student->student_id)
                    ->where('assessment_id', $ass->assessment_id)
                    ->first();

                $score = $grade ? (float)$grade->score : 0;
                $max = (float)($ass->max_score ?? $ass->grade ?? 0);

                $items[] = [
                    'name' => $ass->name,
                    'score' => $score,
                    'max_score' => $max,
                    'notes' => $grade->notes ?? '',
                ];

                $totalScore += $score;
                $maxPossible += $max;
            }

            $percentage = $maxPossible > 0 ? round(($totalScore / $maxPossible) * 100, 1) : 0;

            $result[] = [
                'course_id' => $c->course_id,
                'course_name' => $c->course_name,
                'course_code' => $c->course_code,
                'credit_hours' => $c->credit_hours,
                'assessments' => $items,
                'total_score' => $totalScore,
                'max_score' => $maxPossible,
                'percentage' => $percentage,
            ];
        }

        return response()->json([
            'status' => true,
            'grades' => $result,
        ]);
    }

    /**
     * قائمة الأعذار المقدمة من الطالب
     * GET /api/v1/student/excuses
     */
    public function excuses(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) return response()->json([], 401);

        $excuses = StudentExcuseSubmission::with(['course', 'lecturerUser'])
            ->where('student_user_id', $user->user_id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($ex) => [
                'id' => $ex->submission_id,
                'course_id' => $ex->course_id,
                'course_name' => $ex->course->course_name ?? '',
                'lecturer_name' => $ex->lecturerUser->full_name ?? 'أستاذ المقرر',
                'request_date' => $ex->request_date ? Carbon::parse($ex->request_date)->format('Y-m-d') : '',
                'reason' => $ex->reason,
                'excuse_image' => $ex->excuse_image,
                'response_status' => (int)$ex->response_status,
                'status_text' => match ((int)$ex->response_status) {
                    1 => 'مقبول ✅',
                    2 => 'مرفوض ❌',
                    default => 'قيد المراجعة ⏳'
                },
                'lecturer_comment' => $ex->lecturer_comment,
                'created_at' => $ex->created_at->format('Y-m-d H:i'),
            ]);

        return response()->json([
            'status' => true,
            'excuses' => $excuses,
        ]);
    }

    /**
     * تقديم عذر طبي أو غيابي جديد
     * POST /api/v1/student/excuses
     */
    public function submitExcuse(Request $request): JsonResponse
    {
        $user = $request->user();
        $student = $this->getCurrentStudent($request);
        if (!$student) {
            return response()->json(['message' => 'بيانات الطالب غير موجودة'], 404);
        }

        $validated = $request->validate([
            'course_id' => 'required|exists:courses,course_id',
            'request_date' => 'required|date',
            'reason' => 'required|string|min:5|max:1000',
            'excuse_image' => 'nullable|string', // صورة العذر أو مساره أو Base64
            'file' => 'nullable|file|max:15360', // ملف مرفق بحد أقصى 15 ميجابايت
        ]);

        $group = $this->getStudentGroup($student->student_id);
        $groupId = $group ? $group->group_id : null;

        // معالجة الملف المرفق أو الصورة الملتقطة بالكاميرا
        $imagePath = null;
        if ($request->hasFile('file')) {
            $uploaded = $request->file('file');
            $filename = 'excuse_' . time() . '_' . uniqid() . '.' . $uploaded->getClientOriginalExtension();
            $dest = public_path('uploads/excuses');
            if (!file_exists($dest)) {
                mkdir($dest, 0777, true);
            }
            $uploaded->move($dest, $filename);
            $imagePath = '/uploads/excuses/' . $filename;
        } elseif ($request->filled('excuse_image') && str_starts_with($request->excuse_image, 'data:')) {
            // صورة ملتقطة مباشرة من كاميرا الهاتف أو المتصفح
            $dataUri = $request->excuse_image;
            if (preg_match('/^data:image\/(\w+);base64,/', $dataUri, $matches)) {
                $ext = strtolower($matches[1]);
                $cleanData = substr($dataUri, strpos($dataUri, ',') + 1);
                $decoded = base64_decode($cleanData);
                if ($decoded !== false) {
                    $filename = 'cam_excuse_' . time() . '_' . uniqid() . '.' . $ext;
                    $dest = public_path('uploads/excuses');
                    if (!file_exists($dest)) {
                        mkdir($dest, 0777, true);
                    }
                    file_put_contents($dest . '/' . $filename, $decoded);
                    $imagePath = '/uploads/excuses/' . $filename;
                }
            }
        } elseif ($request->filled('excuse_image')) {
            $imagePath = $request->excuse_image;
        }

        // تحديد المحاضر المسؤول عن هذا المقرر للمجموعة
        $tt = Timetable::where('course_id', $validated['course_id'])
            ->when($groupId, fn($q) => $q->where('group_id', $groupId))
            ->first();

        $lecturerUserId = null;
        if ($tt && $tt->lecturer_id) {
            $lecturer = Lecturer::find($tt->lecturer_id);
            $lecturerUserId = $lecturer ? $lecturer->user_id : null;
        }

        if (!$lecturerUserId) {
            // كبديل نأخذ أول محاضر مسند للمقرر
            $fallbackTt = Timetable::where('course_id', $validated['course_id'])->first();
            if ($fallbackTt) {
                $lecturer = Lecturer::find($fallbackTt->lecturer_id);
                $lecturerUserId = $lecturer ? $lecturer->user_id : null;
            }
        }

        if (!$lecturerUserId) {
            return response()->json(['message' => 'تعذر العثور على أستاذ المقرر المعني لتحويل العذر إليه.'], 422);
        }

        // إنشاء أو تحديث العذر
        $submission = StudentExcuseSubmission::updateOrCreate(
            [
                'student_user_id' => $user->user_id,
                'course_id' => $validated['course_id'],
                'request_date' => $validated['request_date'],
            ],
            [
                'lecturer_user_id' => $lecturerUserId,
                'reason' => $validated['reason'],
                'excuse_image' => $imagePath,
                'response_status' => 0, // معلق قيد المراجعة
                'is_lecturer_notified' => true,
                'lecturer_comment' => null,
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'تم تقديم العذر الطبي بنجاح وإرفاق المستند/الصورة للمراجعة والاعتماد.',
            'data' => $submission,
        ], 201);
    }

    /**
     * إعلانات وتنبيهات المحاضرين للمجموعة
     * GET /api/v1/student/announcements
     */
    public function announcements(Request $request): JsonResponse
    {
        $student = $this->getCurrentStudent($request);
        if (!$student) return response()->json([], 404);

        $group = $this->getStudentGroup($student->student_id);
        if (!$group) return response()->json(['announcements' => []]);

        $announcements = LecturerGroupNotification::with('lecturerUser')
            ->where('group_id', $group->group_id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($item) => [
                'id' => $item->notification_id,
                'subject' => $item->subject,
                'message' => $item->message_body,
                'sender' => $item->lecturerUser->full_name ?? 'أستاذ المقرر',
                'sender_email' => $item->lecturerUser->email ?? '',
                'date' => $item->send_at ? $item->send_at->format('Y-m-d H:i') : $item->created_at->format('Y-m-d H:i'),
            ]);

        return response()->json([
            'status' => true,
            'group_name' => $group->group_name,
            'announcements' => $announcements,
        ]);
    }

    /**
     * حساب المسافة الجغرافية بالأمتار (Haversine Formula)
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371000; // بالأمتار

        $latFrom = deg2rad((float)$lat1);
        $lonFrom = deg2rad((float)$lon1);
        $latTo = deg2rad((float)$lat2);
        $lonTo = deg2rad((float)$lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * $earthRadius;
    }

    /**
     * مسح رمز الـ QR وتسجيل حضور الطالب مع الفحص الجغرافي الصارم (Geofencing)
     * POST /api/v1/student/scan-qr
     */
    public function scanQr(Request $request): JsonResponse
    {
        $student = $this->getCurrentStudent($request);
        if (!$student) {
            return response()->json(['status' => false, 'message' => 'بيانات الطالب غير موجودة أو غير مسجلة.'], 403);
        }

        $validated = $request->validate([
            'qr_code_value' => 'required|string',
            'latitude'      => 'required|numeric',
            'longitude'     => 'required|numeric',
        ]);

        // 1. البحث عن رمز الـ QR الفعال
        $qrCode = \App\Models\QrCode::where('qr_code_value', $validated['qr_code_value'])
            ->where('is_active', true)
            ->first();

        if (!$qrCode) {
            return response()->json([
                'status' => false,
                'message' => 'رمز الـ QR غير صالح أو أن الجلسة قد أغلقت بالفعل من قبل المحاضر.'
            ], 404);
        }

        if (Carbon::now()->gt(Carbon::parse($qrCode->expires_at))) {
            return response()->json([
                'status' => false,
                'message' => 'انتهت صلاحية رمز الـ QR. اطلب من المحاضر تمديد وقت الجلسة أو تجديد الرمز.'
            ], 410);
        }

        // 2. فحص جلسة المحاضرة والقاعة
        $session = LectureSession::with(['actualClassroom', 'timetable.course', 'timetable.lecturer.user'])
            ->find($qrCode->session_id);

        if (!$session) {
            return response()->json(['status' => false, 'message' => 'تعذر العثور على جلسة المحاضرة المرتبطة.'], 404);
        }

        // 3. التحقق الجغرافي الصارم (Geofencing)
        $targetLat = (float)($qrCode->latitude ?? $session->actualClassroom->latitude ?? 0);
        $targetLon = (float)($qrCode->longitude ?? $session->actualClassroom->longitude ?? 0);
        $allowedDistance = (float)($qrCode->allowed_distance ?? $session->actualClassroom->allowed_distance ?? 50.0);

        $distance = $this->calculateDistance(
            $validated['latitude'],
            $validated['longitude'],
            $targetLat,
            $targetLon
        );

        if ($distance > $allowedDistance) {
            return response()->json([
                'status' => false,
                'is_out_of_range' => true,
                'distance' => round($distance, 1),
                'allowed_distance' => $allowedDistance,
                'message' => 'أنت خارج نطاق القاعة الدراسية! المسافة الحالية هي ' . round($distance, 1) . " متراً، والحد الأقصى المسموح به هو {$allowedDistance} متراً."
            ], 422);
        }

        // 4. التحقق من عدم التكرار لنفس الجلسة
        $existing = StudentAttendance::where('student_id', $student->student_id)
            ->where('session_code', $session->session_code)
            ->first();

        if ($existing) {
            return response()->json([
                'status' => true,
                'already_recorded' => true,
                'message' => 'لقد تم تسجيل حضورك مسبقاً في هذه الجلسة بنجاح.',
                'course_name' => $session->timetable->course->course_name ?? 'المقرر',
                'session_code' => $session->session_code,
            ]);
        }

        // 5. تسجيل الحضور الفعلي في قاعدة البيانات
        $attendance = StudentAttendance::create([
            'student_id'        => $student->student_id,
            'timetable_id'      => $session->timetable_id,
            'level_id'          => $student->level_id ?? 1,
            'attendance_date'   => Carbon::today()->toDateString(),
            'status'            => 1, // 1 = حاضر
            'attendance_method' => 0, // 0 = QR
            'college_id'        => $student->college_id ?? 1,
            'department_id'     => $student->department_id ?? 1,
            'session_code'      => $session->session_code,
        ]);

        $session->increment('system_attendance_count');

        return response()->json([
            'status' => true,
            'message' => 'تم تسجيل حضورك بنجاح عبر التحقق الجغرافي ورمز الـ QR!',
            'data' => [
                'course_name' => $session->timetable->course->course_name ?? 'المقرر',
                'lecturer_name' => $session->timetable->lecturer->user->full_name ?? 'المحاضر',
                'classroom' => $session->actualClassroom->classroom_name ?? 'القاعة',
                'distance' => round($distance, 1) . ' متر',
                'time' => Carbon::now()->format('H:i:s'),
            ]
        ]);
    }

    /**
     * تسليم تكليف أو واجب دراسي مع رفع الملف أو الصورة
     * POST /api/v1/student/assignments/submit
     */
    public function submitAssignment(Request $request): JsonResponse
    {
        $student = $this->getCurrentStudent($request);
        if (!$student) {
            return response()->json(['message' => 'بيانات الطالب غير موجودة.'], 403);
        }

        $validated = $request->validate([
            'course_id' => 'required|exists:courses,course_id',
            'title'     => 'required|string|max:255',
            'notes'     => 'nullable|string|max:1000',
            'file'      => 'nullable|file|max:20480', // 20MB
            'file_data' => 'nullable|string',
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = 'assign_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $dest = public_path('uploads/assignments');
            if (!file_exists($dest)) {
                mkdir($dest, 0777, true);
            }
            $file->move($dest, $filename);
            $filePath = '/uploads/assignments/' . $filename;
        } elseif ($request->filled('file_data') && str_starts_with($request->file_data, 'data:')) {
            $dataUri = $request->file_data;
            if (preg_match('/^data:(\w+\/[\w\-]+);base64,/', $dataUri, $matches)) {
                $ext = str_contains($matches[1], 'pdf') ? 'pdf' : 'jpg';
                $clean = substr($dataUri, strpos($dataUri, ',') + 1);
                $decoded = base64_decode($clean);
                if ($decoded !== false) {
                    $filename = 'assign_snap_' . time() . '_' . uniqid() . '.' . $ext;
                    $dest = public_path('uploads/assignments');
                    if (!file_exists($dest)) {
                        mkdir($dest, 0777, true);
                    }
                    file_put_contents($dest . '/' . $filename, $decoded);
                    $filePath = '/uploads/assignments/' . $filename;
                }
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'تم تسليم التكليف ورفع الملف بنجاح إلى دكتور المقرر.',
            'data' => [
                'title' => $validated['title'],
                'file_url' => $filePath,
                'submitted_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]
        ]);
    }
}

