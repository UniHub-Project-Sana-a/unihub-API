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
    public function getPendingEvaluations(Request $request)
    {
        $user = Auth::user();
        $student = Student::where('user_id', $user->user_id)->firstOrFail();
    
        // 1. جلب مجموعات الطالب
        $groupIds = DB::table('student_group_members')
            ->where('student_id', $student->student_id)
            ->pluck('group_id');
    
        // 2. جلب الجداول الدراسية المرتبطة بحملات نشطة
        $timetables = Timetable::query()
            ->whereIn('group_id', $groupIds)
            ->where('status', 1) // الجدول فعال
            ->whereHas('qaCampaigns', function($q) {
                 // الشرط: الحملة منشورة + تاريخ اليوم يقع ضمن فترتها
                 $q->where('is_published', true)
                   ->where('start_date', '<=', now()->format('Y-m-d'))
                   ->where('end_date', '>=', now()->format('Y-m-d'));
            })
            ->with(['course', 'lecturer.user', 'qaCampaigns' => function($q) {
                 // نجلب بيانات الحملة والنموذج
                 $q->where('is_published', true)
                   ->where('start_date', '<=', now()->format('Y-m-d'))
                   ->where('end_date', '>=', now()->format('Y-m-d'))
                   ->with('form');
            }])
            ->get();
    
        $evaluationsList = [];
    
        foreach ($timetables as $timetable) {
            // بما أن العلاقة أصبحت Many-to-Many، قد يكون هناك أكثر من حملة
            // سنقوم بالدوران عليهم جميعاً (في الغالب ستكون واحدة)
            foreach ($timetable->qaCampaigns as $campaign) {
                
                // التحقق من التقييم السابق
                $isSubmitted = QaSubmission::where('campaign_id', $campaign->campaign_id)
                    ->where('student_id', $student->student_id)
                    ->where('course_id', $timetable->course_id) // تأكدنا من المادة
                    ->where('lecturer_id', $timetable->lecturer_id) // تأكدنا من المحاضر
                    ->exists();
    
                if ($isSubmitted) continue;
    
                // --- حساب نسبة الحضور ---
                $totalSessions = DB::table('lecture_sessions')
                    ->where('timetable_id', $timetable->timetable_id)
                    ->where('status', 1)
                    ->count();
    
                $studentAttendanceCount = DB::table('student_attendance')
                    ->where('timetable_id', $timetable->timetable_id)
                    ->where('student_id', $student->student_id)
                    ->where('status', 1)
                    ->count();
    
                $attendancePercentage = ($totalSessions > 0) ? ($studentAttendanceCount / $totalSessions) * 100 : 100;
                $attendancePercentage = round($attendancePercentage, 1);
                
                // --- التحقق من الأهلية ---
                $isEligibleAttendance = $attendancePercentage >= $campaign->min_attendance_percentage;
                
                // إضافة البيانات للقائمة
                $evaluationsList[] = [
                    'timetable_id' => $timetable->timetable_id,
                    'campaign_id' => $campaign->campaign_id,
                    'course_id' => $timetable->course_id,
                    'lecturer_id' => $timetable->lecturer_id,
                    
                    'campaign_name' => $campaign->campaign_name,
                    'course_name' => $timetable->course->course_name,
                    'course_code' => $timetable->course->course_code,
                    'lecture_type' => $timetable->lecture_type == 1 ? 'عملي' : 'نظري',
                    'lecturer_name' => $timetable->lecturer->user->full_name ?? 'غير محدد',
                    
                    'student_attendance' => $attendancePercentage,
                    'required_attendance' => $campaign->min_attendance_percentage,
                    'start_date' => $campaign->start_date,
                    
                    'is_upcoming' => false, // بما أننا فلترنا بالتاريخ في الاستعلام، فهي جارية حكماً
                    'is_eligible_attendance' => $isEligibleAttendance,
                    'can_evaluate' => $isEligibleAttendance,
                    'rejection_reason' => $isEligibleAttendance ? null : "نسبة حضورك ($attendancePercentage%) أقل من الحد المطلوب ($campaign->min_attendance_percentage%)."
                ];
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