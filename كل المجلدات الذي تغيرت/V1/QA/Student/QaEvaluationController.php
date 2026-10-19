<?php

namespace App\Http\Controllers\Api\V1\QA\Student;

use App\Http\Controllers\Controller;
use App\Models\QA\QaCampaign;
use App\Models\QA\QaSubmission;
use App\Models\QA\QaAnswer;
use App\Models\Student;
use App\Models\Timetable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class QaEvaluationController extends Controller
{
    /**
     * جلب المواد التي يجب على الطالب تقييمها الآن
     */
    // App/Http/Controllers/Api/V1/QA/Student/QaEvaluationController.php

    public function getPendingEvaluations(Request $request)
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->user_id)->firstOrFail();
    
        $groupIds = DB::table('student_group_members')
            ->where('student_id', $student->student_id)
            ->pluck('group_id');
    
        // 1. جلب الجداول الدراسية المرتبطة بحملات نشطة
        $timetables = Timetable::query()
            ->whereIn('group_id', $groupIds)
            ->where('status', 1) 
            ->whereHas('qaCampaigns', function($q) {
                 $q->where('is_published', true)
                   ->where('end_date', '>=', now()->format('Y-m-d'));
            })
            ->with(['course', 'lecturer.user', 'qaCampaigns' => function($q) {
                 $q->where('is_published', true)
                   ->where('end_date', '>=', now()->format('Y-m-d'))
                   ->with('form');
            }])
            ->get();
    
        $evaluationsList = [];
    
        foreach ($timetables as $timetable) {
            foreach ($timetable->qaCampaigns as $campaign) {
                
                // 🔥🔥 التعديل الجذري: جمع كل المحاضرين المحتملين 🔥🔥
                
                // 1. المحاضر الأصلي (من الجدول مباشرة)
                $lecturerIds = [$timetable->lecturer_id];
    
                // 2. المحاضرين البدلاء (من جدول الجلسات - بغض النظر عن الحالة)
                $sessionLecturers = DB::table('lecture_sessions')
                    ->where('timetable_id', $timetable->timetable_id)
                    // ->where('status', 1) // ❌ حذفنا هذا الشرط لنكتشفهم حتى لو المحاضرة مجدولة
                    ->pluck('lecturer_id')
                    ->toArray();
    
                // 3. دمج المصفوفتين وإزالة التكرار
                $allLecturers = array_unique(array_merge($lecturerIds, $sessionLecturers));
    
                // الآن ننشئ بطاقة لكل محاضر وجدناه
                foreach ($allLecturers as $lecturerId) {
                    
                    // التحقق: هل قام الطالب بتقييم هذا المحاضر تحديداً؟
                    $isSubmitted = QaSubmission::where('campaign_id', $campaign->campaign_id)
                        ->where('student_id', $student->student_id)
                        ->where('course_id', $timetable->course_id)
                        ->where('lecturer_id', $lecturerId) 
                        ->exists();
    
                    if ($isSubmitted) continue;
    
                    // جلب اسم المحاضر (سواء كان الأصلي أو البديل)
                    $lecturerName = \App\Models\User::whereHas('lecturer', function($q) use ($lecturerId) {
                        $q->where('lecturer_id', $lecturerId);
                    })->value('full_name');
    
                    if (!$lecturerName) continue; // حماية في حال عدم وجود بيانات
    
                    // --- حساب نسبة الحضور (الخاصة بهذا المحاضر) ---
                    // ملاحظة: نحسب نسبة الحضور بناءً على الجلسات التي *نفذها هذا المحاضر فعلياً* (status=1)
                    
                    $totalSessions = DB::table('lecture_sessions')
                        ->where('timetable_id', $timetable->timetable_id)
                        ->where('lecturer_id', $lecturerId) // ✅ نحسب جلسات هذا المحاضر فقط
                        ->where('status', 1)
                        ->count();
    
                    // إذا لم يكن للمحاضر جلسات منفذة بعد (جديد أو بديل مستقبلي)، نعتبر النسبة 100%
                    // لكي لا يظهر "محروم" بسبب عدم وجود جلسات
                    if ($totalSessions == 0) {
                        $attendancePercentage = 100;
                    } else {
                        // نحسب كم مرة حضر الطالب في جلسات هذا المحاضر
                        // نحتاج لربط student_attendance بـ lecture_sessions للتأكد من المحاضر
                        $studentAttendanceCount = DB::table('student_attendance')
                            ->join('lecture_sessions', 'student_attendance.session_code', '=', 'lecture_sessions.session_code') // الربط عبر الكود أو ID
                            ->where('student_attendance.student_id', $student->student_id)
                            ->where('lecture_sessions.timetable_id', $timetable->timetable_id)
                            ->where('lecture_sessions.lecturer_id', $lecturerId) // ✅ جلسات هذا المحاضر
                            ->where('student_attendance.status', 1)
                            ->count();
    
                        $attendancePercentage = ($studentAttendanceCount / $totalSessions) * 100;
                    }
                    
                    $attendancePercentage = round($attendancePercentage, 1);
                    
                    // التحقق من الأهلية
                    $isEligibleAttendance = $attendancePercentage >= $campaign->min_attendance_percentage;
                    
                    // إضافة البطاقة
                    $evaluationsList[] = [
                        'timetable_id' => $timetable->timetable_id,
                        'campaign_id' => $campaign->campaign_id,
                        'course_id' => $timetable->course_id,
                        'lecturer_id' => $lecturerId, // ✅ ID المحاضر الحالي
                        
                        'campaign_name' => $campaign->campaign_name,
                        'course_name' => $timetable->course->course_name,
                        'course_code' => $timetable->course->course_code,
                        'lecture_type' => $timetable->lecture_type == 1 ? 'عملي' : 'نظري',
                        
                        'lecturer_name' => $lecturerName, // ✅ اسم المحاضر الحالي
                        
                        'student_attendance' => $attendancePercentage,
                        'required_attendance' => $campaign->min_attendance_percentage,
                        'start_date' => $campaign->start_date,
                        
                        'is_upcoming' => false,
                        'is_eligible_attendance' => $isEligibleAttendance,
                        'can_evaluate' => $isEligibleAttendance,
                        'rejection_reason' => $isEligibleAttendance ? null : "نسبة حضورك ($attendancePercentage%) مع هذا المحاضر أقل من الحد المطلوب."
                    ];
                }
            }
        }
    
        return response()->json($evaluationsList);
    }

    /**
     * جلب أسئلة النموذج للبدء في التقييم
     */
    public function getEvaluationForm($campaignId)
    {
        $campaign = QaCampaign::with(['form.domains.questions' => function($q) {
            $q->orderBy('sort_order');
        }])->findOrFail($campaignId);

        return response()->json($campaign->form);
    }

    /**
     * حفظ الإجابات
     */
    public function submitEvaluation(Request $request)
    {
        $request->validate([
            'campaign_id' => 'required|exists:qa_campaigns,campaign_id',
            'course_id' => 'required|exists:courses,course_id',
            'lecturer_id' => 'required|exists:lecturers,lecturer_id',
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:qa_questions,question_id',
            'answers.*.value' => 'required|in:1,2,3', // القيم المسموحة (لا أتفق، أتفق نوعاً ما، أتفق)
        ]);

        $user = Auth::user();
        $student = Student::where('user_id', $user->user_id)->firstOrFail();

        DB::transaction(function () use ($request, $student) {
            // 1. إنشاء سجل التسليم (Submission Header)
            $submission = QaSubmission::create([
                'campaign_id' => $request->campaign_id,
                'student_id' => $student->student_id,
                'lecturer_id' => $request->lecturer_id,
                'course_id' => $request->course_id,
                // 'submission_date_timestamp' => now(),
                'submission_date_timestamp' => now()->timestamp,
                'is_practical' => false, // يمكن تحديده ديناميكياً
            ]);

            // 2. حفظ الإجابات التفصيلية
            $answersData = [];
            foreach ($request->answers as $ans) {
                $answersData[] = [
                    'submission_id' => $submission->submission_id,
                    'question_id' => $ans['question_id'],
                    'rating_value' => $ans['value'],
                ];
            }
            
            // Insert Bulk للأداء العالي
            QaAnswer::insert($answersData);
        });

        return response()->json(['message' => 'تم حفظ التقييم بنجاح، شكراً لمشاركتك!']);
    }
}