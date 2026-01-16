<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CourseAssessment;
use App\Models\StudentGrade;
use App\Models\StudentGroup;
use App\Models\Timetable;

class LecturerGradebookController extends Controller
{
    /**
     * 1. جلب قائمة المواد الدراسية المسندة للمحاضر
     * يتم تجميعها حسب: المادة + المجموعة + الترم + السنة الدراسية
     */
    public function getMyCourses(Request $request)
    {
        $user = $request->user();
        
        // 1. التأكد من أن المستخدم محاضر
        if (!$user || !$user->lecturer) {
            return response()->json(['data' => []]);
        }

        $lecturerId = $user->lecturer->lecturer_id;

        try {
            // 2. الاستعلام (بدون deleted_at وبدون الاعتماد على timetable.semester_id)
            $courses = DB::table('timetable')
                ->join('courses', 'timetable.course_id', '=', 'courses.course_id')
                ->join('student_groups', 'timetable.group_id', '=', 'student_groups.group_id')
                ->join('semesters', 'student_groups.semester_id', '=', 'semesters.semester_id')
                ->where('timetable.lecturer_id', $lecturerId)
                // ❌ تم حذف السطر التالي لأنه قد يسبب خطأ إذا لم يكن الجدول يدعم الحذف المؤقت
                // ->whereNull('timetable.deleted_at') 
                ->select(
                    'timetable.course_id',
                    'courses.course_name',
                    'courses.course_code',
                    'timetable.group_id',
                    'student_groups.group_name',
                    'student_groups.semester_id', // ✅ نأخذه من المجموعة
                    'semesters.semester_name',
                    'timetable.academic_year',    // ✅ نأخذه من الجدول (حسب المايكريشن موجود)
                    'timetable.college_id'
                )
                ->distinct()
                ->get();

            return response()->json(['data' => $courses]);

        } catch (\Exception $e) {
            // هذا السطر سيظهر لك الخطأ الحقيقي في الـ Network Tab بدلاً من الخطأ العام
            return response()->json([
                'message' => 'حدث خطأ في قاعدة البيانات',
                'error' => $e->getMessage() // ✅ سيعرض لك تفاصيل العمود المفقود
            ], 500);
        }
    }

    /**
     * 2. جلب سجل الدرجات والحضور (الشبكة الكاملة)
     * يعيد: أعمدة التقييم + صفوف الطلاب (مع درجاتهم وحضورهم)
     */
    public function getGradebookData(Request $request)
    {
        // التحقق من المدخلات الأساسية
        $request->validate([
            'course_id' => 'required|integer',
            'group_id' => 'required|integer',
            'semester_id' => 'required|integer',
            'academic_year' => 'required|string',
        ]);

        $courseId = $request->course_id;
        $groupId = $request->group_id;
        $semesterId = $request->semester_id;
        $academicYear = $request->academic_year;

        // أ) جلب أعمدة التقييم (Assessments) - هنا semester_id موجود في جدول التقييمات، فلا بأس
        $assessments = CourseAssessment::where([
                ['course_id', '=', $courseId],
                ['group_id', '=', $groupId],
                ['semester_id', '=', $semesterId],
                ['academic_year', '=', $academicYear],
            ])
            ->select('assessment_id', 'name', 'max_score', 'weight')
            ->get();

        // ب) جلب الطلاب المسجلين في هذه المجموعة
        $students = DB::table('student_group_members')
            ->join('students', 'student_group_members.student_id', '=', 'students.student_id')
            ->join('users', 'students.user_id', '=', 'users.user_id')
            ->where('student_group_members.group_id', $groupId)
            ->where('students.status', 1) 
            ->select(
                'students.student_id',
                'users.full_name',
                'users.academic_number',
                'students.status'
            )
            ->orderBy('users.full_name')
            ->get();

        // ج) جلب جميع الدرجات المرصودة
        $assessmentIds = $assessments->pluck('assessment_id')->toArray();
        $grades = [];
        if (!empty($assessmentIds)) {
            $grades = DB::table('student_grades')
                ->whereIn('assessment_id', $assessmentIds)
                ->select('student_id', 'assessment_id', 'score')
                ->get();
        }

        // د) حساب إحصائيات الحضور
        // 1. تحديد حصص الجدول (Timetable Slots)
        // ✅ التعديل هنا: حذفنا where('semester_id') لأن العمود غير موجود في timetable
        $timetableIds = DB::table('timetable')
            ->where('course_id', $courseId)
            ->where('group_id', $groupId)
            ->where('academic_year', $academicYear)
            ->pluck('timetable_id')
            ->toArray();

        // 2. حساب إجمالي الجلسات التي تم عقدها
        $totalSessionsHeld = 0;
        $studentAttendanceCounts = [];

        if (!empty($timetableIds)) {
            $totalSessionsHeld = DB::table('lecture_sessions')
                ->whereIn('timetable_id', $timetableIds)
                ->where('status', '!=', 0) 
                ->count();

            // 3. حساب عدد مرات حضور كل طالب
            $attendanceRecords = DB::table('student_attendance')
                ->whereIn('timetable_id', $timetableIds)
                ->where('status', 1)
                ->select('student_id', DB::raw('count(*) as attended_count'))
                ->groupBy('student_id')
                ->get();

            // تحويل النتائج إلى مصفوفة لسهولة الوصول إليها: [student_id => count]
            foreach ($attendanceRecords as $record) {
                $studentAttendanceCounts[$record->student_id] = $record->attended_count;
            }
        }

        // هـ) دمج البيانات لبناء الصفوف
        // تحويل مجموعة الدرجات إلى Collection لسهولة البحث
        $gradesCollection = collect($grades);

        $studentRows = $students->map(function ($student) use ($assessments, $gradesCollection, $studentAttendanceCounts, $totalSessionsHeld) {
            
            // 1. تجميع الدرجات
            $myGrades = [];
            foreach ($assessments as $col) {
                $gradeRecord = $gradesCollection
                                      ->where('student_id', $student->student_id)
                                      ->where('assessment_id', $col->assessment_id)
                                      ->first();
                $myGrades[$col->assessment_id] = $gradeRecord ? $gradeRecord->score : null;
            }

            // 2. حساب الحضور
            $attended = $studentAttendanceCounts[$student->student_id] ?? 0;
            $percentage = $totalSessionsHeld > 0 
                ? round(($attended / $totalSessionsHeld) * 100, 1) 
                : 0;

            return [
                'student_id' => $student->student_id,
                'academic_number' => $student->academic_number,
                'full_name' => $student->full_name,
                'status' => $student->status,
                'grades' => $myGrades,
                'attendance' => [
                    'attended' => $attended,
                    'total_sessions' => $totalSessionsHeld,
                    'percentage' => $percentage
                ]
            ];
        });

        return response()->json([
            'meta' => [
                'total_sessions' => $totalSessionsHeld,
                'students_count' => $students->count(),
                'academic_year' => $academicYear
            ],
            'columns' => $assessments,
            'students' => $studentRows
        ]);
    }

