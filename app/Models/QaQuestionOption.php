<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QaQuestionOption extends Model
{
    protected $primaryKey = 'option_id';

    protected $fillable = [
        'question_id', 
        'option_text', 
        'is_correct'
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function question()
    {
        return $this->belongsTo(QaQuestion::class, 'question_id', 'question_id');
    }
}