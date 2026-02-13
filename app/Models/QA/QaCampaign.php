<?php

namespace App\Models\QA;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Semester; // تأكد من مسار موديل الفصول الدراسية

class QaCampaign extends Model
{
    use HasFactory;

    protected $table = 'qa_campaigns';
    protected $primaryKey = 'campaign_id';
    protected $guarded = ['campaign_id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_published' => 'boolean',
    ];

    // علاقة: الحملة تتبع نموذج معين
    public function form()
    {
        return $this->belongsTo(QaForm::class, 'form_id');
    }

    // علاقة: الحملة تتبع فصل دراسي
    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_id', 'semester_id');
    }

    // علاقة: الحملة تحتوي على استجابات طلاب (Submissions)
    public function submissions()
    {
        return $this->hasMany(QaSubmission::class, 'campaign_id');
    }

    // Scope: لجلب الحملات النشطة حالياً فقط
    public function scopeActive($query)
    {
        $today = now()->format('Y-m-d');
        return $query->where('is_published', true)
                     ->where('start_date', '<=', $today)
                     ->where('end_date', '>=', $today);
    }

    public function timetables()
    {
        return $this->belongsToMany(
            \App\Models\Timetable::class, 
            'qa_campaign_assignments', 
            'campaign_id', 
            'timetable_id'
        );
    }
}