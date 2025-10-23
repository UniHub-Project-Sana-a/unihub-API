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
        'program_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function departments()
    {
        return $this->belongsToMany(Department::class, 'department_programs', 'program_id', 'department_id')->withTimestamps();
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