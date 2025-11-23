<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Building;

class Classroom extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'classrooms';
    protected $primaryKey = 'classroom_id';
    public $timestamps = true;

    protected $fillable = [
        'classroom_name',
        'building_id',
        'floor',
        'capacity',
        'latitude',
        'longitude',
        'allowed_distance',
        'classroom_type',
    ];

    protected $casts = [
        'building_id'      => 'integer',
        'floor'            => 'integer',
        'capacity'         => 'integer',
        'classroom_type'   => 'integer',
        'latitude'         => 'decimal:7',
        'longitude'        => 'decimal:7',
        'allowed_distance' => 'decimal:2',
    ];

    public function building()
    {
        // افترض أن جدول `classrooms` يحتوي على `building_id`
        return $this->belongsTo(Building::class, 'building_id');
    }

    public function timetables()
    {
        return $this->hasMany(Timetable::class, 'classroom_id', 'classroom_id');
    }

    public function lectureSessions()
    {
        return $this->hasMany(LectureSession::class, 'actual_classroom_id', 'classroom_id');
    }
}