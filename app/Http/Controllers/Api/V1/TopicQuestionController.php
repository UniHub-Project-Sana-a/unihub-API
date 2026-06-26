<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TopicQuestion;
use App\Models\CourseTopic;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TopicQuestionController extends Controller
{
    /**
     * عرض جميع الأسئلة في موضوع
     * GET /api/v1/topics/{topic_id}/questions
     */
    public function index($topicId, Request $request): JsonResponse
    {
        try {
            $topic = CourseTopic::findOrFail($topicId);

            $query = TopicQuestion::where('topic_id', $topicId);

            // فلترة حسب نوع السؤال
            if ($request->has('question_type')) {
                $query->where('question_type', $request->question_type);
            }

            // فلترة حسب مستوى الصعوبة
            if ($request->has('difficulty_level')) {
                $query->where('difficulty_level', $request->difficulty_level);
            }

            // فلترة الأسئلة النشطة
            if ($request->has('active_only') && $request->boolean('active_only')) {
                $query->where('is_active', true);
            }

            $questions = $query->orderBy('order')->get();

            return response()->json([
                'success' => true,
                'topic' => [
                    'topic_id' => $topic->topic_id,
                    'unit_name' => $topic->unit_name,
                ],
                'questions' => $questions,
                'total_count' => $questions->count(),
                'mcq_count' => $questions->where('question_type', 'MCQ')->count(),
                'essay_count' => $questions->where('question_type', 'essay')->count(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * إنشاء سؤال جديد
     * POST /api/v1/topics/{topic_id}/questions
     */
    public function store(Request $request, $topicId): JsonResponse
    {
        try {
            $topic = CourseTopic::findOrFail($topicId);

            $validated = $request->validate([
                'subtopic' => 'nullable|string|max:300',
                'question_text' => 'required|string|min:10',
                'question_type' => 'required|in:MCQ,essay',
                'difficulty_level' => 'integer|min:1|max:5',
                'clo_code' => 'nullable|string|max:10',
                'options' => 'required_if:question_type,MCQ|array|size:4',
                'options.*.id' => 'required|string',
                'options.*.text' => 'required|string|min:2',
                'options.*.is_correct' => 'required|boolean',
                'correct_answer' => 'required_if:question_type,essay|string',
            ], $this->getArabicMessages());

            // التحقق من خيارات MCQ
            if ($validated['question_type'] === 'MCQ') {
                $correctCount = collect($validated['options'])
                    ->where('is_correct', true)
                    ->count();

                if ($correctCount !== 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'يجب أن يكون هناك خيار واحد صحيح فقط',
                    ], 422);
                }
            }

            $question = TopicQuestion::create([
                'topic_id' => $topicId,
                'subtopic' => $validated['subtopic'] ?? null,
                'question_text' => $validated['question_text'],
                'question_type' => $validated['question_type'],
                'difficulty_level' => $validated['difficulty_level'] ?? 1,
                'clo_code' => $validated['clo_code'] ?? null,
                'options' => $validated['question_type'] === 'MCQ' ? $validated['options'] : null,
                'correct_answer' => $validated['question_type'] === 'essay' ? $validated['correct_answer'] : null,
                'is_active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء السؤال بنجاح',
                'data' => $question,
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
     * عرض سؤال محدد
     * GET /api/v1/topics/{topic_id}/questions/{question_id}
     */
    public function show($topicId, $questionId): JsonResponse
    {
        try {
            $question = TopicQuestion::where('topic_id', $topicId)
                ->where('question_id', $questionId)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $question,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'السؤال غير موجود',
            ], 404);
        }
    }

    /**
     * تحديث سؤال
     * PUT /api/v1/topics/{topic_id}/questions/{question_id}
     */
    public function update(Request $request, $topicId, $questionId): JsonResponse
    {
        try {
            $question = TopicQuestion::where('topic_id', $topicId)
                ->where('question_id', $questionId)
                ->firstOrFail();

            $validated = $request->validate([
                'subtopic' => 'nullable|string',
                'question_text' => 'string|min:10',
                'difficulty_level' => 'integer|min:1|max:5',
                'clo_code' => 'nullable|string',
                'options' => 'array|size:4',
                'correct_answer' => 'nullable|string',
                'is_active' => 'boolean',
            ], $this->getArabicMessages());

            // التحقق من خيارات MCQ إذا تم تحديثها
            if (isset($validated['options'])) {
                $correctCount = collect($validated['options'])
                    ->where('is_correct', true)
                    ->count();

                if ($correctCount !== 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'يجب أن يكون هناك خيار واحد صحيح فقط',
                    ], 422);
                }
            }

            $question->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث السؤال بنجاح',
                'data' => $question,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * حذف سؤال
     * DELETE /api/v1/topics/{topic_id}/questions/{question_id}
     */
    public function destroy($topicId, $questionId): JsonResponse
    {
        try {
            $question = TopicQuestion::where('topic_id', $topicId)
                ->where('question_id', $questionId)
                ->firstOrFail();

            $question->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف السؤال بنجاح',
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
            'question_text.required' => 'نص السؤال مطلوب',
            'question_text.min' => 'نص السؤال يجب أن يكون 10 أحرف على الأقل',
            'question_type.required' => 'نوع السؤال مطلوب',
            'options.required_if' => 'يجب تحديد 4 خيارات لسؤال MCQ',
            'options.size' => 'يجب أن يكون هناك 4 خيارات بالضبط',
        ];
    }
}