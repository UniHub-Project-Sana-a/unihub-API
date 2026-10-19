<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Program extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'programs';
    protected $primaryKey = 'program_id';
    public $timestamps = true;

    protected $fillable = [
        'department_id',
        'program_name',
        'academic_system',
        'block_based',
        'is_active',
        'total_hours'
    ];

    protected $casts = [
        'block_based' => 'boolean',
        'is_active' => 'boolean',
        'total_hours' => 'integer'
    ];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

        // مستويات البرنامج
    public function levels()
    {
        return $this->hasMany(Level::class, 'program_id', 'program_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'program_id', 'program_id');
    }

    public function departmentPrograms()
    {
        return $this->hasMany(DepartmentProgram::class, 'program_id', 'program_id');
    }
}