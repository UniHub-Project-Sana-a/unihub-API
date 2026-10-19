<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Period\StorePeriodRequest;
use App\Http\Requests\V1\Period\UpdatePeriodRequest;
use App\Models\Period;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeriodsController extends Controller
{
    // GET /v1/periods?college_id=...
    public function index(Request $request)
    {
        $q = Period::query()->select(['period_id','college_id','period_name','start_time','end_time']);
        if ($request->filled('college_id')) {
            $q->where('college_id', (int)$request->college_id);
        }
        $q->orderBy('start_time');
        return response()->json($q->get());
    }

    public function store(StorePeriodRequest $request)
    {
        $data = $request->validated();

        $period = Period::create($data);
        return response()->json($period->fresh(), 201);
    }

    public function update(UpdatePeriodRequest $request, Period $period)
    {
        $data = $request->validated();

        $period->update($data);
        return response()->json($period->fresh());
    }

    public function destroy(Period $period)
    {
        $period->delete();
        return response()->json(['message'=>'Deleted']);
    }

    /**
     * سلة المحذوفات (Soft Delete) للفترات الزمنية.
     * POST /api/v1/periods/trash
     */
    public function trash(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:list,restore,force_delete',
            'college_id' => 'required|integer|min:1',
            'type' => 'required_if:action,restore,force_delete|in:periods',
            'id' => 'required_if:action,restore,force_delete|integer|min:1',
        ]);

        $collegeId = (int) $validated['college_id'];

        $queries = [
            'periods' => fn () => Period::onlyTrashed()->where('college_id', $collegeId),
        ];

        if ($validated['action'] === 'list') {
            $labels = [
                'periods' => 'الفترات الزمنية',
            ];

            $groups = [];
            foreach ($queries as $type => $queryFactory) {
                $groups[] = [
                    'type' => $type,
                    'label' => $labels[$type],
                    'records' => $queryFactory()->get()->map(function ($record) {
                        $data = $record->getAttributes();
                        $data['deleted_at'] = $record->deleted_at?->toISOString();

                        return [
                            'id' => $record->getKey(),
                            'name' => $record->period_name ?? ('#' . $record->getKey()),
                            'deleted_at' => $record->deleted_at?->toISOString(),
                            'data' => $data,
                        ];
                    })->values(),
                ];
            }

            return response()->json(['groups' => $groups]);
        }

        $type = $validated['type'];
        $record = $queries[$type]()->whereKey((int) $validated['id'])->firstOrFail();

        DB::transaction(function () use ($record, $validated) {
            if ($validated['action'] === 'restore') {
                $record->restore();
            } else {
                $record->forceDelete();
            }
        });

        return response()->json([
            'message' => $validated['action'] === 'restore'
                ? 'تمت استعادة العنصر بنجاح'
                : 'تم حذف العنصر نهائياً',
        ]);
    }
}