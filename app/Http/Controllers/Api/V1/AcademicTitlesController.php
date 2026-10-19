<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\AcademicTitle\StoreAcademicTitleRequest;
use App\Http\Requests\V1\AcademicTitle\UpdateAcademicTitleRequest;
use App\Models\AcademicTitle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AcademicTitlesController extends Controller
{
    // GET /api/v1/academic-titles?college_id=...
    public function index(Request $request)
    {
        $q = AcademicTitle::query()
            ->select(['title_id','title_name','title_code','hourly_price','college_id']);

        if ($request->filled('college_id')) {
            $q->where('college_id', (int)$request->college_id);
        }

        return response()->json($q->get());
    }

    // POST /api/v1/academic-titles
    public function store(StoreAcademicTitleRequest $request)
    {
        $data = $request->validated();
        $title = AcademicTitle::create($data);
        return response()->json($title->fresh(), 201);
    }

    // GET /api/v1/academic-titles/{academic_title}
    public function show(AcademicTitle $academic_title)
    {
        return response()->json($academic_title);
    }

    // PUT /api/v1/academic-titles/{academic_title}
    public function update(UpdateAcademicTitleRequest $request, AcademicTitle $academic_title)
    {
        $academic_title->update($request->validated());
        return response()->json($academic_title->fresh());
    }

    // DELETE /api/v1/academic-titles/{academic_title}
    public function destroy(AcademicTitle $academic_title)
    {
        $academic_title->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * سلة المحذوفات (Soft Delete) للرتب الأكاديمية.
     * POST /api/v1/academic-titles/trash
     */
    public function trash(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:list,restore,force_delete',
            'college_id' => 'required|integer|min:1',
            'type' => 'required_if:action,restore,force_delete|in:academic_titles',
            'id' => 'required_if:action,restore,force_delete|integer|min:1',
        ]);

        $collegeId = (int) $validated['college_id'];

        $queries = [
            'academic_titles' => fn () => AcademicTitle::onlyTrashed()->where('college_id', $collegeId),
        ];

        if ($validated['action'] === 'list') {
            $labels = [
                'academic_titles' => 'الرتب الأكاديمية',
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
                            'name' => $record->title_name ?? ('#' . $record->getKey()),
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