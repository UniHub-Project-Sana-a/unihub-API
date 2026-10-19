<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Building extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'buildings';
    protected $primaryKey = 'building_id';
    public $timestamps = true;

    protected $fillable = [
        'building_name',
        'building_code',
        'floors_count',
        'college_id',
    ];

    protected $casts = [
        'floors_count' => 'integer',
        'college_id'   => 'integer',
    ];

    public function college()
    {
        return $this->belongsTo(College::class, 'college_id', 'college_id');
    }

    public function classrooms()
    {
        return $this->hasMany(Classroom::class, 'building_id', 'building_id');
    }
}