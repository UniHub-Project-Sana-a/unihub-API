<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CoursePolicy;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CoursePolicyController extends Controller
{
    /**
     * عرض جميع الضوابط
     * GET /api/v1/courses/{course_id}/policies
     */
    public function index($courseId): JsonResponse
    {
        try {
            $course = Course::findOrFail($courseId);

            $policies = CoursePolicy::where('course_id', $courseId)
                ->orderBy('order')
                ->get()
                ->map(function ($policy) {
                    return [
                        'policy_id' => $policy->policy_id,
                        'course_id' => $policy->course_id,
                        'policy_number' => $policy->policy_number,
                        'title' => $policy->title,
                        'content' => $policy->content,
                        'is_fixed' => $policy->is_fixed,
                        'fixed_title' => $policy->is_fixed ? CoursePolicy::getFixedPolicyName($policy->policy_number) : null,
                        'order' => $policy->order,
                        'created_at' => $policy->created_at,
                        'updated_at' => $policy->updated_at,
                    ];
                });

            // فصل الثابتة والمضافة
            $fixed = $policies->where('is_fixed', true)->values();
            $additional = $policies->where('is_fixed', false)->values();

            return response()->json([
                'success' => true,
                'course' => [
                    'course_id' => $course->course_id,
                    'course_name' => $course->course_name,
                ],
                'fixed_policies' => $fixed,
                'additional_policies' => $additional,
                'summary' => [
                    'total_count' => $policies->count(),
                    'fixed_count' => $fixed->count(),
                    'additional_count' => $additional->count(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * إضافة ضابط جديد (مضاف، غير ثابت)
     * POST /api/v1/courses/{course_id}/policies
     */
    public function store(Request $request, $courseId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:300',
                'content' => 'required|string',
                'order' => 'integer|min:0',
            ], $this->getArabicMessages());

            $course = Course::findOrFail($courseId);

            // جلب أعلى رقم ضابط مضاف
            $maxNumber = CoursePolicy::where('course_id', $courseId)
                ->where('is_fixed', false)
                ->max('policy_number') ?? 7;

            $policy = CoursePolicy::create([
                'course_id' => $courseId,
                'policy_number' => $maxNumber + 1,
                'title' => $validated['title'],
                'content' => $validated['content'],
                'is_fixed' => false,
                'order' => $validated['order'] ?? 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة الضابط بنجاح',
                'data' => $policy,
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
     * عرض ضابط محدد
     * GET /api/v1/courses/{course_id}/policies/{policy_id}
     */
    public function show($courseId, $policyId): JsonResponse
    {
        try {
            $policy = CoursePolicy::where('course_id', $courseId)
                ->where('policy_id', $policyId)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $policy,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'الضابط غير موجود',
            ], 404);
        }
    }

    /**
     * تحديث ضابط (فقط الضوابط المضافة)
     * PUT /api/v1/courses/{course_id}/policies/{policy_id}
     */
    public function update(Request $request, $courseId, $policyId): JsonResponse
    {
        try {
            $policy = CoursePolicy::where('course_id', $courseId)
                ->where('policy_id', $policyId)
                ->firstOrFail();

            // التحقق من أنه ليس ضابط ثابت
            if ($policy->is_fixed) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تعديل الضوابط الثابتة',
                ], 422);
            }

            $validated = $request->validate([
                'title' => 'string|max:300',
                'content' => 'string',
                'order' => 'integer|min:0',
            ], $this->getArabicMessages());

            $policy->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الضابط بنجاح',
                'data' => $policy,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * حذف ضابط (فقط الضوابط المضافة)
     * DELETE /api/v1/courses/{course_id}/policies/{policy_id}
     */
    public function destroy($courseId, $policyId): JsonResponse
    {
        try {
            $policy = CoursePolicy::where('course_id', $courseId)
                ->where('policy_id', $policyId)
                ->firstOrFail();

            // التحقق من أنه ليس ضابط ثابت
            if ($policy->is_fixed) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف الضوابط الثابتة',
                ], 422);
            }

            $policy->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الضابط بنجاح',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * الحصول على الضوابط الثابتة المسبقة
     * GET /api/v1/courses/{course_id}/policies/fixed-template
     */
    public function fixedTemplate($courseId): JsonResponse
    {
        try {
            $course = Course::findOrFail($courseId);

            $fixedPolicies = [
                [
                    'policy_number' => 1,
                    'title' => 'الحضور والغياب',
                    'content' => 'حضور المحاضرات إلزامي، ويعتبر الطالب غائباً إذا تجاوزت نسبة غيابه عن 25% من الساعات المحددة، ويُعد محرو ماً من دخول الاختبار النهائي.',
                ],
                [
                    'policy_number' => 2,
                    'title' => 'الحضور المتأخر',
                    'content' => 'يعتبر الطالب متأخراً عن الفصل إذا لم يكن في الفصل بعد 10 دقائق من وقت بدء المحاضرة.',
                ],
                [
                    'policy_number' => 3,
                    'title' => 'ضوابط الاختبار',
                    'content' => 'لا يُسمح لأي طالب دخول قاعة الاختبارات بعد مرور 30 دقيقة من وقت بدء الاختبار، ولا يُسمح له بمغادرة القاعة قبل مرور نصف وقت الاختبار.',
                ],
                [
                    'policy_number' => 4,
                    'title' => 'التكليفات والمهام والمشاريع',
                    'content' => 'يجب على الطالب تقديم الواجبات والمشاريع في الوقت المحدد، وإذا تأخر الطالب عن تسليم واجباته عن الموعد المحدد فسيفقد الدرجة المخصصة لذلك.',
                ],
                [
                    'policy_number' => 5,
                    'title' => 'الغش',
                    'content' => 'الغش هو فعل احتيالي ينتج عنه إلغاء الاختبار النهائي للطالب وتطبق عليه العقوبات المنصوص عليها في نظام الطلاب الموحد (2008).',
                ],
                [
                    'policy_number' => 6,
                    'title' => 'التزوير وانتحال الهوية',
                    'content' => 'التزوير/ انتحال الهوية هو عمل احتيالي ينتج عنه إلغاء الاختبار النهائي للطالب، وتطبق عليه العقوبات المنصوص عليها في النظام الموحد لشئون الطلاب (2008).',
                ],
                [
                    'policy_number' => 7,
                    'title' => 'سياسات أخرى',
                    'content' => 'يتم التقيد الصارم باللوائح الرسمية الأكاديمية السارية ويجب على الطلاب الامتثال لجميع القواعد واللوائح الخاصة بالاختبارات.',
                ],
            ];

            return response()->json([
                'success' => true,
                'fixed_template' => $fixedPolicies,
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
            'title.required' => 'عنوان الضابط مطلوب',
            'title.max' => 'عنوان الضابط لا يجب أن يزيد عن 300 حرف',
            'content.required' => 'محتوى الضابط مطلوب',
        ];
    }
}