<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CourseDescription;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CourseDescriptionController extends Controller
{
    /**
     * عرض وصف المقرر
     * GET /api/v1/courses/{course_id}/description
     */
    public function show($courseId): JsonResponse
    {
        try {
            Course::findOrFail($courseId);
            $description = CourseDescription::where('course_id', $courseId)->first();

            // ✅ تأكد من صيغة الـ response
            if ($description) {
                return response()->json([
                    'success' => true,
                    'description' => [
                        'description' => $description->description ?? '', // ✅ string
                        'goals' => $description->goals ?? [],              // ✅ array
                        'word_count' => $description->word_count ?? 0,
                        'goals_count' => $description->goals_count ?? 0,
                    ],
                ], 200);
            } else {
                return response()->json([
                    'success' => true,
                    'description' => null, // ✅ لا توجد بيانات
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * تحديث الوصف
     * PUT /api/v1/courses/{course_id}/description
     */
    public function updateDescription(Request $request, $courseId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'description' => 'required|string',
            ], [
                'description.required' => 'الوصف مطلوب',
                'description.min' => 'الوصف يجب أن يكون 80 كلمة على الأقل',
                'description.max' => 'الوصف يجب ألا يتجاوز 500 كلمة',
            ]);

            Course::findOrFail($courseId);

            $description = trim($validated['description']);
            $wordCount = count(preg_split('/\s+/u', $description, -1, PREG_SPLIT_NO_EMPTY));
            $sentences = array_values(array_filter(preg_split('/[.!؟]+/u', $description)));
            $prefixes = [
                'يهدف هذا المقرر إلى',
                'ويغطي هذا المقرر',
                'ويركز هذا المقرر على',
                'ويعتمد هذا المقرر',
            ];

            if ($wordCount < 80 || $wordCount > 100) {
                return response()->json(['success' => false, 'message' => 'الوصف يجب أن يكون بين 80 و100 كلمة'], 422);
            }
            if (count($sentences) !== 4 || count(array_filter($prefixes, fn ($prefix, $i) => !str_starts_with(trim($sentences[$i] ?? ''), $prefix), ARRAY_FILTER_USE_BOTH)) > 0) {
                return response()->json(['success' => false, 'message' => 'الوصف يجب أن يتكون من أربع جمل بالترتيب المحدد'], 422);
            }

            $desc = CourseDescription::firstOrCreate(['course_id' => $courseId]);
            $desc->description = $description;
            $desc->calculateWordCount();
            $desc->is_completed = (
                !empty($desc->description) &&
                $desc->isValidWordCount() &&
                !empty($desc->goals) &&
                $desc->isValidGoalsCount()
            );
            $desc->save();

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ الوصف',
                'description' => $desc->description
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحديث الأهداف
     * PUT /api/v1/courses/{course_id}/goals
     */
    public function updateGoals(Request $request, $courseId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'goals' => 'required|array|min:4|max:6',
                'goals.*' => 'required|string|min:3',
            ], [
                'goals.required' => 'الأهداف مطلوبة',
                'goals.min' => 'يجب إضافة 4 أهداف على الأقل',
                'goals.max' => 'لا يمكن إضافة أكثر من 6 أهداف',
            ]);

            foreach ($validated['goals'] as $index => $goal) {
                $words = preg_split('/\s+/u', trim($goal), -1, PREG_SPLIT_NO_EMPTY);
                if (count($words) < 4) {
                    return response()->json([
                        'success' => false,
                        'message' => 'الهدف رقم ' . ($index + 1) . ' يجب أن يتكون من أربع كلمات على الأقل',
                    ], 422);
                }
            }

            Course::findOrFail($courseId);

            $desc = CourseDescription::firstOrCreate(['course_id' => $courseId]);
            $desc->goals = $validated['goals'];
            $desc->goals_count = count($validated['goals']);
            $desc->is_completed = (
                !empty($desc->description) &&
                $desc->isValidWordCount() &&
                !empty($desc->goals) &&
                $desc->isValidGoalsCount()
            );
            $desc->save();

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ الأهداف',
                'goals' => $desc->goals
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}