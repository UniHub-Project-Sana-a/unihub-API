<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Department\StoreDepartmentRequest;
use App\Http\Requests\V1\Department\UpdateDepartmentRequest;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentsController extends Controller {
    public function index(\Illuminate\Http\Request $r)
{
    $q = \App\Models\Department::query()
        ->select(['department_id','department_name','department_code','college_id']);

    if ($r->filled('college_id')) {
        $q->where('college_id', (int) $r->college_id);
    }

    return response()->json($q->get());
}
    public function store(StoreDepartmentRequest $request)
{
    $data = $request->validated();
    $dept = \App\Models\Department::create([
        'department_name' => $data['department_name'],
        'department_code' => $data['department_code'] ?? null,
        'college_id'      => $data['college_id'],
    ]);
    return response()->json($dept->fresh(), 201);
}
    public function show(Department $department) {
        return response()->json($department->load('college'));
    }
    public function update(UpdateDepartmentRequest $request, Department $department) {
        $department->update($request->validated());
        return response()->json($department->load('college'));
    }
    public function destroy(Department $department) {
        $department->delete();
        return response()->json(['message' => 'Department deleted']);
    }
}