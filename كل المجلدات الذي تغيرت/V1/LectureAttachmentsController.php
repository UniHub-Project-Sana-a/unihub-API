<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LectureAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LectureAttachmentsController extends Controller
{
    private function isValidHttpUrl(?string $url): bool
    {
        if (!is_string($url) || trim($url) === '') {
            return false;
        }

        $url = trim($url);
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parsed = parse_url($url);
        return is_array($parsed)
            && !empty($parsed['scheme'])
            && in_array(strtolower($parsed['scheme']), ['http', 'https'], true)
            && !empty($parsed['host']);
    }

    private function isValidYoutubeUrl(?string $url): bool
    {
        if (!$this->isValidHttpUrl($url)) {
            return false;
        }

        $host = strtolower(parse_url(trim($url), PHP_URL_HOST) ?? '');
        $allowedHosts = ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be', 'www.youtu.be'];

        if (!in_array($host, $allowedHosts, true)) {
            return false;
        }

        $pattern = '/(?:youtube\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/)|youtu\.be\/)/i';

        return preg_match($pattern, trim($url)) === 1;
    }

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
            'file' => 'required_if:type,file|file|max:10240',
            'url' => [
                'required_if:type,video,link',
                'nullable',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value === null || trim((string) $value) === '') {
                        return;
                    }

                    if ($request->type === 'video' && !$this->isValidYoutubeUrl($value)) {
                        $fail('رابط الفيديو يجب أن يكون رابط YouTube صحيحاً.');
                        return;
                    }

                    if ($request->type === 'link' && !$this->isValidHttpUrl($value)) {
                        $fail('الرابط الخارجي غير صالح، يجب أن يبدأ بـ http أو https.');
                    }
                },
            ],
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
            'url' => [
                $attachment->type === 'file' ? 'nullable' : 'required',
                function ($attribute, $value, $fail) use ($attachment) {
                    if ($attachment->type === 'file') {
                        return;
                    }

                    if ($value === null || trim((string) $value) === '') {
                        return;
                    }

                    if ($attachment->type === 'video' && !$this->isValidYoutubeUrl($value)) {
                        $fail('رابط الفيديو يجب أن يكون رابط YouTube صحيحاً.');
                        return;
                    }

                    if ($attachment->type === 'link' && !$this->isValidHttpUrl($value)) {
                        $fail('الرابط الخارجي غير صالح، يجب أن يبدأ بـ http أو https.');
                    }
                },
            ],
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