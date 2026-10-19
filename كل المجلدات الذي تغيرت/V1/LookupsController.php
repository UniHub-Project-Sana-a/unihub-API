<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UserType;
use App\Models\Permission;
use App\Models\College;
use Illuminate\Support\Facades\DB;

class LookupsController extends Controller
{
    public function userTypes()
    {
        return response()->json(
            UserType::select('user_type_id','user_type_name','user_type_code')
                ->orderBy('user_type_name')
                ->get()
        );
    }

    public function permissions()
    {
        return response()->json(
            Permission::select('permission_id','permission_key','permission_name')
                ->orderBy('permission_name')
                ->get()
        );
    }

    public function colleges()
    {
        return response()->json(
            College::select('college_id','college_name')
                ->orderBy('college_name')
                ->get()
        );
    }

    public function academicYears()
    {
        // نجلب السنوات الفريدة من جدول timetable
        $years = DB::table('timetable')
                    ->select('academic_year')
                    ->whereNotNull('academic_year') // نتجاهل القيم الفارغة
                    ->where('academic_year', '!=', '')
                    ->distinct()
                    ->orderBy('academic_year', 'desc') // الأحدث أولاً
                    ->pluck('academic_year');

        return response()->json([
            'status' => true,
            'data' => $years
        ]);
    }
}