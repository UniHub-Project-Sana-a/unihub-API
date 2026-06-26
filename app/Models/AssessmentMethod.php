<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentMethod extends Model
{
    use HasFactory;

    protected $table = 'assessment_methods';
    protected $primaryKey = 'id';
    protected $guarded = ['id'];

    protected $fillable = [
        'name',
        'description',
        'category',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ============================================================
    // العلاقات
    // ============================================================

    /**
     * مخرجات التعلم المرتبطة بهذه الطريقة
     */
    public function courseLearningOutcomes()
    {
        return $this->belongsToMany(
            CourseLearningOutcome::class,
            'outcome_assessment_method',
            'method_id',
            'clo_id'
        )->withTimestamps();
    }

    // ============================================================
    // Scopes
    // ============================================================

    /**
     * فلترة الطرق النشطة
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * فلترة حسب الفئة
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}