<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'users';
    protected $primaryKey = 'user_id';
    public $timestamps = true;

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'password',
        'academic_number',
        'gender',
        'user_type_id',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'gender' => 'integer',
        // إن أردت التهشير التلقائي لكلمة المرور لاحقاً: 'password' => 'hashed',
    ];

    // Relations
    public function userType()
    {
        return $this->belongsTo(UserType::class, 'user_type_id', 'user_type_id');
    }

    public function student()
    {
        return $this->hasOne(Student::class, 'user_id', 'user_id');
    }

    public function lecturer()
    {
        return $this->hasOne(Lecturer::class, 'user_id', 'user_id');
    }

    public function devices()
    {
        return $this->hasMany(UserDevice::class, 'user_id', 'user_id');
    }

    public function activities()
    {
        return $this->hasMany(UserActivity::class, 'user_id', 'user_id');
    }

    public function otpDeviceVerifications()
    {
        return $this->hasMany(OtpDeviceVerification::class, 'user_id', 'user_id');
    }

    public function lecturerGroupNotifications()
    {
        return $this->hasMany(LecturerGroupNotification::class, 'lecturer_user_id', 'user_id');
    }
}