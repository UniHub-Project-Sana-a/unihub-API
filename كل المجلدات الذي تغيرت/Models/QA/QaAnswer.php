<?php

namespace App\Models\QA;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QaAnswer extends Model
{
    use HasFactory;

    protected $table = 'qa_answers';
    protected $primaryKey = 'answer_id';
    protected $guarded = ['answer_id'];

    // هذا الجدول لا يحتاج timestamps (created_at, updated_at) لتخفيف الحجم لأنه سيحتوي ملايين السجلات
    public $timestamps = false;

    // علاقة: الإجابة تتبع استمارة معينة
    public function submission()
    {
        return $this->belongsTo(QaSubmission::class, 'submission_id');
    }

    // علاقة: الإجابة تتبع سؤال معين
    public function question()
    {
        return $this->belongsTo(QaQuestion::class, 'question_id');
    }
}