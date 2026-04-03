<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LectureAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LectureAttachmentsController extends Controller
{
    /**
     * جلب مرفقات جلسة معينة
     */
    public function index($sessionId)
    {
        $attachments = LectureAttachment::where('session_id', $sessionId)
            ->orderByDesc('created_at')
            ->get();
            
        return response()->json($attachments);
    }

    /**
     * رفع ملف أو إضافة رابط
     */
    public function store(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:lecture_sessions,session_id',
            'type' => 'required|in:file,video,link',
            'title' => 'required|string|max:200',
            // إذا كان ملفاً، يجب أن يكون file، وإذا كان رابطاً يجب أن يكون url
            'file' => 'required_if:type,file|file|max:10240', // Max 10MB
            'url' => 'required_if:type,video,link|nullable|url',
        ]);

        $url = $request->url;
        $fileSize = null;

        // معالجة رفع الملف
        if ($request->hasFile('file') && $request->type === 'file') {
            $file = $request->file('file');
            // التخزين في مجلد lecture_files داخل public disk
            $path = $file->store('lecture_files', 'public');
            
            // إنشاء الرابط الكامل للملف
            $url = asset('storage/' . $path);
            
            // حساب الحجم (MB)
            $sizeInMB = round($file->getSize() / 1024 / 1024, 2);
            $fileSize = $sizeInMB . ' MB';
        }

        $attachment = LectureAttachment::create([
            'session_id' => $request->session_id,
            'type' => $request->type,
            'title' => $request->title,
            'url' => $url,
            'file_size' => $fileSize
        ]);

        return response()->json($attachment, 201);
    }

        /**
     * تعديل مرفق (العنوان أو الرابط)
     */
    public function update(Request $request, $id)
    {
        $attachment = LectureAttachment::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:200',
            // نسمح بتعديل الرابط فقط إذا لم يكن ملفاً مرفوعاً
            'url' => $attachment->type === 'file' ? 'nullable' : 'required|url',
        ]);

        $attachment->title = $request->title;
        
        // إذا لم يكن ملفاً، نحدث الرابط أيضاً
        if ($attachment->type !== 'file') {
            $attachment->url = $request->url;
        }

        $attachment->save();

        return response()->json($attachment);
    }

    /**
     * حذف مرفق
     */
    public function destroy($id)
    {
        $attachment = LectureAttachment::findOrFail($id);

        // إذا كان ملفاً، نحذفه من السيرفر أيضاً لتوفير المساحة
        if ($attachment->type === 'file') {
            // استخراج المسار النسبي من الرابط الكامل
            $relativePath = str_replace(asset('storage/'), '', $attachment->url);
            Storage::disk('public')->delete($relativePath);
        }

        $attachment->delete();
        return response()->noContent();
    }
}