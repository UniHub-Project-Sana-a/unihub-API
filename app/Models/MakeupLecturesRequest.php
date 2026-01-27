<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MakeupLecturesRequest extends Model
{
    use HasFactory;

    protected $table = 'makeup_lectures_requests';
    protected $primaryKey = 'request_id';

    protected $fillable = [
        'lecturer_id',
        'course_id',
        'group_id',
        'original_date',
        'requested_date',
        'start_time',
        'end_time',
        'classroom_id',
        'reason_type',
        'description',
        'status',
        'notification_status'
    ];

    protected $casts = [
        'lecturer_id'    => 'integer',
        'course_id'      => 'integer',
        'group_id'       => 'integer',
        'original_date'  => 'date', // مهم لتنسيق التاريخ
        'requested_date' => 'date', // مهم لتنسيق التاريخ
        'status'         => 'integer',
        'notification_status' => 'integer',
        'classroom_id'   => 'integer',
    ];

    // ✅✅ العلاقات المفقودة (التي تسبب الخطأ) ✅✅

    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class, 'lecturer_id', 'lecturer_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    public function group()
    {
        return $this->belongsTo(StudentGroup::class, 'group_id', 'group_id');
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id', 'classroom_id');
    }
}