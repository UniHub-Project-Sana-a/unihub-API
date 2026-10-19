<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpDeviceVerification extends Model
{
    use HasFactory;

    /**
     * اسم الجدول في قاعدة البيانات.
     */
    protected $table = 'otp_device_verifications';

    /**
     * المفتاح الأساسي للجدول. Laravel يفترض أن يكون 'id' بشكل افتراضي.
     * بما أن الـ migration تستخدم ->increments('verification_id')، يجب أن نحدده هنا.
     */
    protected $primaryKey = 'verification_id';

    /**
     * الحقول التي يمكن تعبئتها.
     * يجب أن تطابق الأعمدة التي نرسلها في دالة create.
     */
    protected $fillable = [
        'user_id',
        'otp_code',
        'device_name',
        'mac_address',
        'os_type',
        'expires_at',
        'is_verified', // نسمح بتحديث هذا الحقل
        'delivery_status',
    ];

    /**
     * تحويل أنواع البيانات.
     */
    protected $casts = [
        'is_verified' => 'boolean',
        'expires_at' => 'datetime',
    ];

    /**
     * العلاقة مع المستخدم.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}