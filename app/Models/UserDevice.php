<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
    use HasFactory;

    protected $table = 'user_devices';
    protected $primaryKey = 'device_id';
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'device_name',
        'mac_address',
        'os_type',
        'is_auto_attendance_enabled',
        'registered_at',
        'last_login_at',
    ];

    protected $casts = [
        'is_auto_attendance_enabled' => 'boolean',
        'registered_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}