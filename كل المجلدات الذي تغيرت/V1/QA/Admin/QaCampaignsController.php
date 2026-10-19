<?php

namespace App\Http\Controllers\Api\V1\QA\Admin;

use App\Http\Controllers\Controller;
use App\Models\QA\QaCampaign;
use App\Models\QA\QaForm;
use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;  

class QaCampaignsController extends Controller
{
    /**
     * عرض قائمة الحملات (الحالية والسابقة)
     */
    public function index(Request $request)
    {
        $collegeId = $request->query('college_id');
    
        // 👇 التعديل هنا: استبدل type بـ target_type في جملة with
        $campaigns = QaCampaign::with(['form:form_id,title,target_type', 'semester:semester_id,semester_name,academic_year']) // (ملاحظة: semester قد تكون null الآن بعد التعديلات الأخيرة، يفضل حذفها إذا لم تعد مستخدمة أو تركها إذا كان هناك علاقة)
            // الأفضل بعد التعديلات الأخيرة أن يكون الاستدعاء كالتالي:
            ->with(['form:form_id,title,target_type']) 
            ->whereHas('form', function($q) use ($collegeId) {
                    if($collegeId) $q->where('college_id', $collegeId);
            })
            ->orderByDesc('created_at')
            ->get();
    
        return response()->json($campaigns);
    }

    /**
     * جلب البيانات اللازمة للقوائم المنسدلة (Semesters + Forms)
     */
    public function getCreationMeta(Request $request)
    {
        $collegeId = $request->query('college_id');
    
        // 1. جلب النماذج المفعلة (كما كان سابقاً)
        $forms = QaForm::where('college_id', $collegeId)
            ->where('is_active', true)
            ->select('form_id', 'title', 'target_type') // تأكد من target_type
            ->get();
    
        // 2. جلب السنوات الدراسية المتاحة من جدول Timetable
        // الشرط: status != 1 (بناءً على طلبك أن 1 تعني غير متاح)
        $academicYears = DB::table('timetable')
            ->where('college_id', $collegeId)
            // ->where('status', '!=', 1) // حسب طلبك: 1 تعني غير متاح
            ->select('academic_year')
            ->distinct() // لمنع التكرار
            ->orderBy('academic_year', 'desc')
            ->pluck('academic_year');
    
        return response()->json([
            'forms' => $forms,
            'academic_years' => $academicYears
        ]);
    }
    
    // دالة لجلب إحصائيات سريعة عند اختيار السنة (اختياري للعرض في الواجهة)

    public function getYearDetails(Request $request)
    {
        $year = $request->query('year');
        $collegeId = $request->query('college_id');
    
        $rows = \App\Models\Timetable::query()
            ->join('courses', 'timetable.course_id', '=', 'courses.course_id')
            ->join('lecturers', 'timetable.lecturer_id', '=', 'lecturers.lecturer_id')
            ->join('users', 'lecturers.user_id', '=', 'users.user_id')
            ->leftJoin('student_groups', 'timetable.group_id', '=', 'student_groups.group_id')
            ->where('timetable.college_id', $collegeId)
            ->where('timetable.academic_year', $year)
            ->select(
                'timetable.timetable_id',
                'timetable.lecture_type',
                'courses.course_name',
                'courses.course_code',
                'users.full_name as lecturer_name', // المحاضر الأصلي
                'student_groups.group_name'
            )
            ->get();
    
        // 🔥 السحر هنا: البحث عن المحاضرين البدلاء لكل جدول
        $rows->transform(function ($row) {
            // نجمع أسماء المحاضرين المختلفين الذين درّسوا جلسات لهذا الجدول
            $substituteLecturers = DB::table('lecture_sessions')
                ->join('lecturers', 'lecture_sessions.lecturer_id', '=', 'lecturers.lecturer_id')
                ->join('users', 'lecturers.user_id', '=', 'users.user_id')
                ->where('lecture_sessions.timetable_id', $row->timetable_id)
                // ->where('lecture_sessions.status', 1) 
                ->where('users.full_name', '!=', $row->lecturer_name) // نستبعد الأصلي
                ->distinct()
                ->pluck('users.full_name')
                ->toArray();
    
            // إذا وجد بدلاء، نضيفهم للاسم المعروض
            if (!empty($substituteLecturers)) {
                $row->lecturer_name .= ' و ' . implode('، ', $substituteLecturers) . ' (مشترك)';
                $row->has_substitutes = true; // علامة للواجهة (لتلوينها مثلاً)
            } else {
                $row->has_substitutes = false;
            }
    
            return $row;
        });
    
        return response()->json([
            'rows' => $rows,
            'count' => $rows->count()
        ]);
    }
        /**
         * إنشاء حملة جديدة
         */
    public function store(Request $request)
    {
        $request->validate([
            'campaign_name' => 'required|string|max:150',
            'form_id' => 'required|integer|exists:qa_forms,form_id',
            
            // ✅ التغيير 1: استقبال مصفوفة IDs
            'timetable_ids' => 'required|array|min:1',
            'timetable_ids.*' => 'exists:timetable,timetable_id',
            
            'academic_year' => 'required|string',
            'min_attendance_percentage' => 'required|integer|min:0|max:100',
            
            // ✅ التغيير 2: نسبة الهدف الجديدة
            'target_percentage' => 'required|integer|min:50|max:100',
            
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);
    
        // نستخدم Transaction لضمان حفظ كل شيء أو لا شيء
        $campaign = DB::transaction(function () use ($request) {
            
            // 1. إنشاء الحملة
            $newCampaign = QaCampaign::create([
                'campaign_name' => $request->campaign_name,
                'form_id' => $request->form_id,
                'academic_year' => $request->academic_year,
                'min_attendance_percentage' => $request->min_attendance_percentage,
                'target_percentage' => $request->target_percentage,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_published' => false,
            ]);
    
            // 2. ربط الحملة بالجداول المختارة (Bulk Insert)
            $newCampaign->timetables()->attach($request->timetable_ids);
    
            return $newCampaign;
        });
    
        return response()->json($campaign->load('form'), 201);
    }
    /**
     * تحديث الحملة (تعديل التواريخ أو النشر)
     */
    public function update(Request $request, $id)
    {
        $campaign = QaCampaign::findOrFail($id);

        $request->validate([
            'campaign_name' => 'sometimes|string|max:100',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'is_published' => 'sometimes|boolean'
        ]);

        $campaign->update($request->only([
            'campaign_name', 'start_date', 'end_date', 'is_published', 'form_id', 'semester_id'
        ]));

        return response()->json($campaign->load(['form', 'semester']));
    }

    public function destroy($id)
    {
        QaCampaign::destroy($id);
        return response()->noContent();
    }
}