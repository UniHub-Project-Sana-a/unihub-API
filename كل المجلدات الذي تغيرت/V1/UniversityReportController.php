<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\College; // أو College حسب تسميتك
use Illuminate\Http\Request;

class UniversityReportController extends Controller
{
    public function index()
    {
        // استخدام Eager Loading لجلب كل شيء دفعة واحدة لتجنب N+1 Problem
        $data = College::with([
            // 1. الخطة الدراسية (تسلسل عميق)
            'departments.programs.levels.semesters.courses',
            
            // 2. القاعات والمباني
            'buildings.classrooms',
            
            // 3. الرتب الأكاديمية
            'academicTitles',
            
            // 4. أعضاء هيئة التدريس (مع بيانات المستخدم)
            'lecturers.user',
            'lecturers.department', // لمعرفة القسم
            'lecturers.academicTitle', // لمعرفة الرتبة
            
            // 5. الفترات الزمنية
            'periods',
            
            // 6. تسجيل الطلاب (المجموعات)
            'studentGroups' => function($q) {
                $q->withCount('students'); // نكتفي بالعدد
                $q->with(['department', 'level', 'semester']); // لمعرفة التبعية
            }
        ])->get();

        return response()->json($data);
    }
}