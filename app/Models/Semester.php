<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Semester extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'semesters';
    protected $primaryKey = 'semester_id';
    public $timestamps = true;

    protected $fillable = [
        'level_id',
        'term_number',
        'semester_name',
        'academic_year',
    ];

    protected $casts = [
        'level_id' => 'integer',
    ];

    public function getRouteKeyName()
    {
        return 'semester_id';
    }

    public function level()
    {
        return $this->belongsTo(Level::class, 'level_id', 'level_id');
    }

    public function courses()
    {
        return $this->hasMany(Course::class, 'semester_id', 'semester_id');
    }
}