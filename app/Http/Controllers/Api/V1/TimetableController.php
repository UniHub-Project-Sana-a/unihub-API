<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Timetable\StoreTimetableRequest;
use App\Http\Requests\V1\Timetable\UpdateTimetableRequest;
use App\Models\Timetable;
use Illuminate\Http\Request;

class TimetableController extends Controller {
    public function index(Request $request) {
        $q = $request->query('q');
        $query = Timetable::query()->with(['course', 'lecturer', 'group', 'classroom', 'day', 'period', 'college', 'department'])->when($q, fn($qq) => $qq->whereHas('course', fn($q) => $q->where('course_name', 'like', "%{$q}%")));
        return response()->json($query->get());
    }
    public function store(StoreTimetableRequest $request) {
        $timetable = Timetable::create($request->validated());
        return response()->json($timetable->load(['course', 'lecturer', 'group', 'classroom', 'day', 'period', 'college', 'department']), 201);
    }
    public function show(Timetable $timetable) {
        return response()->json($timetable->load(['course', 'lecturer', 'group', 'classroom', 'day', 'period', 'college', 'department']));
    }
    public function update(UpdateTimetableRequest $request, Timetable $timetable) {
        $timetable->update($request->validated());
        return response()->json($timetable->load(['course', 'lecturer', 'group', 'classroom', 'day', 'period', 'college', 'department']));
    }
    public function destroy(Timetable $timetable) {
        $timetable->delete();
        return response()->json(['message' => 'Timetable entry deleted']);
    }
}