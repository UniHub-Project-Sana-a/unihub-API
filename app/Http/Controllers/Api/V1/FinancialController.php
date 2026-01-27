<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FinancialCycle;
use App\Models\LecturerPayout;
use App\Models\Lecturer;
use App\Models\LectureSession;
use App\Models\College;
use GeniusTS\HijriDate\Hijri;
use GeniusTS\HijriDate\Date as HijriDate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class FinancialController extends Controller
{
    // جلب الكشف لشهر معين
    public function getCycleByMonth(Request $request, $collegeId)
    {
        $month = $request->month; // مثال: 1
        $startYear = $request->year;   // مثال: 2025 (بداية العام الدراسي)
        $calendarType = $request->input('calendar_type', 'gregorian');

        $monthYearKey = "";

        // 1. حساب المفتاح الصحيح بنفس منطق دالة التوليد (generateCycle)
        if ($calendarType === 'hijri') {
            // للهجري: نستخدم آخر رقمين من السنة + حرف H
            $shortYear = $startYear % 100;
            $monthYearKey = sprintf("%02d-%02dH", $month, $shortYear);
        } else {
            // للميلادي: نحسب السنة التقويمية الفعلية
            // إذا الشهر < 9 (يناير - أغسطس) فهو في السنة التالية (2026)
            // إذا الشهر >= 9 (سبتمبر - ديسمبر) فهو في نفس سنة البداية (2025)
            $realCalendarYear = ($month < 9) ? ($startYear + 1) : $startYear;
            
            // المفتاح الذي تم تخزينه عند الإنشاء
            $monthYearKey = sprintf("%02d-%d", $month, $realCalendarYear);
        }

        // 2. البحث عن الكشف
        $cycle = FinancialCycle::where('college_id', $collegeId)
            ->where('month_year', $monthYearKey)
            ->with(['payouts.lecturer.user', 'payouts.lecturer.department', 'payouts.adjustments'])
            ->first();

        if (!$cycle) {
            return response()->json([
                'status' => false, 
                'message' => 'No cycle found', 
                'code' => 'NOT_FOUND',
                // (اختياري) لمعرفة ماذا كان يبحث النظام
                // 'debug_searched_key' => $monthYearKey 
            ], 404);
        }

        return response()->json(['status' => true, 'data' => $cycle]);
    }

    /**
     * توليد أو تحديث كشف الرواتب لشهر معين
     */
    public function generateCycle(Request $request, $collegeId)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year'  => 'required|integer', // مثلاً 2025 (بداية السنة الدراسية)
            'calendar_type' => 'nullable|in:gregorian,hijri',
        ]);

        $month = $request->month;
        $startYear = $request->year; // 2025
        $calendarType = $request->input('calendar_type', 'gregorian');

        // 1. بناء نص العام الدراسي (للمطابقة مع timetable.academic_year)
        // إذا كان النظام هجرياً، تكون الصيغة 1446-1447، وإذا ميلادي 2025-2026
        $academicYearString = sprintf("%d-%d", $startYear, $startYear + 1);

        // 2. تحديد "السنة التقويمية الفعلية" (لأجل تواريخ الكشف start_date و end_date)
        // القاعدة: إذا كان الشهر أقل من 9 (يناير - أغسطس)، فهو في السنة التالية (2026)
        // إذا كان الشهر 9 أو أكثر (سبتمبر - ديسمبر)، فهو في نفس سنة البداية (2025)
        $realCalendarYear = ($month < 9) ? ($startYear + 1) : $startYear;

        // تحديد التواريخ الدقيقة للكشف (للتخزين في financial_cycles)
        if ($calendarType === 'hijri') {
            $dates = $this->getGregorianDatesFromHijri($realCalendarYear, $month);
            $startDate = $dates['start'];
            $endDate = $dates['end'];
            $monthYearKey = sprintf("%02d-%s-H", $month, $academicYearString); 
        } else {
            $startDate = \Carbon\Carbon::create($realCalendarYear, $month, 1)->startOfMonth();
            $endDate = \Carbon\Carbon::create($realCalendarYear, $month, 1)->endOfMonth();
            if ($calendarType === 'hijri') {
                $shortYear = $startYear % 100;
                $monthYearKey = sprintf("%02d-%02dH", $month, $shortYear); 
            } else {
                $monthYearKey = sprintf("%02d-%d", $month, $realCalendarYear);
            }
        }

        // 3. إدارة سجل الدورة المالية
        $cycle = FinancialCycle::firstOrCreate(
            ['college_id' => $collegeId, 'month_year' => $monthYearKey],
            [
                'start_date' => $startDate,
                'end_date'   => $endDate,
                'status'     => 'draft',
                'created_by' => \Illuminate\Support\Facades\Auth::id() ?? 1,
            ]
        );

        if (in_array($cycle->status, ['approved', 'paid', 'locked'])) {
            return response()->json(['status' => false, 'message' => 'الكشف معتمد ولا يمكن تعديله.'], 403);
        }

        DB::beginTransaction();
        try {
            // 4. الاستعلام: نعتمد على academic_year في timetable وعلى الشهر في lecture_sessions
            $lecturerStats = DB::table('lecture_sessions')
                ->join('timetable', 'lecture_sessions.timetable_id', '=', 'timetable.timetable_id')
                ->where('timetable.college_id', $collegeId)
                // ✅ الشرط الأول: السنة الدراسية في الجدول
                ->where('timetable.academic_year', $academicYearString)
                // ✅ الشرط الثاني: الجلسات المنفذة فقط
                ->where('lecture_sessions.status', 1) 
                // ✅ الشرط الثالث: الشهر المحدد (بغض النظر عن السنة، لأن academic_year ضبطها)
                // نستخدم whereBetween للتأكد من الدقة في حال الهجري، أو whereMonth للميلادي
                ->where(function($q) use ($calendarType, $month, $startDate, $endDate) {
                    if ($calendarType === 'hijri') {
                        // للهجري نلتزم بالنطاق الزمني المحول
                        $q->whereBetween('lecture_sessions.session_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
                    } else {
                        // للميلادي: نستخدم الشهر والسنة الفعلية التي حسبناها
                        // (ضروري تحديد السنة الفعلية 2026 لكي لا يجلب بيانات من 2025 بالخطأ)
                        $q->whereMonth('lecture_sessions.session_date', $startDate->month)
                          ->whereYear('lecture_sessions.session_date', $startDate->year);
                    }
                })
                ->select(
                    DB::raw('COALESCE(lecture_sessions.lecturer_id, timetable.lecturer_id) as actual_lecturer_id'),
                    DB::raw('SUM(timetable.lecture_hours) as total_hours')
                )
                ->groupBy('actual_lecturer_id')
                ->get();

            $activeLecturerIds = [];
            $totalCyclePayout = 0;
            $lecturersCount = 0;

            foreach ($lecturerStats as $stat) {
                if (!$stat->actual_lecturer_id || $stat->total_hours <= 0) continue;

                $activeLecturerIds[] = $stat->actual_lecturer_id;

                $lecturer = Lecturer::with('academicTitle')->find($stat->actual_lecturer_id);
                if (!$lecturer) continue;

                $hourlyRate = $lecturer->academicTitle->hourly_price ?? 0;
                $baseAmount = $stat->total_hours * $hourlyRate;

                // إنشاء أو تحديث
                $payout = LecturerPayout::updateOrCreate(
                    [
                        'cycle_id'    => $cycle->cycle_id,
                        'lecturer_id' => $stat->actual_lecturer_id
                    ],
                    [
                        'total_hours' => $stat->total_hours,
                        'hourly_rate' => $hourlyRate,
                        'base_amount' => $baseAmount,
                    ]
                );
                
                // حساب الصافي
                if (method_exists($payout, 'recalculateTotals')) {
                    $payout->recalculateTotals();
                    $net = $payout->net_amount;
                } else {
                    $net = $baseAmount + ($payout->total_bonuses ?? 0) - ($payout->total_deductions ?? 0) - ($payout->tax_amount ?? 0);
                    $payout->update(['net_amount' => $net]);
                }

                $totalCyclePayout += $net;
                $lecturersCount++;
            }

            // تنظيف
            LecturerPayout::where('cycle_id', $cycle->cycle_id)
                ->whereNotIn('lecturer_id', $activeLecturerIds)
                ->delete();

            // تحديث الكشف
            $cycle->update([
                'total_payout' => $totalCyclePayout,
                'lecturers_count' => $lecturersCount,
                'updated_at' => now()
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'data' => $cycle->load(['payouts.lecturer.user', 'payouts.lecturer.department'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * تحويل الشهر الهجري إلى نطاق ميلادي (بداية ونهاية الشهر)
     */
    private function getGregorianDatesFromHijri($year, $month)
    {
        try {
            // 1. تحديد عدد أيام الشهر الهجري لهذه السنة (للحصول على تاريخ النهاية بدقة 29 أو 30)
            // ملاحظة: المكتبة قد لا تحتوي على دالة مباشرة لعدد الأيام، لذا سنفترض 30 يوماً
            // وإذا كان التاريخ غير صالح (مثلاً 30 فبراير)، ستقوم بالتصحيح أو نستخدم try-catch
            
            // تاريخ بداية الشهر الهجري (1 / شهر / سنة)
            $hijriStart = Hijri::convertToGregorian(1, $month, $year);
            $startDate = Carbon::createFromDate($hijriStart);

            // تاريخ نهاية الشهر الهجري
            // نحاول تحويل يوم 30، إذا فشل نجرب 29
            try {
                $hijriEnd = Hijri::convertToGregorian(30, $month, $year);
            } catch (\Exception $e) {
                $hijriEnd = Hijri::convertToGregorian(29, $month, $year);
            }
            
            $endDate = Carbon::createFromDate($hijriEnd)->endOfDay();

            return [
                'start' => $startDate,
                'end'   => $endDate
            ];

        } catch (\Exception $e) {
            // في حال حدوث خطأ في التحويل، نعود للميلادي كاحتياط لتجنب توقف النظام
            Log::warning("Hijri conversion failed for $month-$year: " . $e->getMessage());
            $start = Carbon::create($year, $month, 1);
            return [
                'start' => $start, 
                'end' => $start->copy()->endOfMonth()
            ];
        }
    }

    /**
     * إضافة تسوية (خصم/مكافأة) لسجل استحقاق معين
    */
    public function addAdjustment(Request $request, $collegeId, $payoutId)
    {
        $request->validate([
            'type'   => 'required|in:bonus,deduction,tax',
            'amount' => 'required|numeric|min:0',
            'reason' => 'nullable|string|max:255',
        ]);

        // 1. جلب الاستحقاق مع الكشف المرتبط به للتأكد من الحالة
        $payout = LecturerPayout::with('cycle')->findOrFail($payoutId);

        // 2. منع التعديل إذا كان الكشف مغلقاً أو مدفوعاً
        if (in_array($payout->cycle->status, ['approved', 'paid', 'locked'])) {
            return response()->json([
                'status' => false,
                'message' => 'عذراً، لا يمكن تعديل هذا الكشف لأنه معتمد أو تم صرفه.'
            ], 403);
        }

        DB::beginTransaction();
        try {
            // 3. إنشاء التسوية
            $payout->adjustments()->create([
                'type'         => $request->type,
                'amount'       => $request->amount,
                'reason'       => $request->reason,
                'is_automatic' => false // تسوية يدوية
            ]);

            // 4. إعادة حساب إجماليات المحاضر وإجمالي الكشف
            // (تعتمد على الدالة التي أضفناها في الموديل سابقاً)
            $payout->recalculateTotals();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'تم إضافة التسوية بنجاح وتحديث الصافي.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
      * تحديث حالة الدورة المالية (إرسال للمراجعة / اعتماد / صرف)
    */
    public function updateStatus(Request $request, $collegeId, $cycleId)
    {
        $request->validate([
            'status' => 'required|in:draft,review,approved,paid,locked'
        ]);

        $cycle = FinancialCycle::findOrFail($cycleId);

        // (اختياري) يمكنك إضافة شروط منطقية هنا
        // مثال: لا يمكن الانتقال من draft إلى paid مباشرة
        // if ($cycle->status == 'draft' && $request->status == 'paid') { ... }

        $cycle->update([
            'status' => $request->status,
            'updated_at' => now() // لتسجيل وقت الاعتماد
        ]);

        // إذا تم الاعتماد، يمكننا تحديث حالة كل الاستحقاقات الفردية أيضاً
        if ($request->status == 'approved') {
            $cycle->payouts()->update(['status' => 'approved']);
        } elseif ($request->status == 'paid') {
            $cycle->payouts()->update(['status' => 'paid']);
        }

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث حالة الكشف بنجاح.',
            'data' => $cycle
        ]);
    }
}