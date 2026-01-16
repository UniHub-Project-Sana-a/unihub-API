<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseAssessment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'course_assessments';
    protected $primaryKey = 'assessment_id';

    protected $fillable = [
        'college_id',
        'course_id',
        'group_id',
        'semester_id',
        'created_by',     // Lecturer ID
        'academic_year',  // ✅ العام الدراسي
        'name',           // اسم التقييم
        'max_score',
        'weight'
    ];

    /**
     * العلاقة مع الدرجات المرصودة تحت هذا البند
     */
    public function grades()
    {
        return $this->hasMany(StudentGrade::class, 'assessment_id', 'assessment_id');
    }

    // --- علاقات السياق (لأغراض العرض أو التحقق) ---

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    public function group()
    {
        return $this->belongsTo(StudentGroup::class, 'group_id', 'group_id');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_id', 'semester_id');
    }

    public function creator()
    {
        return $this->belongsTo(Lecturer::class, 'created_by', 'lecturer_id');
    }
}