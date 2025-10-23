<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MakeupLecturesRequest extends Model
{
    use HasFactory;

    protected $table = 'makeup_lectures_requests';
    protected $primaryKey = 'request_id';
    public $timestamps = true;

    protected $fillable = [
        'lecturer_id','course_id','group_id','requested_date','status','notification_status',
    ];

    protected $casts = [
        'lecturer_id'    => 'integer',
        'course_id'      => 'integer',
        'group_id'       => 'integer',
        'requested_date' => 'date',
        'status'         => 'integer',
        'notification_status' => 'integer',
    ];

    public function lecturer() { return $this->belongsTo(Lecturer::class, 'lecturer_id', 'lecturer_id'); }
    public function course()   { return $this->belongsTo(Course::class, 'course_id', 'course_id'); }
    public function group()    { return $this->belongsTo(StudentGroup::class, 'group_id', 'group_id'); }
}