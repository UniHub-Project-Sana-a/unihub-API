<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LectureAttachment extends Model
{
    use HasFactory;

    protected $table = 'lecture_attachments';
    protected $primaryKey = 'attachment_id';
    
    protected $fillable = [
        'session_id', 'type', 'title', 'url', 'file_size'
    ];

    // علاقة عكسية مع الجلسة
    public function session()
    {
        return $this->belongsTo(LectureSession::class, 'session_id');
    }
}