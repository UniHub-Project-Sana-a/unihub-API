<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lecturer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'lecturers';
    protected $primaryKey = 'lecturer_id';
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'college_id',
        'department_id',
        'title_id',
        'hire_date',
        'status',
    ];

    protected $casts = [
        'user_id'       => 'integer',
        'college_id'    => 'integer',
        'department_id' => 'integer',
        'title_id'      => 'integer',
        'hire_date'     => 'date',
        'status'        => 'boolean',
    ];

    public function getRouteKeyName()
    {
        return 'lecturer_id';
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function college()
    {
        return $this->belongsTo(College::class, 'college_id', 'college_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    public function academicTitle()
    {
        return $this->belongsTo(AcademicTitle::class, 'title_id', 'title_id');
    }

    public function timetables()
    {
        return $this->hasMany(Timetable::class, 'lecturer_id', 'lecturer_id');
    }

    public function lecturerAttendances()
    {
        return $this->hasMany(LecturerAttendance::class, 'lecturer_id', 'lecturer_id');
    }

    public function qrCodes()
    {
        return $this->hasMany(QrCode::class, 'created_by', 'lecturer_id');
    }

    public function makeupLectureRequests()
    {
        return $this->hasMany(MakeupLecturesRequest::class, 'lecturer_id', 'lecturer_id');
    }
}