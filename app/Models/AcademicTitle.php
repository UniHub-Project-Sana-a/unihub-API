<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicTitle extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'academic_titles';
    protected $primaryKey = 'title_id';
    public $timestamps = true;

    protected $fillable = [
        'college_id',
        'title_name',
        'title_code',
        'hourly_price',
        'lecture_price',
    ];

    protected $casts = [
        'college_id'    => 'integer',
        'hourly_price'  => 'decimal:2',
        'lecture_price' => 'decimal:2',
    ];

    public function college()
    {
        return $this->belongsTo(College::class, 'college_id', 'college_id');
    }

    public function lecturers()
    {
        return $this->hasMany(Lecturer::class, 'title_id', 'title_id');
    }
}