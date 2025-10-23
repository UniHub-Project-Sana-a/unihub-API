<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Period extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'periods';
    protected $primaryKey = 'period_id';
    public $timestamps = true;

    protected $fillable = [
        'college_id',
        'period_name',
        'start_time',
        'end_time',
        'session_type',
    ];

    protected $casts = [
        'college_id' => 'integer',
        // لأن الأعمدة من نوع TIME سنحتفظ بها كسلاسل نصية
        'start_time' => 'string',
        'end_time'   => 'string',
    ];

    public function college()
    {
        return $this->belongsTo(College::class, 'college_id', 'college_id');
    }

    public function timetables()
    {
        return $this->hasMany(Timetable::class, 'period_id', 'period_id');
    }
}