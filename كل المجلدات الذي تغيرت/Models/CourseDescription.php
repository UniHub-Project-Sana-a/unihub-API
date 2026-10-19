<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseDescription extends Model
{
    use HasFactory;

    protected $table = 'course_descriptions';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'course_id',
        'description',
        'word_count',
        'goals',
        'goals_count',
        'is_completed',
    ];

    protected $casts = [
        'goals' => 'array',
        'is_completed' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ============================================================
    // العلاقات
    // ============================================================

    /**
     * العلاقة مع المقرر
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    // ============================================================
    // الدوال المساعدة
    // ============================================================

    /**
     * التحقق من صحة عدد الكلمات (80-100)
     */
    public function isValidWordCount(): bool
    {
        return $this->word_count >= 80 && $this->word_count <= 100;
    }

    /**
     * التحقق من صحة عدد الأهداف (4-6)
     */
    public function isValidGoalsCount(): bool
    {
        return $this->goals_count >= 4 && $this->goals_count <= 6;
    }

    /**
     * حساب عدد الكلمات تلقائياً
     */
    public function calculateWordCount(): self
    {
        if ($this->description) {
            $words = preg_split('/\s+/u', trim($this->description), -1, PREG_SPLIT_NO_EMPTY);
            $this->word_count = count($words);
        } else {
            $this->word_count = 0;
        }
        
        return $this;
    }

    /**
     * حساب عدد الأهداف تلقائياً
     */
    public function calculateGoalsCount(): self
    {
        $this->goals_count = count($this->goals ?? []);
        return $this;
    }

    /**
     * التحقق من اكتمال البيانات
     */
    public function isComplete(): bool
    {
        return !empty($this->description) &&
               $this->isValidWordCount() &&
               !empty($this->goals) &&
               $this->isValidGoalsCount();
    }

    /**
     * ✅ Mutator - تحديث الوصف مع حساب الكلمات
     */
    public function setDescriptionAttribute(?string $value): void
    {
        $this->attributes['description'] = $value;
        
        if ($value) {
            $words = preg_split('/\s+/u', trim($value), -1, PREG_SPLIT_NO_EMPTY);
            $this->attributes['word_count'] = count($words);
        } else {
            $this->attributes['word_count'] = 0;
        }
    }
}