<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'student_groups';
    protected $primaryKey = 'group_id';
    public $timestamps = true;

    protected $fillable = [
        'college_id',
        'department_id',
        'program_id',
        'level_id',
        'semester_id',
        'block_id',
        'group_name',
        'max_students',
    ];

    public function members()
    {
        // student_group_members هو اسم الجدول الوسيط (pivot table)
        return $this->belongsToMany(Student::class, 'student_group_members', 'group_id', 'student_id');
    }

    public function students()
    {
        return $this->belongsToMany(\App\Models\Student::class, 'student_group_members', 'group_id', 'student_id');
    }

    public function timetables()
    {
        return $this->hasMany(Timetable::class, 'group_id', 'group_id');
    }

    public function lecturerGroupNotifications()
    {
        return $this->hasMany(LecturerGroupNotification::class, 'group_id', 'group_id');
    }
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id', 'program_id');
    }

    public function level()
    {
        return $this->belongsTo(Level::class, 'level_id', 'level_id');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_id', 'semester_id');
    }

    public function block()
    {
        return $this->belongsTo(Block::class, 'block_id', 'id');
    }
}