<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentGrade extends Model
{
    use HasFactory;

    protected $table = 'student_grades';
    protected $primaryKey = 'grade_id';

    protected $fillable = [
        'assessment_id',
        'student_id',
        'score',
        'notes'
    ];

    /**
     * العلاقة مع بند التقييم (العمود)
     */
    public function assessment()
    {
        return $this->belongsTo(CourseAssessment::class, 'assessment_id', 'assessment_id');
    }

    /**
     * العلاقة مع الطالب
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }
}