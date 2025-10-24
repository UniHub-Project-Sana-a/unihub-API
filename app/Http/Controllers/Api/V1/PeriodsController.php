<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Period\StorePeriodRequest;
use App\Http\Requests\V1\Period\UpdatePeriodRequest;
use App\Models\Period;
use Illuminate\Http\Request;

class PeriodsController extends Controller {
    public function index(Request $request) {
        $q = $request->query('q');
        $query = Period::query()->with('college')->when($q, fn($qq) => $qq->where('period_name', 'like', "%{$q}%"));
        return response()->json($query->get());
    }
    public function store(StorePeriodRequest $request) {
        $period = Period::create($request->validated());
        return response()->json($period->load('college'), 201);
    }
    public function show(Period $period) {
        return response()->json($period->load('college'));
    }
    public function update(UpdatePeriodRequest $request, Period $period) {
        $period->update($request->validated());
        return response()->json($period->load('college'));
    }
    public function destroy(Period $period) {
        $period->delete();
        return response()->json(['message' => 'Period deleted']);
    }
}