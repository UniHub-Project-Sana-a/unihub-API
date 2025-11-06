<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimetableEntry extends Model
{
    use SoftDeletes;

    protected $table = 'timetable_entries';
    protected $primaryKey = 'entry_id';
    protected $fillable = [
        'schedule_id','course_id','lecturer_id','group_id','classroom_id',
        'day_id','period_id','lecture_type','status','gender_type','lecture_hours','notes'
    ];

    public function schedule() { return $this->belongsTo(TimetableSet::class, 'schedule_id', 'schedule_id'); }
    public function course()   { return $this->belongsTo(\App\Models\Course::class, 'course_id', 'course_id'); }
    public function lecturer() { return $this->belongsTo(\App\Models\Lecturer::class, 'lecturer_id', 'lecturer_id'); }
    public function group()    { return $this->belongsTo(\App\Models\StudentGroup::class, 'group_id', 'group_id'); }
    public function classroom(){ return $this->belongsTo(\App\Models\Classroom::class, 'classroom_id', 'classroom_id'); }
    public function day()      { return $this->belongsTo(\App\Models\Day::class, 'day_id', 'day_id'); }
    public function period()   { return $this->belongsTo(\App\Models\Period::class, 'period_id', 'period_id'); }

    public function sessions()
    {
        return $this->hasMany(LectureSession::class, 'entry_id', 'entry_id');
    }
}