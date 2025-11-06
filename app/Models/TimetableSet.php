<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimetableSet extends Model
{
    use SoftDeletes;

    protected $table = 'timetable_sets';
    protected $primaryKey = 'schedule_id';
    protected $fillable = [
        'college_id','semester_id','department_id','name',
        'start_date','end_date','weeks_count','status','is_primary','notes'
    ];

    public function entries()
    {
        return $this->hasMany(TimetableEntry::class, 'schedule_id', 'schedule_id');
    }
}