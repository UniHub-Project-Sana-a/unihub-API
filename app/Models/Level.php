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
        'level_name',
        'department_id',
    ];

    protected $casts = [
        'department_id' => 'integer',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
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