    /**
     * 3. إضافة عمود تقييم جديد (مثلاً: اختبار شهري)
     */
    public function addAssessmentColumn(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'max_score' => 'required|numeric|min:1',
            'course_id' => 'required|integer',
            'group_id' => 'required|integer',
            'semester_id' => 'required|integer',
            'academic_year' => 'required|string', // ✅
        ]);

        $lecturerId = $request->user()->lecturer->lecturer_id;
        $collegeId = $request->user()->lecturer->college_id;

        $assessment = CourseAssessment::create([
            'name' => $request->name,
            'max_score' => $request->max_score,
            'college_id' => $collegeId,
            'course_id' => $request->course_id,
            'group_id' => $request->group_id,
            'semester_id' => $request->semester_id,
            'academic_year' => $request->academic_year, // ✅ حفظ السنة
            'created_by' => $lecturerId,
        ]);

        return response()->json([
            'message' => 'تم إضافة بند التقييم بنجاح',
            'data' => $assessment
        ]);
    }

    /**
     * 4. تحديث أو رصد درجة طالب في خلية معينة
     */
    public function updateStudentGrade(Request $request)
    {
        $request->validate([
            'assessment_id' => 'required|exists:course_assessments,assessment_id',
            'student_id' => 'required|exists:students,student_id',
            'score' => 'nullable|numeric|min:0', // nullable للسماح بحذف الدرجة
        ]);

        // التحقق من أن الدرجة لا تتجاوز الحد الأقصى
        if (!is_null($request->score)) {
            $assessment = CourseAssessment::find($request->assessment_id);
            if ($request->score > $assessment->max_score) {
                return response()->json([
                    'message' => "الدرجة المدخلة أكبر من الدرجة العظمى ($assessment->max_score)"
                ], 422);
            }
        }

        if (is_null($request->score)) {
            // إذا كانت القيمة فارغة، نحذف السجل (تصفير الدرجة)
            StudentGrade::where('assessment_id', $request->assessment_id)
                ->where('student_id', $request->student_id)
                ->delete();
        } else {
            // تحديث أو إنشاء
            StudentGrade::updateOrCreate(
                [
                    'assessment_id' => $request->assessment_id,
                    'student_id' => $request->student_id
                ],
                [
                    'score' => $request->score
                ]
            );
        }

        return response()->json(['message' => 'تم حفظ الدرجة']);
    }

    /**
     * 5. حذف عمود تقييم (اختياري - في حال الخطأ)
     */
    public function deleteAssessmentColumn(Request $request, $id)
    {
        $lecturerId = $request->user()->lecturer->lecturer_id;
        
        $assessment = CourseAssessment::where('assessment_id', $id)
            ->where('created_by', $lecturerId) // أمان: المحاضر يحذف ما أنشأه فقط
            ->firstOrFail();

        $assessment->delete();

        return response()->json(['message' => 'تم حذف بند التقييم والدرجات المرتبطة به']);
    }
}