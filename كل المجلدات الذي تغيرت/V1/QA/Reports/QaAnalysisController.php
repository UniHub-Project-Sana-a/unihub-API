<?php

namespace App\Http\Controllers\Api\V1\QA\Reports;

use App\Http\Controllers\Controller;
use App\Models\QA\QaSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QaAnalysisController extends Controller
{
    /**
     * جديد: جلب الجداول الدراسية المرتبطة بحملة معينة (للقائمة المنسدلة)
     */
    public function getCampaignTimetables(Request $request)
    {
        $request->validate(['campaign_id' => 'required|integer']);

        $timetables = DB::table('qa_campaign_assignments')
            ->join('timetable', 'qa_campaign_assignments.timetable_id', '=', 'timetable.timetable_id')
            ->join('courses', 'timetable.course_id', '=', 'courses.course_id')
            ->join('lecturers', 'timetable.lecturer_id', '=', 'lecturers.lecturer_id')
            ->join('users', 'lecturers.user_id', '=', 'users.user_id')
            ->leftJoin('student_groups', 'timetable.group_id', '=', 'student_groups.group_id')
            ->where('qa_campaign_assignments.campaign_id', $request->campaign_id)
            ->select(
                'timetable.timetable_id',
                'timetable.lecture_type', // 0 نظري 1 عملي
                'courses.course_name',
                'courses.course_code',
                'users.full_name as lecturer_name',
                'student_groups.group_name'
            )
            ->get();

        return response()->json($timetables);
    }

    /**
     * التقرير الرئيسي (محدث لدعم الفلترة)
     */
    public function getCampaignSummary(Request $request)
    {
        $request->validate([
            'campaign_id' => 'required|integer',
            'timetable_id' => 'nullable|integer' // ✅ فلتر اختياري
        ]);

        $campaignId = $request->campaign_id;
        $timetableId = $request->timetable_id;

        // 1. تحديد نطاق الفلترة (Scope)
        // إذا تم تحديد جدول، سنعرف المادة والمحاضر منه لنفلتر التقييمات
        $filterCourseId = null;
        $filterLecturerId = null;

        if ($timetableId) {
            $timetable = DB::table('timetable')->where('timetable_id', $timetableId)->first();
            if ($timetable) {
                $filterCourseId = $timetable->course_id;
                $filterLecturerId = $timetable->lecturer_id;
            }
        }

        // دالة مساعدة لبناء شرط الاستعلام الأساسي
        $applyFilter = function($query) use ($campaignId, $filterCourseId, $filterLecturerId) {
            $query->where('qa_submissions.campaign_id', $campaignId);
            if ($filterCourseId && $filterLecturerId) {
                $query->where('qa_submissions.course_id', $filterCourseId)
                      ->where('qa_submissions.lecturer_id', $filterLecturerId);
            }
        };

        // ---------------------------------------------
        // أ. الإحصائيات المفلترة (للبطاقة المختارة)
        // ---------------------------------------------
        
        $totalSubmissionsQuery = QaSubmission::query();
        // تطبيق نفس منطق الفلترة يدوياً هنا لأن Eloquent يختلف قليلاً
        $totalSubmissionsQuery->where('campaign_id', $campaignId);
        if ($filterCourseId && $filterLecturerId) {
            $totalSubmissionsQuery->where('course_id', $filterCourseId)
                                  ->where('lecturer_id', $filterLecturerId);
        }
        $totalSubmissions = $totalSubmissionsQuery->count();

        // المتوسط العام (المفلتر)
        $avgQuery = DB::table('qa_answers')
            ->join('qa_submissions', 'qa_answers.submission_id', '=', 'qa_submissions.submission_id');
        $applyFilter($avgQuery);
        $overallAvg = $avgQuery->avg('rating_value');
        $overallPercentage = $overallAvg ? ($overallAvg / 3) * 100 : 0;

        // تحليل المجالات (المفلتر)
        $domainsQuery = DB::table('qa_answers')
            ->join('qa_submissions', 'qa_answers.submission_id', '=', 'qa_submissions.submission_id')
            ->join('qa_questions', 'qa_answers.question_id', '=', 'qa_questions.question_id')
            ->join('qa_domains', 'qa_questions.domain_id', '=', 'qa_domains.domain_id');
        $applyFilter($domainsQuery);
        
        $rawQuestions = $domainsQuery->select(
            'qa_domains.domain_name',
            'qa_questions.question_text',
            DB::raw('AVG(qa_answers.rating_value) as avg_score'),
            DB::raw('COUNT(*) as total_answers'),
            DB::raw('SUM(CASE WHEN rating_value = 3 THEN 1 ELSE 0 END) as count_3'),
            DB::raw('SUM(CASE WHEN rating_value = 2 THEN 1 ELSE 0 END) as count_2'),
            DB::raw('SUM(CASE WHEN rating_value = 1 THEN 1 ELSE 0 END) as count_1')
        )
        ->groupBy('qa_domains.domain_id', 'qa_domains.domain_name', 'qa_questions.question_id', 'qa_questions.question_text')
        ->get();

        // تجميع النتائج
        $domainsAnalysis = $rawQuestions->groupBy('domain_name')->map(function ($questions, $domainName) {
            $domainAvg = $questions->avg('avg_score');
            return [
                'name' => $domainName,
                'score' => round($domainAvg, 2),
                'percentage' => round(($domainAvg / 3) * 100, 1),
                'questions' => $questions->map(function($q) {
                    $total = $q->total_answers > 0 ? $q->total_answers : 1;
                    return [
                        'question' => $q->question_text,
                        'avg_score' => round($q->avg_score, 2),
                        'distribution' => [
                            'agree' => round(($q->count_3 / $total) * 100, 1),
                            'neutral' => round(($q->count_2 / $total) * 100, 1),
                            'disagree' => round(($q->count_1 / $total) * 100, 1),
                        ]
                    ];
                })->values()
            ];
        })->values();

        // ---------------------------------------------
        // ب. جدول المقارنة الشامل (Leaderboard) - دائماً لكل الحملة
        // ---------------------------------------------
        $lecturersPerformance = DB::table('qa_submissions')
            ->join('lecturers', 'qa_submissions.lecturer_id', '=', 'lecturers.lecturer_id')
            ->join('users', 'lecturers.user_id', '=', 'users.user_id')
            ->join('courses', 'qa_submissions.course_id', '=', 'courses.course_id')
            ->join('qa_answers', 'qa_submissions.submission_id', '=', 'qa_answers.submission_id')
            ->where('qa_submissions.campaign_id', $campaignId) // ⚠️ لا نفلتر هنا بالجدول، نريد الكل للمقارنة
            ->select(
                'lecturers.lecturer_id',
                'users.full_name as lecturer_name',
                'courses.course_name',
                'qa_submissions.course_id', // نحتاجه للتمييز
                DB::raw('COUNT(DISTINCT qa_submissions.submission_id) as evaluation_count'),
                DB::raw('AVG(qa_answers.rating_value) as avg_score')
            )
            ->groupBy('lecturers.lecturer_id', 'users.full_name', 'qa_submissions.course_id', 'courses.course_name')
            ->orderByDesc('avg_score')
            ->get()
            ->map(function($l) use ($filterCourseId, $filterLecturerId) {
                // نضع علامة إذا كان هذا الصف هو المختار حالياً
                $isSelected = ($l->course_id == $filterCourseId && $l->lecturer_id == $filterLecturerId);
                return [
                    'name' => $l->lecturer_name,
                    'course' => $l->course_name,
                    'eval_count' => $l->evaluation_count,
                    'score' => round($l->avg_score, 2),
                    'percentage' => round(($l->avg_score / 3) * 100, 1),
                    'rating_label' => self::getRatingLabel($l->avg_score),
                    'is_current' => $isSelected // ✅ لتمييزه في الجدول
                ];
            });

        // جلب هدف الجودة
        $campaignInfo = \App\Models\QA\QaCampaign::find($campaignId);

        return response()->json([
            'summary' => [
                'total_submissions' => $totalSubmissions,
                'overall_score' => round($overallAvg, 2),
                'overall_percentage' => round($overallPercentage, 1),
                'target_percentage' => $campaignInfo->target_percentage,
            ],
            'domains_analysis' => $domainsAnalysis,
            'leaderboard' => $lecturersPerformance // الاسم الجديد للقائمة
        ]);
    }

    private static function getRatingLabel($score) {
        if ($score >= 2.5) return 'ممتاز';
        if ($score >= 2.0) return 'جيد جداً';
        if ($score >= 1.5) return 'متوسط';
        return 'ضعيف';
    }
}