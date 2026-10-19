<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TopicQuestion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'topic_questions';
    protected $primaryKey = 'question_id';
    protected $guarded = ['question_id'];

    protected $fillable = [
        'course_id',
        'part',
        'topic_id',
        'subtopic',
        'question_text',
        'question_type',
        'difficulty_level',
        'clo_code',
        'options',
        'correct_answer',
        'is_used_in_exam',
        'is_active',
        'usage_count',
        'order',
    ];

    protected $casts = [
        'options' => 'array',
        'is_used_in_exam' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ============================================================
    // العلاقات
    // ============================================================

    /**
     * الموضوع الذي ينتمي له السؤال
     */
    public function topic()
    {
        return $this->belongsTo(CourseTopic::class, 'topic_id', 'topic_id');
    }

    /**
     * المقرر الذي ينتمي له السؤال (عبر الموضوع)
     */
    public function course()
    {
        return $this->hasManyThrough(
            Course::class,
            CourseTopic::class,
            'topic_id',
            'course_id',
            'topic_id',
            'course_id'
        )->distinct();
    }

    // ============================================================
    // Scopes
    // ============================================================

    /**
     * فلترة الأسئلة النشطة
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * فلترة أسئلة MCQ
     */
    public function scopeMCQ($query)
    {
        return $query->where('question_type', 'MCQ');
    }

    /**
     * فلترة الأسئلة المقالية
     */
    public function scopeEssay($query)
    {
        return $query->where('question_type', 'essay');
    }

    /**
     * فلترة حسب مستوى الصعوبة
     */
    public function scopeByDifficulty($query, $level)
    {
        return $query->where('difficulty_level', $level);
    }

    /**
     * فلترة الأسئلة المستخدمة في الامتحانات
     */
    public function scopeUsedInExams($query)
    {
        return $query->where('is_used_in_exam', true);
    }

    // ============================================================
    // الدوال المساعدة
    // ============================================================

    /**
     * التحقق من صحة خيارات MCQ
     */
    public function isValidMCQOptions()
    {
        if ($this->question_type !== 'MCQ' || !$this->options) {
            return false;
        }

        // يجب أن يكون هناك 4 خيارات
        if (count($this->options) !== 4) {
            return false;
        }

        // يجب أن يكون هناك خيار واحد صحيح فقط
        $correctCount = collect($this->options)->where('is_correct', true)->count();
        return $correctCount === 1;
    }

    /**
     * الحصول على الإجابة الصحيحة (للـ MCQ)
     */
    public function getCorrectOption()
    {
        if ($this->question_type !== 'MCQ' || !$this->options) {
            return null;
        }

        $correct = collect($this->options)->firstWhere('is_correct', true);
        return $correct;
    }

    /**
     * زيادة عدد الاستخدامات
     */
    public function incrementUsage()
    {
        $this->increment('usage_count');
        return $this;
    }
}