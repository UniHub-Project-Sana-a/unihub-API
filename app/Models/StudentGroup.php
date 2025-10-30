<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'student_groups';
    protected $primaryKey = 'group_id';
    public $timestamps = true;

    protected $fillable = [
        'college_id',
        'group_name',
    ];

    public function members()
    {
        return $this->hasMany(StudentGroupMember::class, 'group_id', 'group_id');
    }

    public function students()
    {
        return $this->belongsToMany(\App\Models\Student::class, 'student_group_members', 'group_id', 'student_id')
            ->withTimestamps();
    }

    public function timetables()
    {
        return $this->hasMany(Timetable::class, 'group_id', 'group_id');
    }

    public function lecturerGroupNotifications()
    {
        return $this->hasMany(LecturerGroupNotification::class, 'group_id', 'group_id');
    }
}