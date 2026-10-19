<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'departments';
    protected $primaryKey = 'department_id';
    public $timestamps = true;

    protected $fillable = [
        'department_name',
        'department_code',
        'college_id',
    ];

    protected $casts = [
        'college_id' => 'integer',
    ];

    public function college()
    {
        return $this->belongsTo(College::class, 'college_id', 'college_id');
    }

    public function levels()
    {
        return $this->hasMany(Level::class, 'department_id', 'department_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'department_id', 'department_id');
    }

    public function lecturers()
    {
        return $this->hasMany(Lecturer::class, 'department_id', 'department_id');
    }

    public function programs()
    {
        return $this->hasMany(Program::class, 'department_id', 'department_id');
    }

    public function timetables()
    {
        return $this->hasMany(Timetable::class, 'department_id', 'department_id');
    }
}