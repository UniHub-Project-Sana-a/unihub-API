<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TimetableImportLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Jobs\TimetableImportJob; // سيتم إنشاؤه في الخطوة التالية
use Illuminate\Support\Str;

class TimetablesImportController extends Controller
{
    // === الدوال العامة للاستيراد ===

    /**
     * استيراد من ملف PDF
     */
    public function importPdf(Request $request)
    {
        return $this->handleImport($request, 'pdf');
    }

    /**
     * استيراد من ملف Excel
     */
    public function importExcel(Request $request)
    {
        return $this->handleImport($request, 'excel');
    }

    /**
     * استيراد من ملف CSV
     */
    public function importCsv(Request $request)
    {
        return $this->handleImport($request, 'csv');
    }

    /**
     * استيراد من رابط API
     */
    public function importApi(Request $request)
    {
        $data = $request->validate([
            'url'           => ['required','url'],
            'college_id'    => ['required','integer','exists:colleges,college_id'],
            'department_id' => ['nullable','integer','exists:departments,department_id'],
        ]);

        $log = TimetableImportLog::create([
            'source'  => 'api',
            'status'  => 'معالجة',
            'notes'   => "جاري الجلب من {$data['url']}",
            'user_id' => Auth::id(),
        ]);

        // إرسال البيانات إلى الجوب للمعالجة في الخلفية
        TimetableImportJob::dispatch($log->id, $data['url'], $data['college_id'], $data['department_id'] ?? null);

        return response()->json(['job_id' => $log->id, 'message' => 'بدأت عملية الاستيراد'], 202);
    }

    /**
     * جلب سجل عمليات الاستيراد
     */
    public function logs()
    {
        $logs = TimetableImportLog::query()
            ->select(['id', 'created_at', 'source', 'items', 'status', 'notes'])
            ->orderBy('created_at', 'desc')
            ->take(50)->get();

        return response()->json($logs);
    }

    // === دالة مساعدة لمعالجة رفع الملفات ===
    
    private function handleImport(Request $request, string $type)
    {
        $mimes = [
            'pdf'   => 'pdf',
            'excel' => 'xlsx,xls',
            'csv'   => 'csv,txt',
        ];

        $data = $request->validate([
            'file'          => ['required', 'file', 'mimes:'.$mimes[$type], 'max:20480'],
            'college_id'    => ['required', 'integer', 'exists:colleges,college_id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,department_id'],
        ]);

        $file = $request->file('file');
        $ext = $file->getClientOriginalExtension();
        $source = "file:{$ext}";
        $filename = $file->getClientOriginalName();

        $log = TimetableImportLog::create([
            'source'  => $source,
            'status'  => 'معالجة',
            'notes'   => "بدء تحليل {$filename}",
            'user_id' => Auth::id(),
        ]);

        $path = $file->storeAs("imports", "job_{$log->id}.{$ext}");

        TimetableImportJob::dispatch($log->id, $path, $data['college_id'], $data['department_id'] ?? null);

        return response()->json(['job_id' => $log->id, 'message' => 'بدأت عملية الاستيراد'], 202);
    }
}