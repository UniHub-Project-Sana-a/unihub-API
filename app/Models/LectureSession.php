<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LectureSession extends Model
{
    use HasFactory;

    protected $table = 'lecture_sessions';
    protected $primaryKey = 'session_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    // ✅ --- تأكد من أن هذه المصفوفة تحتوي على timetable_id --- ✅
    protected $fillable = [
        'timetable_id', // <-- هذا هو الحقل الناقص
        'lecturer_id',
        'session_date',
        'start_time',
        'end_time',
        'actual_classroom_id',
        'session_code',
        'status',
        'is_makeup',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'session_date' => 'date',
        'attendance_overage_alert' => 'boolean',
    ];

    /**
     * Get the timetable entry that this session belongs to.
     */
    public function timetable()
    {
        return $this->belongsTo(Timetable::class, 'timetable_id', 'timetable_id');
    }

    public function actualClassroom() 
    {
        return $this->belongsTo(Classroom::class, 'actual_classroom_id', 'classroom_id');
    }
}