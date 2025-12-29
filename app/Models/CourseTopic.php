<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseTopic extends Model
{
    protected $table = 'course_topics';
    protected $primaryKey = 'topic_id';

    protected $fillable = [
        'course_id', 
        'title', 
        'description', 
        'order_index'
    ];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    // العلاقة مع الأهداف (Many-to-Many)
    public function learningOutcomes()
    {
        return $this->belongsToMany(LearningOutcome::class, 'topic_learning_outcomes', 'topic_id', 'outcome_id');
    }

    // الموضوع يحتوي على أسئلة كثيرة
    public function questions()
    {
        return $this->hasMany(QaQuestion::class, 'topic_id', 'topic_id');
    }
}