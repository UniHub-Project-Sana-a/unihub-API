<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpDeviceVerification extends Model
{
    use HasFactory;

    protected $table = 'otp_device_verifications';
    protected $primaryKey = 'verification_id';
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'otp_code',
        'device_name',
        'mac_address',
        'os_type',
        'delivery_status',
        'is_verified',
        'expires_at',
        'created_at',
    ];

    protected $casts = [
        'delivery_status' => 'integer',
        'is_verified' => 'boolean',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}