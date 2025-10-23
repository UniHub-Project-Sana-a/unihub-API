<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LecturerGroupNotification extends Model
{
    use HasFactory;

    protected $table = 'lecturer_group_notifications';
    protected $primaryKey = 'notification_id';
    public $timestamps = true;

    protected $fillable = [
        'lecturer_user_id','subject','message_body','send_at','group_id','is_sent','is_seen',
    ];

    protected $casts = [
        'lecturer_user_id' => 'integer',
        'group_id'         => 'integer',
        'is_sent'          => 'boolean',
        'is_seen'          => 'boolean',
        'send_at'          => 'datetime',
    ];

    public function group()
    {
        return $this->belongsTo(StudentGroup::class, 'group_id', 'group_id');
    }

    public function lecturerUser()
    {
        return $this->belongsTo(User::class, 'lecturer_user_id', 'user_id');
    }
}