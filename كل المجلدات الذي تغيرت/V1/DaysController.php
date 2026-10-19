<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Day\StoreDayRequest;
use App\Http\Requests\V1\Day\UpdateDayRequest;
use App\Models\Day;
use Illuminate\Http\Request;

class DaysController extends Controller {
    public function index(Request $request) {
        $q = $request->query('q');
        $query = Day::query()->when($q, fn($qq) => $qq->where('day_name', 'like', "%{$q}%"));
        return response()->json($query->get());
    }
    public function store(StoreDayRequest $request) {
        $day = Day::create($request->validated());
        return response()->json($day, 201);
    }
    public function show(Day $day) {
        return response()->json($day);
    }
    public function update(UpdateDayRequest $request, Day $day) {
        $day->update($request->validated());
        return response()->json($day);
    }
    public function destroy(Day $day) {
        $day->delete();
        return response()->json(['message' => 'Day deleted']);
    }
}