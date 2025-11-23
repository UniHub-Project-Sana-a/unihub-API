<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'courses';
    protected $primaryKey = 'course_id';
    public $timestamps = true;

    protected $fillable = [
        'course_name',
        'course_code',
        'course_type',
        'is_active',
        'semester_id',     // مهم
        'credit_hours',
        'is_elective',
        'department_id',
        'notes',
    ];

     protected $casts = [
        'is_active'   => 'boolean',
        'is_elective' => 'boolean',
    ];

    public function timetables()
    {
        return $this->hasMany(Timetable::class, 'course_id', 'course_id');
    }

    public function makeupLectureRequests()
    {
        return $this->hasMany(MakeupLecturesRequest::class, 'course_id', 'course_id');
    }

    public function studentExcuseSubmissions()
    {
        return $this->hasMany(StudentExcuseSubmission::class, 'course_id', 'course_id');
    }

     public function getRouteKeyName()
    {
        return 'course_id';
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_id', 'semester_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

        public function timetable()
    {
        return $this->hasMany(Timetable::class, 'course_id', 'course_id');
    }
    
    // إذا كنت تستخدم studentAttendance في الكود (اختياري حسب الكنترولر)
    public function studentAttendance()
    {
        return $this->hasManyThrough(
            StudentAttendance::class,
            Timetable::class,
            'course_id',
            'timetable_id',
            'course_id',
            'timetable_id'
        );
    }
}