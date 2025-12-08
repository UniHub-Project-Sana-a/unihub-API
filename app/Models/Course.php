<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\College;
use App\Models\Department;
use App\Models\Program;
use App\Models\Level;
use App\Models\Semester;
use App\Models\Timetable;

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
        'semester_id',
        
        // ✅ الحقول الجديدة
        'college_id',
        'department_id',
        'program_id',
        'level_id',
        
        'credit_hours',
        'is_elective',
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

    public function college()
    {
        return $this->belongsTo(College::class, 'college_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function level()
    {
        return $this->belongsTo(Level::class, 'level_id');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    public function timetable()
    {
        return $this->hasMany(Timetable::class, 'course_id');
    }
}