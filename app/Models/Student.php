<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use SoftDeletes;

    protected $table = 'students';
    protected $primaryKey = 'student_id';
    public $timestamps = true;

    protected $fillable = [
        'user_id','college_id','department_id','level_id','program_id','semester_id','block_id','status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id', 'user_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id', 'program_id');
    }

    public function level()
    {
        return $this->belongsTo(Level::class, 'level_id', 'level_id');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_id', 'semester_id');
    }

    public function block()
    {
        return $this->belongsTo(Block::class, 'block_id', 'id');
    }

    public function groups()
    {
        return $this->belongsToMany(\App\Models\StudentGroup::class, 'student_group_members', 'student_id', 'group_id')
            ->withTimestamps();
    }
}