<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LectureSession extends Model
{
    use HasFactory;

    protected $table = 'lecture_sessions';
    protected $primaryKey = 'session_id';
    public $timestamps = true;

    protected $fillable = [
        'timetable_id','session_date','start_time','end_time','actual_classroom_id',
        'actual_attendance_count','session_code','status','attendance_overage_alert','system_attendance_count',
    ];

    protected $casts = [
        'timetable_id'              => 'integer',
        'session_date'              => 'date',
        'start_time'                => 'string',
        'end_time'                  => 'string',
        'actual_classroom_id'       => 'integer',
        'actual_attendance_count'   => 'integer',
        'status'                    => 'integer',
        'attendance_overage_alert'  => 'boolean',
        'system_attendance_count'   => 'integer',
    ];

    public function timetable()   { return $this->belongsTo(Timetable::class, 'timetable_id', 'timetable_id'); }
    public function classroom()   { return $this->belongsTo(Classroom::class, 'actual_classroom_id', 'classroom_id'); }
}