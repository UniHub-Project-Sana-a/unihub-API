<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\College;
use App\Models\Building;
use App\Models\Classroom;

class SyncController extends Controller
{
    /**
     * تنفيذ مزامنة مجمّعة محصنة أمنياً (Scoped & Hardened Bulk Sync).
     * يستقبل بيانات المباني والقاعات والكليات ويقوم بالمعالجة الآمنة وفق الصلاحية.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkSync(Request $request)
    {
        $request->validate([
            'colleges' => 'array',
            'buildings' => 'array',
            'classrooms' => 'array',
        ]);

        $user = Auth::user();
        $userTypeCode = optional($user?->userType)->user_type_code;
        $isSuperAdmin = !$user || !$user->college_id || in_array($userTypeCode, ['admin', 'presidency']);
        $userCollegeId = $user?->college_id;

        $responseMap = [
            'colleges' => [],
            'buildings' => [],
            'classrooms' => [],
            'summary' => [
                'imported_classrooms' => 0,
                'skipped_test_data' => 0,
                'skipped_unapproved_buildings' => 0,
                'skipped_scope' => 0,
                'message' => ''
            ]
        ];

        $collegeMap = [];
        $buildingMap = [];

        return DB::transaction(function () use ($request, $user, $isSuperAdmin, $userCollegeId, &$responseMap, &$collegeMap, &$buildingMap) {
            
            // 1. معالجة الكليات (Colleges)
            if ($request->has('colleges')) {
                foreach ($request->colleges as $item) {
                    $nameAr = $item['name_ar'] ?? $item['name'] ?? null;
                    if (!$nameAr) continue;

                    // المشرف العام فقط يمكنه إنشاء كليات جديدة في المزامنة الميدانية
                    if ($isSuperAdmin) {
                        $college = College::updateOrCreate(['college_name' => $nameAr], []);
                        $cId = $college->college_id;
                    } else {
                        // مشرف الكلية يقتصر على كليته المعتمدة
                        $cId = $userCollegeId;
                    }

                    if (isset($item['local_id']) && $cId) {
                        $collegeMap[$item['local_id']] = $cId;
                    }
                    $responseMap['colleges'][] = [
                        'local_id' => $item['local_id'] ?? null,
                        'server_id' => $cId
                    ];
                }
            }

            // 2. معالجة المباني (Buildings) - حصانة الأصول المادية (Read-Only لمشرف الكلية)
            if ($request->has('buildings')) {
                foreach ($request->buildings as $item) {
                    $buildingName = $item['name_ar'] ?? $item['name'] ?? $item['building_name'] ?? null;
                    $code = $item['code'] ?? $buildingName;
                    if (!$buildingName) continue;

                    $building = null;

                    $hasBuildingCode = \Illuminate\Support\Facades\Schema::hasColumn('buildings', 'code');

                    if ($isSuperAdmin) {
                        // المشرف العام يملك حق إنشاء وتحديث المباني المادية
                        $attributes = ['building_name' => $buildingName];
                        $values = [
                            'floors_count' => $item['floors_count'] ?? $item['floors'] ?? 5,
                        ];
                        if ($hasBuildingCode) {
                            $values['code'] = $code;
                        }
                        if (isset($item['college_id'])) {
                            $values['college_id'] = $item['college_id'];
                        }
                        $building = Building::updateOrCreate($attributes, $values);
                    } else {
                        // مشرف الكلية: مطابقة المبنى الموجود فقط (Read-Only) دون التعديل على اسمه أو كوده
                        $bQuery = Building::where('building_name', $buildingName);
                        if ($hasBuildingCode) {
                            $bQuery->orWhere('code', $code);
                        }
                        $building = $bQuery->first();

                        if (!$building) {
                            // إذا لم يكن المبنى معتمداً مسبقاً، لا يتم إنشاؤه من قِبل مشرف الكلية
                            $responseMap['summary']['skipped_unapproved_buildings']++;
                            continue;
                        }
                    }

                    if ($building && isset($item['local_id'])) {
                        $buildingMap[$item['local_id']] = $building->building_id;
                        $responseMap['buildings'][] = [
                            'local_id' => $item['local_id'],
                            'server_id' => $building->building_id
                        ];
                    }
                }
            }

            // 3. معالجة القاعات (Classrooms) - التصفية الجزئية الذكية وفرض الصلاحية
            if ($request->has('classrooms')) {
                foreach ($request->classrooms as $item) {
                    
                    // أ) توحيد مسميات الحقول
                    $roomCodeOrName = $item['code'] ?? $item['classroom_name'] ?? $item['name'] ?? null;
                    $floor = isset($item['floor']) ? (int)$item['floor'] : 0;
                    $capacity = isset($item['capacity']) ? (int)$item['capacity'] : 0;

                    // ب) مصفاة البيانات الضوضائية (Data Ingestion Filter)
                    if (!$roomCodeOrName) {
                        $responseMap['summary']['skipped_test_data']++;
                        continue;
                    }
                    if (mb_strpos($roomCodeOrName, 'تجربة') !== false || mb_strpos($roomCodeOrName, 'اختبار') !== false || str_contains(strtolower($roomCodeOrName), 'test')) {
                        $responseMap['summary']['skipped_test_data']++;
                        continue;
                    }
                    if ($floor > 20 || $floor < -2) {
                        $responseMap['summary']['skipped_test_data']++;
                        continue;
                    }

                    // ج) ربط المبنى المادي وتأكيد وجوده
                    $buildingId = null;
                    if (isset($item['building_ref']) && isset($buildingMap[$item['building_ref']])) {
                        $buildingId = $buildingMap[$item['building_ref']];
                    } elseif (isset($item['building_id'])) {
                        $buildingId = $item['building_id'];
                    } elseif (isset($item['buildingId'])) {
                        $buildingId = $item['buildingId'];
                    }

                    // إذا تعذر العثور على المبنى (أو لم يكن معتمداً لمشرف الكلية)
                    if (!$buildingId) {
                        $bSearch = Building::where('building_id', $item['building_id'] ?? 0);
                        if ($hasBuildingCode && isset($item['building_code'])) {
                            $bSearch->orWhere('code', $item['building_code']);
                        }
                        $buildingId = $bSearch->value('building_id');
                    }

                    if (!$buildingId) {
                        $responseMap['summary']['skipped_unapproved_buildings']++;
                        continue;
                    }

                    // د) تحديد وتأمين تبعية الكلية (Spoofing Prevention)
                    $itemCollegeId = $item['collegeId'] ?? $item['college_id'] ?? null;

                    if ($isSuperAdmin) {
                        // المشرف العام: يعتمد الكلية المحددة في ملف القاعة أو كليته
                        $effectiveCollegeId = $itemCollegeId ?? $userCollegeId;
                    } else {
                        // مشرف الكلية: إذا أرسل قاعة تخص كلية أخرى، نثبتها كـ skipped_scope
                        if ($itemCollegeId && (int)$itemCollegeId !== (int)$userCollegeId) {
                            $responseMap['summary']['skipped_scope']++;
                            continue;
                        }
                        // فرض الكلية قسرياً من جلسة المستخدم الحالية لـ حماية النظام من التلاعب
                        $effectiveCollegeId = $userCollegeId;
                    }

                    // هـ) المزامنة المقاومة للتكرار (Idempotent Sync)
                    $uuid = $item['id'] ?? $item['uuid'] ?? null;
                    $windowsCount = $item['windows_count'] ?? $item['windowsCount'] ?? 0;
                    $hasComputer = $item['has_computer'] ?? $item['hasComputer'] ?? false;
                    $displayType = $item['display_type'] ?? $item['displayType'] ?? 'none';
                    $lat = $item['lat'] ?? $item['latitude'] ?? 0.0;
                    $lng = $item['lng'] ?? $item['longitude'] ?? 0.0;
                    $range = $item['range'] ?? $item['allowed_distance'] ?? 50.0;

                    $rawType = strtoupper((string)($item['type'] ?? $item['classroom_type'] ?? ''));
                    $typeInt = match(true) {
                        str_contains($rawType, 'LAB') || str_contains($rawType, 'معمل') => 1,
                        str_contains($rawType, 'AUDITORIUM') || str_contains($rawType, 'مدرج') => 2,
                        str_contains($rawType, 'LIBRARY') || str_contains($rawType, 'مكتبة') => 3,
                        str_contains($rawType, 'WORKSHOP') || str_contains($rawType, 'ورشة') => 4,
                        default => 0,
                    };

                    $attributes = $uuid ? ['uuid' => $uuid] : ['classroom_name' => $roomCodeOrName, 'building_id' => $buildingId];

                    $values = [
                        'uuid' => $uuid,
                        'classroom_name' => $roomCodeOrName,
                        'building_id' => $buildingId,
                        'college_id' => $effectiveCollegeId,
                        'classroom_type' => $typeInt,
                        'capacity' => $capacity,
                        'floor' => $floor,
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'allowed_distance' => $range,
                        'windows_count' => $windowsCount,
                        'has_computer' => $hasComputer ? 1 : 0,
                        'display_type' => $displayType,
                    ];

                    $classroom = Classroom::updateOrCreate($attributes, $values);
                    $responseMap['summary']['imported_classrooms']++;

                    $responseMap['classrooms'][] = [
                        'local_id' => $item['local_id'] ?? $uuid,
                        'server_id' => $classroom->classroom_id
                    ];
                }
            }

            // و) صياغة الرسالة الملخصة للنتيجة
            $imp = $responseMap['summary']['imported_classrooms'];
            $skipScope = $responseMap['summary']['skipped_scope'];
            $skipBld = $responseMap['summary']['skipped_unapproved_buildings'];
            $skipTest = $responseMap['summary']['skipped_test_data'];

            $msgParts = [];
            if ($imp > 0) $msgParts[] = "تم استيراد/تحديث {$imp} قاعة بنجاح";
            if ($skipScope > 0) $msgParts[] = "تم تجاوز {$skipScope} قاعة تتبع كليات أخرى";
            if ($skipBld > 0) $msgParts[] = "تم تجاوز {$skipBld} قاعة لمبانٍ غير معتمدة";
            if ($skipTest > 0) $msgParts[] = "تم تنقية {$skipTest} سجل تجريبي";

            $responseMap['summary']['message'] = empty($msgParts) ? "لم يتم إدخال تغييرات جديدة" : implode('، و', $msgParts) . '.';

            return response()->json($responseMap, 200);
            
        });
    }
}