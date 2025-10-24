<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StudentGroup\StoreStudentGroupRequest;
use App\Http\Requests\V1\StudentGroup\UpdateStudentGroupRequest;
use App\Models\StudentGroup;
use Illuminate\Http\Request;

class StudentGroupsController extends Controller {
    public function index(Request $request) {
        $q = $request->query('q');
        $query = StudentGroup::query()->when($q, fn($qq) => $qq->where('group_name', 'like', "%{$q}%"));
        return response()->json($query->get());
    }
    public function store(StoreStudentGroupRequest $request) {
        $group = StudentGroup::create($request->validated());
        return response()->json($group, 201);
    }
    public function show(StudentGroup $studentGroup) {
        return response()->json($studentGroup);
    }
    public function update(UpdateStudentGroupRequest $request, StudentGroup $studentGroup) {
        $studentGroup->update($request->validated());
        return response()->json($studentGroup);
    }
    public function destroy(StudentGroup $studentGroup) {
        $studentGroup->delete();
        return response()->json(['message' => 'Student group deleted']);
    }
}