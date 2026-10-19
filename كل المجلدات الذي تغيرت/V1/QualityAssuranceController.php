<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Course;
use App\Models\LearningOutcome;
use App\Models\CourseTopic;
use App\Models\QaQuestion;
use App\Models\QaQuestionOption;

class QualityAssuranceController extends Controller
{
    /**
     * جلب كافة بيانات الجودة لمادة معينة (للعرض في الداشبورد)
     * GET /api/v1/courses/{courseId}/qa-data
     */
    public function getCourseQaData($courseId)
    {
        $course = Course::find($courseId);
        if (!$course) {
            return response()->json(['status' => false, 'message' => 'المادة غير موجودة'], 404);
        }

        // 1. جلب المخرجات
        $outcomes = LearningOutcome::where('course_id', $courseId)->get()
            ->map(function($lo) {
                return [
                    'id' => $lo->outcome_id,
                    'name' => $lo->description, // الوصف هو الاسم في الواجهة
                    'code' => $lo->code,
                    'type' => $lo->domain
                ];
            });

        // 2. جلب المواضيع مع معرفات المخرجات المرتبطة بها
        $topics = CourseTopic::with('learningOutcomes:outcome_id') // Eager load pivot
            ->where('course_id', $courseId)
            ->orderBy('order_index')
            ->get()
            ->map(function($topic) {
                return [
                    'id' => $topic->topic_id,
                    'title' => $topic->title,
                    'description' => $topic->description,
                    'order_index' => $topic->order_index,
                    // استخراج IDs فقط للواجهة
                    'outcomeIds' => $topic->learningOutcomes->pluck('outcome_id')->toArray() 
                ];
            });

        // 3. جلب الأسئلة المرتبطة بالمواضيع التابعة لهذه المادة
        // نحتاج لجلب الأسئلة التي تتبع مواضيع هذه المادة
        $topicIds = $topics->pluck('id');
        $questions = QaQuestion::with('options')
            ->whereIn('topic_id', $topicIds)
            ->get()
            ->map(function($q) {
                return [
                    'id' => $q->question_id,
                    'text' => $q->question_text,
                    'type' => $q->question_type,
                    'difficulty' => $q->difficulty_level,
                    'topicId' => $q->topic_id,
                    'outcomeId' => $q->outcome_id,
                    'options' => $q->options->map(function($opt) {
                        return [
                            'id' => $opt->option_id,
                            'text' => $opt->option_text,
                            'isCorrect' => $opt->is_correct
                        ];
                    })
                ];
            });

        return response()->json([
            'status' => true,
            'data' => [
                'outcomes' => $outcomes,
                'topics' => $topics,
                'questions' => $questions
            ]
        ]);
    }

    // ==========================================
    // 1. Learning Outcomes CRUD
    // ==========================================

