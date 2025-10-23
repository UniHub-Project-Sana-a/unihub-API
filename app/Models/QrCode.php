<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QrCode extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'qr_codes';
    protected $primaryKey = 'qr_id';
    public $timestamps = true;

    protected $fillable = [
        'timetable_id','refresh_option_id','qr_code_value','generated_at','expires_at',
        'is_active','created_by','latitude','longitude','allowed_distance',
    ];

    protected $casts = [
        'timetable_id'     => 'integer',
        'refresh_option_id'=> 'integer',
        'generated_at'     => 'datetime',
        'expires_at'       => 'datetime',
        'is_active'        => 'boolean',
        'created_by'       => 'integer',
        'latitude'         => 'decimal:7',
        'longitude'        => 'decimal:7',
        'allowed_distance' => 'decimal:2',
    ];

    public function timetable()     { return $this->belongsTo(Timetable::class, 'timetable_id', 'timetable_id'); }
    public function refreshOption() { return $this->belongsTo(QrRefreshOption::class, 'refresh_option_id', 'option_id'); }
    public function createdBy()     { return $this->belongsTo(Lecturer::class, 'created_by', 'lecturer_id'); }
}