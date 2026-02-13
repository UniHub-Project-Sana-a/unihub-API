<?php

namespace App\Models\QA;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QaQuestion extends Model
{
    use HasFactory;

    protected $table = 'qa_questions';
    protected $primaryKey = 'question_id';
    protected $guarded = ['question_id'];

    // علاقة: السؤال ينتمي لمجال معين
    public function domain()
    {
        return $this->belongsTo(QaDomain::class, 'domain_id');
    }

    // علاقة: السؤال له إجابات كثيرة (من جداول الإجابات)
    public function answers()
    {
        return $this->hasMany(QaAnswer::class, 'question_id');
    }
}