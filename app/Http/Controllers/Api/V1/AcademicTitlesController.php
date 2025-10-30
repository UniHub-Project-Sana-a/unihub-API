<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\AcademicTitle\StoreAcademicTitleRequest;
use App\Http\Requests\V1\AcademicTitle\UpdateAcademicTitleRequest;
use App\Models\AcademicTitle;
use Illuminate\Http\Request;

class AcademicTitlesController extends Controller
{
    // GET /api/v1/academic-titles?college_id=...
    public function index(Request $request)
    {
        $q = AcademicTitle::query()
            ->select(['title_id','title_name','title_code','hourly_price','lecture_price','college_id']);

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
}