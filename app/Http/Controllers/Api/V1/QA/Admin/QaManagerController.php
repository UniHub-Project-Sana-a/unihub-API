<?php

namespace App\Http\Controllers\Api\V1\QA\Admin;

use App\Http\Controllers\Controller;
use App\Models\QA\QaForm;
use App\Models\QA\QaDomain;
use App\Models\QA\QaQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QaManagerController extends Controller
{
    /**
     * جلب قائمة النماذج الخاصة بكلية معينة
     */
    public function index(Request $request)
    {
        $collegeId = $request->query('college_id');

        $forms = QaForm::query()
            ->withCount(['domains', 'domains as questions_count' => function ($q) {
                // حسبة تقريبية لعدد الأسئلة للعرض في القائمة
                $q->join('qa_questions', 'qa_domains.domain_id', '=', 'qa_questions.domain_id');
            }])
            ->where('college_id', $collegeId)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($forms);
    }

    /**
     * إنشاء نموذج جديد (فارغ مبدئياً)
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:150',
            'target_type' => 'required|in:theory,practical,both',
            'college_id' => 'required|integer',
        ]);

        $form = QaForm::create([
            'title' => $request->title,
            'description' => $request->description,
            'target_type' => $request->target_type,
            'college_id' => $request->college_id,
            'academic_year' => $request->academic_year ?? date('Y') . '-' . (date('Y') + 1),
            'is_active' => false,
        ]);

        return response()->json($form, 201);
    }

    /**
     * جلب تفاصيل النموذج كاملة (المجالات + الأسئلة) للتعديل
     */
    public function show($id)
    {
        $form = QaForm::with(['domains.questions' => function($q) {
            $q->orderBy('sort_order');
        }])->findOrFail($id);

        return response()->json($form);
    }

    /**
     * تحديث النموذج وهيكلته بالكامل (Save Changes)
     * هذه الدالة تقوم بمزامنة المجالات والأسئلة (إضافة/تعديل/حذف)
     */
    public function update(Request $request, $id)
    {
        $form = QaForm::findOrFail($id);

        // 1. تحديث البيانات الأساسية
        $form->update($request->only(['title', 'description', 'is_active', 'type']));

        // 2. تحديث الهيكل (المجالات والأسئلة)
        // نستخدم Transaction لضمان سلامة البيانات
        DB::transaction(function () use ($form, $request) {
            $incomingDomains = $request->input('domains', []);
            
            // قائمة بمعرفات المجالات القادمة للحفاظ عليها، والباقي سيحذف
            $keptDomainIds = [];

            foreach ($incomingDomains as $dIndex => $domainData) {
                // أ. التعامل مع المجال (Domain)
                if (isset($domainData['domain_id']) && $domainData['domain_id'] > 0) {
                    // تحديث مجال موجود
                    $domain = QaDomain::find($domainData['domain_id']);
                    if ($domain) {
                        $domain->update([
                            'domain_name' => $domainData['domain_name'],
                            'sort_order' => $dIndex + 1
                        ]);
                    }
                } else {
                    // إنشاء مجال جديد
                    $domain = $form->domains()->create([
                        'domain_name' => $domainData['domain_name'],
                        'sort_order' => $dIndex + 1
                    ]);
                }
                
                if (!$domain) continue;
                $keptDomainIds[] = $domain->domain_id;

                // ب. التعامل مع الأسئلة داخل هذا المجال
                $incomingQuestions = $domainData['questions'] ?? [];
                $keptQuestionIds = [];

                foreach ($incomingQuestions as $qIndex => $questionData) {
                    if (isset($questionData['question_id']) && $questionData['question_id'] > 0) {
                        // تحديث سؤال موجود
                        $question = QaQuestion::find($questionData['question_id']);
                        if ($question) {
                            $question->update([
                                'question_text' => $questionData['question_text'],
                                'sort_order' => $qIndex + 1,
                                'domain_id' => $domain->domain_id // لضمان النقل إن حدث
                            ]);
                            $keptQuestionIds[] = $question->question_id;
                        }
                    } else {
                        // إنشاء سؤال جديد
                        $newQ = $domain->questions()->create([
                            'question_text' => $questionData['question_text'],
                            'sort_order' => $qIndex + 1
                        ]);
                        $keptQuestionIds[] = $newQ->question_id;
                    }
                }

                // حذف الأسئلة التي لم تعد موجودة في هذا المجال
                $domain->questions()->whereNotIn('question_id', $keptQuestionIds)->delete();
            }

            // حذف المجالات التي لم تعد موجودة في النموذج
            $form->domains()->whereNotIn('domain_id', $keptDomainIds)->delete();
        });

        // إعادة تحميل البيانات الجديدة لإرجاعها للواجهة
        return response()->json($form->load('domains.questions'));
    }

    public function destroy($id)
    {
        QaForm::destroy($id);
        return response()->noContent();
    }
}