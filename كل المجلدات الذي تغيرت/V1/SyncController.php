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
     * خريطة تحويل نوع القاعة من النص العربي/الإنجليزي القادم من تطبيق الموبايل إلى قيمة enum الرقمية بقاعدة البيانات.
     */
    private const TYPE_MAP = [
        'معمل' => 1,
        'قاعة' => 0,
        'مدرج' => 2,
        'مكتبة' => 3,
        'ورشة' => 4,
        'LAB' => 1,
        'CLASSROOM' => 0,
        'AUDITORIUM' => 2,
        'LIBRARY' => 3,
        'WORKSHOP' => 4,
    ];

    private const DISPLAY_TYPES = ['none', 'screen', 'projector', 'smart_board'];

    /**
     * كلمات تدل على أصل عام/مشترك بالحرم الجامعي لا يتبع كلية بعينها.
     */
    private const GENERAL_KEYWORDS = ['عام', 'shared', 'general', 'مشترك'];

    /**
     * يحاول مطابقة/إنشاء الكلية من اسمها النصي، مع رفض القيم الرقمية (خطأ ربط بيانات) والتعامل مع الكلمات العامة كأصل مشترك بلا كلية.
     *
     * @return array{ok: bool, college_id: int|null}
     */
    private function resolveCollegeId($collegeNameRaw, $legacyCollegeId, string $roomLabel, array &$summary): array
    {
        // الحقل غير موجود إطلاقاً: نعتمد على معرف الكلية القديم إن كان صالحاً
        if ($collegeNameRaw === null) {
            if (!empty($legacyCollegeId) && College::where('college_id', $legacyCollegeId)->exists()) {
                return ['ok' => true, 'college_id' => $legacyCollegeId];
            }
            return ['ok' => true, 'college_id' => null];
        }

        $raw = trim((string)$collegeNameRaw);
        $normalized = mb_strtolower($raw);

        // قيمة فارغة أو كلمة عامة: أصل مشترك للحرم الجامعي بلا كلية محددة
        if ($raw === '' || in_array($normalized, self::GENERAL_KEYWORDS, true)) {
            return ['ok' => true, 'college_id' => null];
        }

        if (is_numeric($raw)) {
            $summary['skipped_invalid_college_mapping']++;
            $summary['errors'][] = [
                'room_code' => $roomLabel,
                'error_type' => 'Invalid Data Mapping',
                'message' => "اسم الكلية غير صالح (يظهر كرقم: {$raw}). يرجى إبلاغ مطور التطبيق بتعديل حقل collegeName ليرسل اسم الكلية الفعلي بدلاً من رقمها.",
                'dev_note' => 'Field collegeName is numeric. Expected string. Check Mobile JSON Export Mapping.',
            ];
            return ['ok' => false, 'college_id' => null];
        }

        $college = College::firstOrCreate(['college_name' => $raw]);
        return ['ok' => true, 'college_id' => $college->college_id];
    }

    /**
     * تنفيذ مزامنة مجمّعة (إنشاء أو تحديث) لمباني وقاعات مسحوبة من ملف JSON الميداني.
     * تعتمد قاعدة المزامنة على `remote_id` (uuid القاعة بتطبيق الموبايل) لمنع تكرار السجلات،
     * وعلى `building_code` لمطابقة/إنشاء المباني.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkSync(Request $request)
    {
        $request->validate([
            'buildings' => ['array'],
            'buildings.*.local_id' => ['required'],
            'buildings.*.code' => ['nullable', 'string'],
            'buildings.*.name_ar' => ['nullable', 'string'],
            'classrooms' => ['array'],
            'classrooms.*.id' => ['nullable', 'string'],
            'classrooms.*.code' => ['nullable', 'string'],
            'scope_college_id' => ['nullable', 'integer'],
        ]);

        $scopeCollegeId = $request->input('scope_college_id');

        $summary = [
            'imported_classrooms' => 0,
            'skipped_test_data' => 0,
            'skipped_unapproved_buildings' => 0,
            'skipped_scope' => 0,
            'skipped_invalid_college_mapping' => 0,
            'errors' => [],
            'message' => '',
        ];

        return DB::transaction(function () use ($request, $scopeCollegeId, &$summary) {
            $buildingMap = []; // local_id (من الملف) => building_id الفعلي بقاعدة البيانات

            foreach ($request->input('buildings', []) as $item) {
                $code = trim((string)($item['code'] ?? ''));
                $name = trim((string)($item['name_ar'] ?? $code));
                $label = $name !== '' ? $name : ($code !== '' ? $code : 'مبنى غير مسمى');

                if ($code === '' && $name === '') {
                    continue;
                }

                $collegeNameRaw = $item['collegeName'] ?? $item['college_name'] ?? null;
                $resolved = $this->resolveCollegeId($collegeNameRaw, $item['college_id'] ?? null, $label, $summary);
                if (!$resolved['ok']) {
                    continue;
                }

                // مفتاح المطابقة الفريد هو building_code، مع الرجوع لاسم المبنى في حال غياب الكود
                $buildingCode = $code !== '' ? $code : $name;

                try {
                    $building = Building::updateOrCreate(
                        ['building_code' => $buildingCode],
                        [
                            'building_name' => $name !== '' ? $name : $code,
                            'college_id' => $resolved['college_id'],
                            'floors_count' => $item['floors_count'] ?? 1,
                        ]
                    );
                } catch (\Throwable $e) {
                    $summary['errors'][] = [
                        'room_code' => $label,
                        'error_type' => 'Database Error',
                        'message' => "تعذّر حفظ بيانات المبنى بسبب خطأ في قاعدة البيانات: {$e->getMessage()}",
                        'dev_note' => get_class($e) . ': ' . $e->getMessage(),
                    ];
                    continue;
                }

                $buildingMap[$item['local_id']] = $building->building_id;
            }

            foreach ($request->input('classrooms', []) as $item) {
                $remoteId = trim((string)($item['id'] ?? $item['remote_id'] ?? ''));
                $name = trim((string)($item['code'] ?? $item['classroom_name'] ?? ''));
                $roomLabel = $name !== '' ? $name : ($remoteId !== '' ? $remoteId : 'قاعة غير مسماة');

                // بيانات ناقصة أو تجريبية بلا معرف فريد أو اسم قاعة تُستبعد لضمان سلامة البيانات
                if ($remoteId === '' || $name === '') {
                    $summary['skipped_test_data']++;
                    continue;
                }

                $localBuildingKey = $item['building_id'] ?? null;
                $buildingId = $buildingMap[$localBuildingKey] ?? $localBuildingKey;

                if (!$buildingId || !Building::where('building_id', $buildingId)->exists()) {
                    $summary['skipped_unapproved_buildings']++;
                    continue;
                }

                // مرحلة التحقق ومطابقة/إنشاء الكلية بالاسم الفعلي، مع دعم الأصول العامة/المشتركة
                $collegeNameRaw = $item['collegeName'] ?? $item['college_name'] ?? null;
                $resolved = $this->resolveCollegeId($collegeNameRaw, $item['college_id'] ?? null, $roomLabel, $summary);
                if (!$resolved['ok']) {
                    continue;
                }
                $collegeId = $resolved['college_id'];

                // استبعاد القاعات خارج نطاق الكلية الحالية عند وجود سياق مزامنة محدد
                if ($scopeCollegeId && !empty($collegeId) && (int)$collegeId !== (int)$scopeCollegeId) {
                    $summary['skipped_scope']++;
                    continue;
                }

                $rawType = $item['type'] ?? $item['classroom_type'] ?? 'قاعة';
                $classroomType = is_numeric($rawType) ? (int)$rawType : (self::TYPE_MAP[$rawType] ?? 0);

                $displayType = $item['display_type'] ?? $item['displayType'] ?? 'none';
                if (!in_array($displayType, self::DISPLAY_TYPES, true)) {
                    $displayType = 'none';
                }

                try {
                    Classroom::updateOrCreate(
                        ['remote_id' => $remoteId],
                        [
                            'classroom_name' => $name,
                            'building_id' => $buildingId,
                            'college_id' => $collegeId,
                            'floor' => $item['floor'] ?? 0,
                            'capacity' => $item['capacity'] ?? 0,
                            'latitude' => $item['lat'] ?? $item['latitude'] ?? null,
                            'longitude' => $item['lng'] ?? $item['longitude'] ?? null,
                            // المسافة المسموحة تُستخرج من حقل range بملف الموبايل
                            'allowed_distance' => $item['range'] ?? $item['allowed_distance'] ?? null,
                            'classroom_type' => $classroomType,
                            'windows_count' => $item['windows_count'] ?? $item['windowsCount'] ?? 0,
                            'has_computer' => (bool)($item['has_computer'] ?? $item['hasComputer'] ?? false),
                            'display_type' => $displayType,
                            'notes' => $item['notes'] ?? null,
                            'location_address' => $item['address'] ?? $item['location_address'] ?? null,
                        ]
                    );
                } catch (\Throwable $e) {
                    // مثل خطأ 1062 عند تكرار مفتاح فريد آخر (مثل اسم القاعة بنفس الدور والمبنى)
                    $summary['errors'][] = [
                        'room_code' => $roomLabel,
                        'error_type' => 'Database Error',
                        'message' => "تعذّر حفظ القاعة بسبب خطأ في قاعدة البيانات، يُرجى مراجعة تكرار البيانات: {$e->getMessage()}",
                        'dev_note' => get_class($e) . ': ' . $e->getMessage(),
                    ];
                    continue;
                }

                $summary['imported_classrooms']++;
            }

            $summary['message'] = "تمت مزامنة {$summary['imported_classrooms']} قاعة بنجاح.";
            if (count($summary['errors']) > 0) {
                $summary['message'] .= " تم رفض " . count($summary['errors']) . " قاعة بسبب أخطاء تقنية في البيانات.";
            }

            return response()->json(['summary' => $summary], 201);
        });
    }
}