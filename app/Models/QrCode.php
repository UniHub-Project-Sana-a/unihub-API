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

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    // ✅ --- أضف أو تأكد من وجود هذه المصفوفة بالكامل --- ✅
     protected $fillable = [
        'timetable_id',
        'refresh_option_id',
        'qr_code_value',
        'expires_at',
        'is_active',
        'created_by', // ✅ --- تأكد من وجود هذا السطر --- ✅
        'latitude',
        'longitude',
        'allowed_distance',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'allowed_distance' => 'decimal:2',
    ];

    // --- العلاقات (Relationships) ---

    public function timetable()
    {
        return $this->belongsTo(Timetable::class, 'timetable_id', 'timetable_id');
    }

    public function creator()
    {
        // 'created_by' يشير إلى 'lecturer_id'
        return $this->belongsTo(Lecturer::class, 'created_by', 'lecturer_id');
    }

    public function refreshOption()
    {
        return $this->belongsTo(QrRefreshOption::class, 'refresh_option_id', 'option_id');
    }
}