<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentExcuseSubmission extends Model
{
    use HasFactory;

    protected $table = 'student_excuse_submissions';
    protected $primaryKey = 'submission_id';
    public $timestamps = true;

    protected $fillable = [
        'student_user_id','request_date','reason','course_id','lecturer_user_id',
        'is_lecturer_notified','response_status','lecturer_comment',
    ];

    protected $casts = [
        'student_user_id'       => 'integer',
        'course_id'             => 'integer',
        'lecturer_user_id'      => 'integer',
        'is_lecturer_notified'  => 'boolean',
        'response_status'       => 'boolean',
        'request_date'          => 'date',
    ];

    public function course()        { return $this->belongsTo(Course::class, 'course_id', 'course_id'); }
    public function studentUser()   { return $this->belongsTo(User::class, 'student_user_id', 'user_id'); }
    public function lecturerUser()  { return $this->belongsTo(User::class, 'lecturer_user_id', 'user_id'); }
}