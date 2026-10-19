<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LecturerAttendance extends Model
{
    use HasFactory;

    protected $table = 'lecturer_attendance';
    protected $primaryKey = 'attendance_id';
    public $timestamps = true;

     protected $fillable = [
        'lecturer_id',
        'timetable_id',
        'attendance_date',
        'status',
        'notification_status',
        'hourly_rate_at_attendance',
        'lecture_rate_at_attendance',
        'college_id',
        'lecture_hours',
        'session_code',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'lecture_rate_at_attendance' => 'decimal:2',
        'hourly_rate_at_attendance' => 'decimal:2',
    ];

    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class, 'lecturer_id', 'lecturer_id');
    }

    public function timetable()
    {
        return $this->belongsTo(Timetable::class, 'timetable_id', 'timetable_id');
    }
    public function college()
    {
        return $this->belongsTo(College::class, 'college_id', 'college_id'); 
    }
}