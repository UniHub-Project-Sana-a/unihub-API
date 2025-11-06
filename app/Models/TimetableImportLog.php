<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimetableImportLog extends Model
{
    protected $table = 'timetable_import_logs';
    protected $fillable = ['source','items','status','notes','user_id'];
}