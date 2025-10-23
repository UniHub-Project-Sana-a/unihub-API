<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QrRefreshOption extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'qr_refresh_options';
    protected $primaryKey = 'option_id';
    public $timestamps = true;

    protected $fillable = [
        'interval_seconds','description','is_active','created_at',
    ];

    protected $casts = [
        'interval_seconds' => 'integer',
        'is_active'        => 'boolean',
        'created_at'       => 'datetime',
    ];

    public function qrCodes()
    {
        return $this->hasMany(QrCode::class, 'refresh_option_id', 'option_id');
    }
}