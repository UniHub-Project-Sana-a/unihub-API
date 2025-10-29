<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Level extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'levels';
    protected $primaryKey = 'level_id';
    public $timestamps = true;

    protected $fillable = [
        'program_id',
        'level_number',
        'level_name',
    ];

    protected $casts = [
        'department_id' => 'integer',
    ];

    public function getRouteKeyName()
    {
        return 'level_id';
    }

    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id', 'program_id');
    }

    public function semesters()
    {
        return $this->hasMany(Semester::class, 'level_id', 'level_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'level_id', 'level_id');
    }
}