    public function storeOutcome(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => 'required|exists:courses,course_id',
            'code' => 'required|string|max:50',
            'name' => 'required|string', // description
            'type' => 'required|string', // domain
        ]);

        if ($validator->fails()) return response()->json(['errors' => $validator->errors()], 422);

        $lo = LearningOutcome::create([
            'course_id' => $request->course_id,
            'code' => $request->code,
            'description' => $request->name,
            'domain' => $request->type
        ]);

        return response()->json(['status' => true, 'message' => 'تم إضافة المخرج', 'data' => $lo]);
    }

    public function updateOutcome(Request $request, $id)
    {
        $lo = LearningOutcome::find($id);
        if (!$lo) return response()->json(['message' => 'غير موجود'], 404);

        $lo->update([
            'code' => $request->code,
            'description' => $request->name,
            'domain' => $request->type
        ]);

        return response()->json(['status' => true, 'message' => 'تم التحديث']);
    }

    public function destroyOutcome($id)
    {
        LearningOutcome::destroy($id);
        return response()->json(['status' => true, 'message' => 'تم الحذف']);
    }

    // ==========================================
    // 2. Topics CRUD
    // ==========================================

    public function storeTopic(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => 'required|exists:courses,course_id',
            'title' => 'required|string|max:200',
            'order_index' => 'integer',
            'outcomeIds' => 'array' // Array of outcome_ids to sync
        ]);

        if ($validator->fails()) return response()->json(['errors' => $validator->errors()], 422);

        $topic = CourseTopic::create([
            'course_id' => $request->course_id,
            'title' => $request->title,
            'description' => $request->description,
            'order_index' => $request->order_index ?? 0
        ]);

        // ربط الموضوع بمخرجات التعلم (Pivot Table)
        if ($request->has('outcomeIds')) {
            $topic->learningOutcomes()->sync($request->outcomeIds);
        }

        return response()->json(['status' => true, 'message' => 'تم إضافة الموضوع', 'data' => $topic]);
    }

    public function updateTopic(Request $request, $id)
    {
        $topic = CourseTopic::find($id);
        if (!$topic) return response()->json(['message' => 'غير موجود'], 404);

        $topic->update([
            'title' => $request->title,
            'description' => $request->description,
            'order_index' => $request->order_index
        ]);

        if ($request->has('outcomeIds')) {
            $topic->learningOutcomes()->sync($request->outcomeIds);
        }

        return response()->json(['status' => true, 'message' => 'تم التحديث']);
    }

    public function destroyTopic($id)
    {
        CourseTopic::destroy($id);
        return response()->json(['status' => true, 'message' => 'تم الحذف']);
    }

    // ==========================================
    // 3. Questions CRUD (Transactional)
    // ==========================================

    public function storeQuestion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'topicId' => 'required|exists:course_topics,topic_id',
            'text' => 'required|string',
            'difficulty' => 'required|integer|in:1,2,3',
            'options' => 'required|array|min:2', // على الأقل خيارين
            'options.*.text' => 'required|string',
            'options.*.isCorrect' => 'required|boolean',
        ]);

        if ($validator->fails()) return response()->json(['errors' => $validator->errors()], 422);

        // استخدام Transaction لضمان حفظ السؤال والخيارات معاً أو فشل العملية بالكامل
        DB::beginTransaction();
        try {
            $question = QaQuestion::create([
                'topic_id' => $request->topicId,
                'outcome_id' => $request->outcomeId, // Nullable
                'question_text' => $request->text,
                'question_type' => 'MCQ',
                'difficulty_level' => $request->difficulty,
                'is_active' => true
            ]);

            foreach ($request->options as $opt) {
                QaQuestionOption::create([
                    'question_id' => $question->question_id,
                    'option_text' => $opt['text'],
                    'is_correct' => $opt['isCorrect']
                ]);
            }

            DB::commit();
            return response()->json(['status' => true, 'message' => 'تم حفظ السؤال وخياراته']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'حدث خطأ أثناء الحفظ', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateQuestion(Request $request, $id)
    {
        $question = QaQuestion::find($id);
        if (!$question) return response()->json(['message' => 'غير موجود'], 404);

        DB::beginTransaction();
        try {
            $question->update([
                'topic_id' => $request->topicId, // في حال تغيير الموضوع
                'outcome_id' => $request->outcomeId,
                'question_text' => $request->text,
                'difficulty_level' => $request->difficulty,
            ]);

            // تحديث الخيارات: الأسهل هو حذف القديم وإضافة الجديد
            // (إلا إذا كنت تريد الحفاظ على IDs الخيارات لتاريخ إجابات الطلاب - هنا نفترض التعديل قبل بدء الدراسة)
            $question->options()->delete();

            foreach ($request->options as $opt) {
                QaQuestionOption::create([
                    'question_id' => $question->question_id,
                    'option_text' => $opt['text'],
                    'is_correct' => $opt['isCorrect']
                ]);
            }

            DB::commit();
            return response()->json(['status' => true, 'message' => 'تم التحديث']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'خطأ', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroyQuestion($id)
    {
        QaQuestion::destroy($id); // سيتم حذف الخيارات تلقائياً بفضل الـ Cascade في قاعدة البيانات
        return response()->json(['status' => true, 'message' => 'تم الحذف']);
    }
}