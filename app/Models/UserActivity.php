<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserActivity extends Model
{
    use HasFactory;

    protected $table = 'user_activities';
    protected $primaryKey = 'activity_id';
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'action_type',
        'action_description',
        'module_name',
        'created_at', // موجود في الجدول
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}