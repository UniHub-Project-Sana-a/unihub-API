<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'students';
    protected $primaryKey = 'student_id';
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'college_id',
        'department_id',
        'level_id',
        'program_id',
        'status',
    ];

    protected $casts = [
        'user_id'       => 'integer',
        'college_id'    => 'integer',
        'department_id' => 'integer',
        'level_id'      => 'integer',
        'program_id'    => 'integer',
        'status'        => 'boolean',
    ];

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

    public function level()
    {
        return $this->belongsTo(Level::class, 'level_id', 'level_id');
    }

    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id', 'program_id');
    }

    public function groups()
    {
        return $this->belongsToMany(StudentGroup::class, 'student_group_members', 'student_id', 'group_id')
            ->withTimestamps();
    }

    public function attendances()
    {
        return $this->hasMany(StudentAttendance::class, 'student_id', 'student_id');
    }
}