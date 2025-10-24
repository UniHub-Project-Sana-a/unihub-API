<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Department\StoreDepartmentRequest;
use App\Http\Requests\V1\Department\UpdateDepartmentRequest;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentsController extends Controller {
    public function index(Request $request) {
        $q = $request->query('q');
        $query = Department::query()->with('college')->when($q, fn($qq) => $qq->where('department_name', 'like', "%{$q}%"));
        return response()->json($query->get());
    }
    public function store(StoreDepartmentRequest $request) {
        $department = Department::create($request->validated());
        return response()->json($department->load('college'), 201);
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