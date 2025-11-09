<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LectureSession extends Model
{
    protected $table = 'lecture_sessions';
    protected $primaryKey = 'session_id';
    protected $fillable = [
        'entry_id','session_date','start_time','end_time',
        'actual_classroom_id','actual_attendance_count','session_code',
        'status','attendance_overage_alert','system_attendance_count'
    ];

    public function timetable()
    {
        return $this->belongsTo(Timetable::class, 'timetable_id', 'timetable_id');
    }
    public function actualClassroom() { return $this->belongsTo(\App\Models\Classroom::class, 'actual_classroom_id', 'classroom_id'); }
}