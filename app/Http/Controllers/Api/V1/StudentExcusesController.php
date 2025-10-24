<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Excuse\StoreExcuseRequest;
use App\Models\StudentExcuseSubmission;
use App\Models\StudentAttendance;
use Illuminate\Http\Request;

class StudentExcusesController extends Controller {
    public function store(StoreExcuseRequest $request) {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $path = $request->hasFile('attachment') ? $request->file('attachment')->store('excuses') : null;
        $excuse = StudentExcuseSubmission::create([
            'student_user_id' => $user->id,
            'attachment_path' => $path,
            ...$request->validated()
        ]);
        return response()->json($excuse, 201);
    }

    public function approveByHead(Request $request, StudentExcuseSubmission $excuse) {
        $excuse->update(['status' => 'approved_by_head']);
        return response()->json($excuse);
    }

    public function approveByLecturer(Request $request, StudentExcuseSubmission $excuse) {
        $excuse->update(['response_status' => 'approved']);
        // تحديث سجل الحضور
        StudentAttendance::where('student_id', $excuse->student_user_id)
            ->where('attendance_date', $excuse->request_date)
            // ->where(...) // يمكن إضافة شروط أخرى
            ->update(['status' => 2]); // 2 = غائب بعذر
        return response()->json($excuse);
    }
}