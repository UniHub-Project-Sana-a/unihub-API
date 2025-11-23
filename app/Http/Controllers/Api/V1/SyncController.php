<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\College;
use App\Models\Building;
use App\Models\Classroom;

class SyncController extends Controller
{
    public function bulkSync(Request $request)
    {
        // التحقق من صحة البيانات القادمة
        $request->validate([
            'colleges' => 'array',
            'buildings' => 'array',
            'classrooms' => 'array',
        ]);

        // مصفوفات لتخزين الاستجابة (الربط بين الـ ID المحلي والجديد)
        $responseMap = [
            'colleges' => [],
            'buildings' => [],
            'classrooms' => []
        ];

        // خرائط مؤقتة للربط الداخلي أثناء العملية
        // local_id => server_id
        $collegeMap = [];
        $buildingMap = [];

        return DB::transaction(function () use ($request, &$responseMap, &$collegeMap, &$buildingMap) {
            
            // 1. معالجة الكليات (Colleges)
            if ($request->has('colleges')) {
                foreach ($request->colleges as $item) {
                    // إنشاء الكلية
                    $college = College::create([
                        'college_name' => $item['name_ar'], // تأكد من تطابق أسماء الأعمدة مع قاعدة بياناتك
                        // 'college_name_en' => $item['name_en'], // إذا كان لديك عمود للاسم الإنجليزي
                        // أضف باقي الحقول الضرورية هنا
                    ]);

                    // تخزين الربط
                    $collegeMap[$item['local_id']] = $college->college_id;
                    $responseMap['colleges'][] = [
                        'local_id' => $item['local_id'],
                        'server_id' => $college->college_id
                    ];
                }
            }

            // 2. معالجة المباني (Buildings)
            if ($request->has('buildings')) {
                foreach ($request->buildings as $item) {
                    // محاولة العثور على معرف الكلية الحقيقي
                    // إما من الخريطة (إذا تم إنشاؤها للتو) أو استخدام القيمة المرسلة مباشرة
                    $collegeId = null;
                    if (isset($item['college_ref']) && isset($collegeMap[$item['college_ref']])) {
                        $collegeId = $collegeMap[$item['college_ref']];
                    } elseif (isset($item['college_id'])) {
                        $collegeId = $item['college_id'];
                    }

                    if (!$collegeId) continue; // تخطي إذا لم نجد أب للمبنى

                    $building = Building::create([
                        'building_name' => $item['name_ar'],
                        'college_id' => $collegeId,
                        'code' => $item['code'] ?? null,
                        // 'floors_count' => ... قد تحتاج لتحديد قيمة افتراضية أو إرسالها
                    ]);

                    $buildingMap[$item['local_id']] = $building->building_id;
                    $responseMap['buildings'][] = [
                        'local_id' => $item['local_id'],
                        'server_id' => $building->building_id
                    ];
                }
            }

            // 3. معالجة القاعات (Classrooms)
            if ($request->has('classrooms')) {
                foreach ($request->classrooms as $item) {
                    $buildingId = null;
                    if (isset($item['building_ref']) && isset($buildingMap[$item['building_ref']])) {
                        $buildingId = $buildingMap[$item['building_ref']];
                    } elseif (isset($item['building_id'])) {
                        $buildingId = $item['building_id'];
                    }

                    if (!$buildingId) continue;

                    $classroom = Classroom::create([
                        'classroom_name' => $item['code'], // أو name_ar حسب المخطط
                        'building_id' => $buildingId,
                        'classroom_type' => ($item['type'] === 'LAB') ? 1 : 0, // تحويل النوع إلى رقم حسب نظامك
                        'capacity' => $item['capacity'],
                        'floor' => $item['floor'],
                        'latitude' => $item['lat'],
                        'longitude' => $item['lng'],
                        'allowed_distance' => $item['range'],
                    ]);

                    $responseMap['classrooms'][] = [
                        'local_id' => $item['local_id'],
                        'server_id' => $classroom->classroom_id
                    ];
                }
            }

            return response()->json($responseMap, 201);
        });
    }
}