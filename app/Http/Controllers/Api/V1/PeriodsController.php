<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Period\StorePeriodRequest;
use App\Http\Requests\V1\Period\UpdatePeriodRequest;
use App\Models\Period;
use Illuminate\Http\Request;

class PeriodsController extends Controller
{
    // GET /v1/periods?college_id=...
    public function index(Request $request)
    {
        $q = Period::query()->select(['period_id','college_id','period_name','start_time','end_time','session_type']);
        if ($request->filled('college_id')) {
            $q->where('college_id', (int)$request->college_id);
        }
        $q->orderBy('start_time');
        return response()->json($q->get());
    }

    public function store(StorePeriodRequest $request)
    {
        $data = $request->validated();

        // تحقق من عدم التداخل ضمن نفس الكلية
        $this->ensureNoOverlap(
            $data['college_id'],
            $data['start_time'],
            $data['end_time']
        );

        $period = Period::create($data);
        return response()->json($period->fresh(), 201);
    }

    public function update(UpdatePeriodRequest $request, Period $period)
    {
        $data = $request->validated();

        // لو بدأ الوقت أو انتهى تغير، افحص التداخل
        $collegeId = $data['college_id'] ?? $period->college_id;
        $start     = $data['start_time'] ?? $period->start_time;
        $end       = $data['end_time']   ?? $period->end_time;

        $this->ensureNoOverlap($collegeId, $start, $end, $period->period_id);

        $period->update($data);
        return response()->json($period->fresh());
    }

    public function destroy(Period $period)
    {
        $period->delete();
        return response()->json(['message'=>'Deleted']);
    }

    private function ensureNoOverlap(int $collegeId, string $start, string $end, ?int $ignoreId = null): void
    {
        $overlaps = Period::query()
            ->where('college_id', $collegeId)
            ->when($ignoreId, fn($q) => $q->where('period_id','!=',$ignoreId))
            ->where(function($q) use ($start, $end) {
                // تتداخل إذا كانت (start < existing_end) && (end > existing_start)
                $q->where(function($qq) use ($start, $end) {
                    $qq->where('start_time','<',$end)->where('end_time','>',$start);
                });
            })->exists();

        if ($overlaps) {
            abort(422, 'الفترة الزمنية تتداخل مع فترة أخرى في نفس الكلية');
        }
    }
}