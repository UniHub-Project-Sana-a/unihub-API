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
    /**
     * تنفيذ مزامنة مجمّعة (إنشاء أو تحديث) للكيانات.
     * يستخدم updateOrCreate لتجنب تكرار السجلات والتعامل مع التحديثات.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkSync(Request $request)
    {
        // ... (الجزء الخاص بالـ validation يبقى كما هو) ...
        $request->validate([
            'colleges' => 'array',
            'buildings' => 'array',
            'classrooms' => 'array',
        ]);

        $responseMap = [
            'colleges' => [],
            'buildings' => [],
            'classrooms' => []
        ];

        $collegeMap = [];
        $buildingMap = [];

        return DB::transaction(function () use ($request, &$responseMap, &$collegeMap, &$buildingMap) {
            
            // 1. معالجة الكليات (Colleges) - إنشاء أو تحديث
            if ($request->has('colleges')) {
                foreach ($request->colleges as $item) {
                    $attributes = ['college_name' => $item['name_ar']];
                    $values = []; // أضف حقول التحديث الأخرى هنا إن وجدت
                    $college = College::updateOrCreate($attributes, $values);

                    $collegeMap[$item['local_id']] = $college->college_id;
                    $responseMap['colleges'][] = [
                        'local_id' => $item['local_id'],
                        'server_id' => $college->college_id
                    ];
                }
            }

            // 2. معالجة المباني (Buildings) - إنشاء أو تحديث
            if ($request->has('buildings')) {
                foreach ($request->buildings as $item) {
                    $collegeId = null;
                    if (isset($item['college_ref']) && isset($collegeMap[$item['college_ref']])) {
                        $collegeId = $collegeMap[$item['college_ref']];
                    } elseif (isset($item['college_id'])) {
                        $collegeId = $item['college_id'];
                    }

                    if (!$collegeId) continue; 

                    // ملاحظة: يُفترض أن 'code' موجود في جدول المباني Building
                    $attributes = [
                        'code' => $item['code'], 
                        'college_id' => $collegeId,
                    ];
                    
                    $values = [
                        'building_name' => $item['name_ar'],
                        // أضف حقول التحديث الأخرى هنا إن وجدت
                    ];

                    $building = Building::updateOrCreate($attributes, $values);

                    $buildingMap[$item['local_id']] = $building->building_id;
                    $responseMap['buildings'][] = [
                        'local_id' => $item['local_id'],
                        'server_id' => $building->building_id
                    ];
                }
            }

            // 3. معالجة القاعات (Classrooms) - الحل النهائي للـ updateOrCreate
            if ($request->has('classrooms')) {
                foreach ($request->classrooms as $item) {
                    
                    $buildingId = null;
                    if (isset($item['building_ref']) && isset($buildingMap[$item['building_ref']])) {
                        $buildingId = $buildingMap[$item['building_ref']];
                    } elseif (isset($item['building_id'])) {
                        $buildingId = $item['building_id'];
                    }

                    if (!$buildingId) continue;

                    // =========================================================
                    // تم التغيير: نستخدم classroom_name للمطابقة بدلاً من 'code'
                    // وذلك لأن عمود 'code' غير موجود في جدول classrooms حسب الصورة
                    // =========================================================

                    // المعايير للبحث (المفتاح الفريد):
                    $attributes = [
                        'classroom_name' => $item['code'],      // نستخدم قيمة 'code' القادمة لملء حقل 'classroom_name' للمطابقة
                        'building_id' => $buildingId,
                    ];

                    // البيانات التي سيتم تحديثها أو إضافتها:
                    $values = [
                        // 'classroom_name' تم إدراجه بالفعل في $attributes للمطابقة
                        'classroom_type' => ($item['type'] === 'LAB') ? 1 : 0, 
                        'capacity' => $item['capacity'],
                        'floor' => $item['floor'],
                        'latitude' => $item['lat'],
                        'longitude' => $item['lng'],
                        'allowed_distance' => $item['range'],
                    ];

                    // استخدام updateOrCreate: يبحث بـ $attributes، ويحدث/ينشئ بـ $values
                    $classroom = Classroom::updateOrCreate($attributes, $values);

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