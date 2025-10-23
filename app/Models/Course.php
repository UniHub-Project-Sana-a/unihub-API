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
    ];

    protected $casts = [
        'course_type' => 'integer',
        'is_active'   => 'boolean',
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
}