<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAttendance extends Model
{
    use HasFactory;

    protected $table = 'student_attendance';
    protected $primaryKey = 'attendance_id';
    public $timestamps = true;

     protected $fillable = [
        'student_id',
        'timetable_id',
        'level_id',
        'attendance_date',
        'status',
        'notification_status',
        'college_id',
        'department_id',
        'session_code',
    ];

    protected $casts = [
        'student_id'         => 'integer',
        'timetable_id'       => 'integer',
        'attendance_date'    => 'date',
        'status'             => 'integer',
        'notification_status'=> 'integer',
        'college_id'         => 'integer',
        'department_id'      => 'integer',
    ];

    public function student()    { return $this->belongsTo(Student::class, 'student_id', 'student_id'); }
    public function timetable()  { return $this->belongsTo(Timetable::class, 'timetable_id', 'timetable_id'); }
    public function college()    { return $this->belongsTo(College::class, 'college_id', 'college_id'); }
    public function department() { return $this->belongsTo(Department::class, 'department_id', 'department_id'); }
}