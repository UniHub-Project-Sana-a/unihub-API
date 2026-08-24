<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramOptionAudit extends Model
{
    protected $table = 'program_option_audits';

    protected $fillable = [
        'program_id',
        'option_type',
        'option_id',
        'action',
        'details',
        'changed_by',
        'changed_at',
    ];

    protected $casts = [
        'details' => 'array',
        'changed_at' => 'datetime',
    ];
}
