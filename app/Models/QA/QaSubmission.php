<?php

namespace App\Models\QA;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Student;
use App\Models\Lecturer;
use App\Models\Course; // انتبه لاسم الموديل لديك (Course أو Courses)

class QaSubmission extends Model
{
    use HasFactory;

    protected $table = 'qa_submissions';
    protected $primaryKey = 'submission_id';
    protected $guarded = ['submission_id'];

    protected $casts = [
        'is_practical' => 'boolean',
        'submission_date_timestamp' => 'timestamp',
    ];

    // علاقة: الاستمارة تتبع حملة معينة
    public function campaign()
    {
        return $this->belongsTo(QaCampaign::class, 'campaign_id');
    }

    // علاقة: الاستمارة لها تفاصيل (أجوبة الأسئلة)
    public function answers()
    {
        return $this->hasMany(QaAnswer::class, 'submission_id');
    }

    // علاقة: الطالب الذي قام بالتقييم
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    // علاقة: المحاضر المُقَيَّم
    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class, 'lecturer_id', 'lecturer_id');
    }

    // علاقة: المادة
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }
}