<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepartmentProgram extends Model
{
    use HasFactory;

    protected $table = 'department_programs';
    public $timestamps = true;
    public $incrementing = false;
    protected $primaryKey = null;

    protected $fillable = [
        'department_id',
        'program_id',
    ];

    protected $casts = [
        'department_id' => 'integer',
        'program_id'    => 'integer',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id', 'program_id');
    }
}