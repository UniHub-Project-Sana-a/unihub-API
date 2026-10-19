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
        'actual_start_time',

        'actual_end_time',
        'end_latitude',
        'end_longitude',
        'is_ended_remotely',
        'early_exit_reason'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'session_date' => 'date:Y-m-d',
        'attendance_overage_alert' => 'boolean',

        'actual_start_time' => 'datetime',
        'actual_end_time' => 'datetime',
        'is_ended_remotely' => 'boolean',
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

    //     /**
    //  * Prepare a date for array / JSON serialization.
    //  *
    //  * @param  \DateTimeInterface  $date
    //  * @return string
    //  */
    // protected function serializeDate(\DateTimeInterface $date)
    // {
    //     // هذا السطر يجبر لارافيل على إرسال التاريخ بصيغة السنة-الشهر-اليوم فقط
    //     // دون أي تلاعب بالتوقيت أو تحويل لـ UTC
    //     return $date->format('Y-m-d');
    // }
}