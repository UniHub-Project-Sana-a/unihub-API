<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseAssignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'course_assignments';
    protected $primaryKey = 'assignment_id';
    protected $guarded = ['assignment_id'];

    protected $fillable = [
        'course_id',
        'part',
        'title',
        'description',
        'week',
        'grade',
        'clo_ids',
        'assignment_type',
        'is_mandatory',
        'notes',
    ];

    protected $casts = [
        'grade' => 'decimal:2',
        'clo_ids' => 'array',
        'is_mandatory' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ============================================================
    // العلاقات
    // ============================================================

    /**
     * المقرر الذي ينتمي له التكليف
     */
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    // ============================================================
    // Scopes
    // ============================================================

    /**
     * فلترة التكاليف الإجبارية
     */
    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    /**
     * فلترة حسب المقرر
     */
    public function scopeByCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    /**
     * فلترة حسب الجزء
     */
    public function scopeByPart($query, $part)
    {
        return $query->where('part', $part);
    }

    /**
     * فلترة حسب نوع التكليف
     */
    public function scopeByType($query, $type)
    {
        return $query->where('assignment_type', $type);
    }

    // ============================================================
    // الدوال المساعدة
    // ============================================================

    /**
     * التحقق من صحة الأسبوع
     */
    public function isValidWeek()
    {
        return $this->week >= 1 && $this->week <= 16;
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