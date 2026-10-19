<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseTopic extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'course_topics';
    protected $primaryKey = 'topic_id';
    protected $guarded = ['topic_id'];

    protected $fillable = [
        'course_id',
        'part',
        'week',
        'unit_name',
        'subtopics',
        'is_exam',
        'exam_type',
        'hours',
        'clo_ids',
        'order',
        'notes',
    ];

    protected $casts = [
        'subtopics' => 'array',
        'clo_ids' => 'array',
        'is_exam' => 'boolean',
        'hours' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ============================================================
    // العلاقات
    // ============================================================

    /**
     * المقرر الذي ينتمي له الموضوع
     */
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    /**
     * الأسئلة المرتبطة بهذا الموضوع
     */
    public function questions()
    {
        return $this->hasMany(TopicQuestion::class, 'topic_id', 'topic_id')
            ->orderBy('order');
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
     * فلترة حسب الجزء
     */
    public function scopeByPart($query, $part)
    {
        return $query->where('part', $part);
    }

    /**
     * فلترة الامتحانات فقط
     */
    public function scopeExams($query)
    {
        return $query->where('is_exam', true);
    }

    /**
     * فلترة المواضيع العادية (غير الامتحانات)
     */
    public function scopeNonExams($query)
    {
        return $query->where('is_exam', false);
    }

    /**
     * فلترة حسب الأسبوع
     */
    public function scopeByWeek($query, $week)
    {
        return $query->where('week', $week);
    }

    // ============================================================
    // الدوال المساعدة
    // ============================================================

    /**
     * التحقق من أن الأسبوع صحيح
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