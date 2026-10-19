<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QaQuestion extends Model
{
    protected $table = 'qa_questions_topics';
    protected $primaryKey = 'question_id';

    protected $fillable = [
        'topic_id', 
        'outcome_id', // اختياري
        'question_text', 
        'question_type', // 'MCQ'
        'difficulty_level', // 1, 2, 3
        'is_active'
    ];

    public function topic()
    {
        return $this->belongsTo(CourseTopic::class, 'topic_id', 'topic_id');
    }

    public function learningOutcome()
    {
        return $this->belongsTo(LearningOutcome::class, 'outcome_id', 'outcome_id');
    }

    // السؤال له خيارات متعددة
    public function options()
    {
        return $this->hasMany(QaQuestionOption::class, 'question_id', 'question_id');
    }
}