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
    protected $guarded = ['assessment_id'];

    protected $fillable = [
        'college_id',
        'course_id',
        'group_id',
        'semester_id',
        'created_by',
        'academic_year',
        'name',
        'week',
        'grade',
        'weight',
        'percentage',
        'clo_ids',
        'assessment_type',
        'order',
        'notes',
    ];

    protected $casts = [
        'grade' => 'decimal:2',
        'weight' => 'integer',
        'percentage' => 'decimal:2',
        'clo_ids' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ============================================================
    // العلاقات
    // ============================================================

    /**
     * المقرر الذي ينتمي له التقييم
     */
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    /**
     * الكلية الذي ينتمي لها التقييم
     */
    public function college()
    {
        return $this->belongsTo(College::class, 'college_id', 'college_id');
    }

    /**
     * المجموعة الطلابية
     */
    public function group()
    {
        return $this->belongsTo(StudentGroup::class, 'group_id', 'group_id');
    }

    /**
     * الفصل الدراسي
     */
    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_id', 'semester_id');
    }

    /**
     * المحاضر الذي أنشأ التقييم
     */
    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class, 'created_by', 'lecturer_id');
    }

    /**
     * درجات الطلاب
     */
    public function studentGrades()
    {
        return $this->hasMany(StudentGrade::class, 'assessment_id', 'assessment_id');
    }

    // ============================================================
    // Scopes
    // ============================================================

    /**
     * فلترة حسب المقرر
     */
    public function scopeByCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    /**
     * فلترة حسب نوع التقييم
     */
    public function scopeByType($query, $type)
    {
        return $query->where('assessment_type', $type);
    }

    /**
     * فلترة حسب السنة الدراسية
     */
    public function scopeByAcademicYear($query, $year)
    {
        return $query->where('academic_year', $year);
    }

    // ============================================================
    // الدوال المساعدة
    // ============================================================

    /**
     * التحقق من صحة النسبة المئوية
     */
    public function isValidPercentage()
    {
        return $this->percentage >= 0 && $this->percentage <= 100;
    }

    /**
     * الحصول على أسماء المخرجات المرتبطة
     */
    public function getOutcomeNames()
    {
        if (!$this->clo_ids) {
            return [];
        }

        return CourseLearningOutcome::whereIn('code', $this->clo_ids)
            ->where('course_id', $this->course_id)
            ->pluck('description', 'code')
            ->toArray();
    }
}