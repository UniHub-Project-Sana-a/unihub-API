<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Day extends Model
{
    use HasFactory;

    protected $table = 'days';
    protected $primaryKey = 'day_id';
    public $timestamps = true; // أضفنا created_at/updated_at بالمهاجرة السابقة

    protected $fillable = [
        'day_name',
    ];

    public function timetables()
    {
        return $this->hasMany(Timetable::class, 'day_id', 'day_id');
    }
}