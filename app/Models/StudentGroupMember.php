<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentGroupMember extends Model
{
    use HasFactory;

    protected $table = 'student_group_members';
    public $timestamps = true;
    public $incrementing = false;
    protected $primaryKey = null;

    protected $fillable = [
        'student_id',
        'group_id',
    ];

    protected $casts = [
        'student_id' => 'integer',
        'group_id'   => 'integer',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }

    public function group()
    {
        return $this->belongsTo(StudentGroup::class, 'group_id', 'group_id');
    }
}