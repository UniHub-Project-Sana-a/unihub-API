<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FinancialCycle;
use App\Models\LecturerPayout;
use App\Models\Lecturer;
use App\Models\LectureSession;
use App\Models\College;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class FinancialController extends Controller
{
    // جلب الكشف لشهر معين
    public function getCycleByMonth(Request $request, $collegeId)
    {
        $month = $request->month; // 10
        $year = $request->year;   // 2024 (Academic or Calendar year?) سنفترض Calendar للرواتب
        
        $monthYear = sprintf("%02d-%s", $month, $year); // 10-2024

        $cycle = FinancialCycle::where('college_id', $collegeId)
            ->where('month_year', $monthYear)
            ->with(['payouts.lecturer.user', 'payouts.lecturer.department', 'payouts.adjustments'])
            ->first();

        if (!$cycle) {
            return response()->json(['status' => false, 'message' => 'No cycle found', 'code' => 'NOT_FOUND']);
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
            'year'  => 'required|integer|min:2020',
        ]);

        $month = $request->month;
        $year = $request->year;
        $monthYear = sprintf("%02d-%s", $month, $year);

        // 1. التحقق من وجود كشف سابق
        $cycle = FinancialCycle::firstOrCreate(
            ['college_id' => $collegeId, 'month_year' => $monthYear],
            [
                'start_date' => Carbon::create($year, $month, 1)->startOfMonth(),
                'end_date'   => Carbon::create($year, $month, 1)->endOfMonth(),
                'status'     => 'draft',
                'created_by' => Auth::id() ?? 1, // المستخدم الحالي
            ]
        );

        // حماية: لا يمكن إعادة الحساب إذا كان الكشف معتمداً أو مدفوعاً
        if (in_array($cycle->status, ['approved', 'paid', 'locked'])) {
            return response()->json([
                'status' => false, 
                'message' => 'لا يمكن إعادة توليد الكشف لأنه معتمد أو مدفوع.'
            ], 403);
        }

        DB::beginTransaction();
        try {
            // 2. جلب جميع المحاضرين في الكلية
            $lecturers = Lecturer::where('college_id', $collegeId)
                ->with('academicTitle') // لجلب السعر
                ->get();

            $totalCyclePayout = 0;
            $lecturersCount = 0;

            foreach ($lecturers as $lecturer) {
                // 3. حساب الساعات المنفذة في هذا الشهر
                // نجمع ساعات كل الجلسات المنفذة (status=1) في هذا الشهر
                $executedHours = DB::table('lecture_sessions')
                    ->join('timetable', 'lecture_sessions.timetable_id', '=', 'timetable.timetable_id')
                    ->where('timetable.lecturer_id', $lecturer->lecturer_id)
                    ->where('lecture_sessions.status', 1) // منفذة
                    ->whereMonth('lecture_sessions.session_date', $month) // في هذا الشهر
                    ->whereYear('lecture_sessions.session_date', $year)   // في هذه السنة
                    ->sum('timetable.lecture_hours'); // نجمع ساعات المحاضرة

                // إذا لم يكن لديه ساعات، نتجاوزه (أو نسجله بصفر حسب السياسة)
                if ($executedHours <= 0) {
                    // خيار: حذف السجل إذا كان موجوداً سابقاً وأصبح صفراً
                    LecturerPayout::where('cycle_id', $cycle->cycle_id)
                        ->where('lecturer_id', $lecturer->lecturer_id)
                        ->delete();
                    continue;
                }

                // 4. تحديد السعر
                $hourlyRate = $lecturer->academicTitle->hourly_price ?? 0;
                $baseAmount = $executedHours * $hourlyRate;

                // 5. إنشاء أو تحديث سجل الاستحقاق
                $payout = LecturerPayout::updateOrCreate(
                    [
                        'cycle_id'    => $cycle->cycle_id,
                        'lecturer_id' => $lecturer->lecturer_id
                    ],
                    [
                        'total_hours' => $executedHours,
                        'hourly_rate' => $hourlyRate,
                        'base_amount' => $baseAmount,
                        // لا نعيد تعيين الخصومات/الإضافات يدوياً هنا لكي لا نفقدها عند التحديث
                        // لكن يجب تحديث الصافي
                    ]
                );
                
                // تحديث الصافي (لأن الساعات قد تكون تغيرت)
                // هذا يتطلب أن تكون الخصومات مسجلة في جدول adjustments
                // دالة recalculateTotals في الموديل ستقوم باللازم
                $payout->recalculateTotals();

                $totalCyclePayout += $payout->net_amount; // أو base_amount حسب المنطق
                $lecturersCount++;
            }

            // 6. تحديث إجماليات الكشف
            $cycle->update([
                'total_payout' => $totalCyclePayout, // هذا تقريبي، الأدق جمعه من الجدول
                'lecturers_count' => $lecturersCount,
                'updated_at' => now()
            ]);
            
            // إعادة حساب دقيق للإجمالي من القاعدة
            $realTotal = LecturerPayout::where('cycle_id', $cycle->cycle_id)->sum(DB::raw('base_amount + total_bonuses - total_deductions - tax_amount'));
            $cycle->update(['total_payout' => $realTotal]);

            DB::commit();

            // إرجاع الكشف محدثاً
            return $this->getCycleByMonth($request, $collegeId);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'فشل توليد الكشف: ' . $e->getMessage()
            ], 500);
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