<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningOutcome extends Model
{
    protected $primaryKey = 'outcome_id';
    
    protected $fillable = [
        'course_id', 
        'code', 
        'description', 
        'domain' // Cognitive, Psychomotor, Affective
    ];

    // العلاقة مع المادة
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    // العلاقة مع المواضيع (Many-to-Many)
    public function topics()
    {
        return $this->belongsToMany(CourseTopic::class, 'topic_learning_outcomes', 'outcome_id', 'topic_id');
    }

    // العلاقة مع الأسئلة (عندما نربط سؤال بهدف محدد)
    public function questions()
    {
        return $this->hasMany(QaQuestion::class, 'outcome_id', 'outcome_id');
    }
}