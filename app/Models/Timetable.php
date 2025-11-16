<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timetable extends Model
{
    use HasFactory;

    protected $table = 'timetable';
    protected $primaryKey = 'timetable_id';
    public $timestamps = true;

    protected $fillable = [
        'course_id', 'lecturer_id', 'group_id', 'classroom_id', 'day_id', 'period_id',
        'lecture_type', 'status', 'start_date', 'end_date', 'academic_year',
        'college_id', 'department_id', 'level_id', 'gender_type', 'lecture_hours',
    ];

    // --- ✅ العلاقات (Relationships) - تأكد من وجودها كلها ---

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class, 'lecturer_id', 'lecturer_id');
    }

    public function group() // <-- على الأغلب، هذه كانت مفقودة
    {
        return $this->belongsTo(StudentGroup::class, 'group_id', 'group_id');
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id', 'classroom_id');
    }

    public function day()
    {
        return $this->belongsTo(Day::class, 'day_id', 'day_id');
    }

    public function period()
    {
        return $this->belongsTo(Period::class, 'period_id', 'period_id');
    }
    
    public function nextSession()
    {
        return $this->hasOne(LectureSession::class, 'timetable_id', 'timetable_id');
    }
public function lectureSessions()
{
    return $this->hasMany(LectureSession::class, 'timetable_id', 'timetable_id');
}
}