<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\QrCode;
use App\Models\TimetableEntry;
use Illuminate\Support\Str;
use Carbon\Carbon;

class QrCodesController extends Controller
{
    public function startSession(Request $request)
    {
        $data = $request->validate([
            'entry_id'         => 'required|integer|exists:timetable_entries,entry_id',
            'interval_seconds' => 'required|integer|min:5',
            'valid_minutes'    => 'required|integer|min:1',
            'latitude'         => 'required|numeric',
            'longitude'        => 'required|numeric',
            'allowed_distance' => 'required|numeric|min:1',
        ]);

        $user = Auth::user();
        if (!$user->lecturer) {
            return response()->json(['message' => 'User is not a lecturer.'], 403);
        }

        $entry = TimetableEntry::where('entry_id', $data['entry_id'])
            ->where('lecturer_id', $user->lecturer->lecturer_id)
            ->firstOrFail();

        QrCode::where('entry_id', $data['entry_id'])->where('is_active', true)->update(['is_active' => false]);
        
        $qrCode = QrCode::create([
            'entry_id'         => $data['entry_id'],
            'qr_code_value'    => Str::random(32),
            'expires_at'       => Carbon::now()->addMinutes($data['valid_minutes']),
            'is_active'        => true,
            'created_by'       => $user->lecturer->lecturer_id,
            'latitude'         => $data['latitude'],
            'longitude'        => $data['longitude'],
            'allowed_distance' => $data['allowed_distance'],
        ]);

        return response()->json($qrCode, 201);
    }

    public function refresh(Request $request)
    {
        $data = $request->validate([
            'qr_code_value' => 'required|string|exists:qr_codes,qr_code_value', // <-- التغيير هنا
            'valid_minutes' => 'required|integer|min:1',
        ]);
        $oldQr = QrCode::where('qr_code_value', $data['qr_code_value'])->firstOrFail();
    
        $user = Auth::user();
        // $oldQr = QrCode::findOrFail($data['qr_id']);
    
        // حماية: تأكد أن المحاضر هو من أنشأ الرمز القديم
        if ($oldQr->created_by !== $user->lecturer->lecturer_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
    
        // إلغاء الرمز القديم
        $oldQr->update(['is_active' => false]);
    
        // إنشاء رمز جديد بنفس بيانات الجلسة
        $newQr = QrCode::create([
            'entry_id'         => $oldQr->entry_id,
            'qr_code_value'    => \Illuminate\Support\Str::random(32),
            'expires_at'       => now()->addMinutes($data['valid_minutes']),
            'is_active'        => true,
            'created_by'       => $oldQr->created_by,
            'latitude'         => $oldQr->latitude,
            'longitude'        => $oldQr->longitude,
            'allowed_distance' => $oldQr->allowed_distance,
        ]);
    
        return response()->json($newQr, 201);
    }
}