<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoursePrerequisite extends Model
{
    protected $table = 'course_prerequisites';
    public $timestamps = true;

    protected $fillable = [
        'course_id',
        'prerequisite_course_id',
        'type',
    ];

    protected $casts = [
        'type' => 'string',
    ];

    // ✅ العلاقات
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    public function prerequisiteCourse()
    {
        return $this->belongsTo(Course::class, 'prerequisite_course_id', 'course_id');
    }

    // ✅ Scopes
    public function scopePrerequisites($query)
    {
        return $query->where('type', 'prerequisite');
    }

    public function scopeCorequisites($query)
    {
        return $query->where('type', 'corequisite');
    }
}