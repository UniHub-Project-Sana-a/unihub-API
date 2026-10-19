<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CourseTopic;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CourseTopicController extends Controller
{
    /**
     * عرض جميع المواضيع
     * GET /api/v1/courses/{course_id}/topics
     */
    public function index($courseId, Request $request): JsonResponse
    {
        try {
            $course = Course::findOrFail($courseId);

            $query = CourseTopic::where('course_id', $courseId);

            // فلترة حسب الجزء
            if ($request->has('part')) {
                $query->where('part', $request->part);
            }

            // فلترة حسب نوع (امتحان/عادي)
            if ($request->has('is_exam')) {
                $query->where('is_exam', $request->boolean('is_exam'));
            }

            $topics = $query->orderBy('part')
                ->orderBy('week')
                ->get();

            // حساب الإجماليات
            $summary = [];
            foreach (['نظري', 'عملي', 'تمارين', 'سريري'] as $part) {
                $partTopics = $topics->where('part', $part);
                if ($partTopics->isNotEmpty()) {
                    $summary[$part] = [
                        'count' => $partTopics->count(),
                        'total_hours' => $partTopics->sum('hours'),
                        'weeks' => $partTopics->pluck('week')->unique()->count(),
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'course' => [
                    'course_id' => $course->course_id,
                    'course_name' => $course->course_name,
                ],
                'topics' => $topics,
                'summary' => $summary,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * إنشاء موضوع جديد
     * POST /api/v1/courses/{course_id}/topics
     */
    public function store(Request $request, $courseId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'part' => 'required|in:نظري,عملي,تمارين,سريري',
                'week' => 'required|integer|min:1|max:16',
                'unit_name' => 'required|string|max:300',
                'subtopics' => 'nullable|array',
                'subtopics.*' => 'string',
                'is_exam' => 'boolean',
                'exam_type' => 'nullable|in:midterm,final',
                'hours' => 'required|numeric|min:0.5',
                'clo_ids' => 'nullable|array',
                'clo_ids.*' => 'string',
                'notes' => 'nullable|string',
            ], $this->getArabicMessages());

            $course = Course::findOrFail($courseId);

            $configuredPart = collect($course->course_parts ?? [])
                ->firstWhere('name', $validated['part']);
            if (!$configuredPart) {
                return response()->json(['success' => false, 'message' => 'هذا الجزء غير موجود في المقرر'], 422);
            }

            $weekLimit = $validated['part'] === 'نظري' ? 16 : 15;
            if ($validated['week'] === $weekLimit && ($validated['exam_type'] ?? null) !== 'final') {
                return response()->json(['success' => false, 'message' => "الأسبوع {$weekLimit} محجوز للاختبار النهائي ولا يقبل موضوعات"], 422);
            }
            if (($validated['exam_type'] ?? null) === 'final' && $validated['week'] !== $weekLimit) {
                return response()->json(['success' => false, 'message' => "الاختبار النهائي يجب أن يكون في الأسبوع {$weekLimit}"], 422);
            }

            $actualHours = (float) ($configuredPart['actual_hours'] ?? $configuredPart['total_hours'] ?? 0);
            $hours = in_array($validated['part'], ['عملي', 'تمارين'], true)
                ? $actualHours / 2
                : ($validated['part'] === 'سريري' ? $actualHours / 3 : $actualHours);

            // التحقق من عدم تكرار نفس الموضوع في نفس الأسبوع والجزء
            $exists = CourseTopic::where('course_id', $courseId)
                ->where('part', $validated['part'])
                ->where('week', $validated['week'])
                ->where('unit_name', $validated['unit_name'])
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذا الموضوع موجود بالفعل في هذا الأسبوع والجزء',
                ], 422);
            }

            // التحقق من أن الامتحان لا يحتوي على مواضيع فرعية
            if ($validated['is_exam'] && !empty($validated['subtopics'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'الامتحان لا يحتوي على مواضيع فرعية',
                ], 422);
            }

            $topic = CourseTopic::create([
                'course_id' => $courseId,
                'part' => $validated['part'],
                'week' => $validated['week'],
                'unit_name' => $validated['unit_name'],
                'subtopics' => $validated['subtopics'] ?? [],
                'is_exam' => $validated['is_exam'] ?? false,
                'exam_type' => $validated['exam_type'] ?? null,
                'hours' => $hours,
                'clo_ids' => $validated['clo_ids'] ?? [],
                'notes' => $validated['notes'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الموضوع بنجاح',
                'data' => $topic,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في التحقق',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * عرض موضوع محدد مع أسئلته
     * GET /api/v1/courses/{course_id}/topics/{topic_id}
     */
    public function show($courseId, $topicId): JsonResponse
    {
        try {
            $topic = CourseTopic::where('course_id', $courseId)
                ->where('topic_id', $topicId)
                ->with('questions')
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $topic,
                'questions_count' => $topic->questions->count(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'الموضوع غير موجود',
            ], 404);
        }
    }

    /**
     * تحديث موضوع
     * PUT /api/v1/courses/{course_id}/topics/{topic_id}
     */
    public function update(Request $request, $courseId, $topicId): JsonResponse
    {
        try {
            $topic = CourseTopic::where('course_id', $courseId)
                ->where('topic_id', $topicId)
                ->firstOrFail();

            $validated = $request->validate([
                'week' => 'integer|min:1|max:16',
                'unit_name' => 'string|max:300',
                'subtopics' => 'nullable|array',
                'hours' => 'numeric|min:0.5',
                'clo_ids' => 'nullable|array',
                'notes' => 'nullable|string',
            ], $this->getArabicMessages());

            $course = Course::findOrFail($courseId);
            $weekLimit = $topic->part === 'نظري' ? 16 : 15;
            if (($validated['exam_type'] ?? $topic->exam_type) === 'final' && ($validated['week'] ?? $topic->week) !== $weekLimit) {
                return response()->json(['success' => false, 'message' => "الاختبار النهائي يجب أن يكون في الأسبوع {$weekLimit}"], 422);
            }
            $configuredPart = collect($course->course_parts ?? [])->firstWhere('name', $topic->part);
            if ($configuredPart) {
                $actualHours = (float) ($configuredPart['actual_hours'] ?? $configuredPart['total_hours'] ?? 0);
                $validated['hours'] = in_array($topic->part, ['عملي', 'تمارين'], true)
                    ? $actualHours / 2
                    : ($topic->part === 'سريري' ? $actualHours / 3 : $actualHours);
            }

            $topic->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الموضوع بنجاح',
                'data' => $topic,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * حذف موضوع
     * DELETE /api/v1/courses/{course_id}/topics/{topic_id}
     */
    public function destroy($courseId, $topicId): JsonResponse
    {
        try {
            $topic = CourseTopic::where('course_id', $courseId)
                ->where('topic_id', $topicId)
                ->firstOrFail();

            // التحقق من وجود أسئلة
            if ($topic->questions()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف موضوع يحتوي على أسئلة',
                ], 422);
            }

            $topic->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الموضوع بنجاح',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function getArabicMessages()
    {
        return [
            'part.required' => 'الجزء مطلوب',
            'week.required' => 'الأسبوع مطلوب',
            'week.min' => 'الأسبوع يجب أن يكون 1 على الأقل',
            'week.max' => 'الأسبوع يجب ألا يزيد عن 16',
            'unit_name.required' => 'اسم الوحدة مطلوب',
            'hours.required' => 'الساعات مطلوبة',
            'hours.min' => 'الساعات يجب أن تكون 0.5 على الأقل',
        ];
    }
}