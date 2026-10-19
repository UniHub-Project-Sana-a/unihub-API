<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationLog extends Model
{
    protected $table = 'integration_logs';

    // سجل إلحاقي: created_at فقط بلا updated_at
    public $timestamps = false;

    protected $fillable = [
        'client_id',
        'method',
        'path',
        'ip',
        'mac',
        'request_payload',
        'request_bytes',
        'http_status',
        'summary',
        'duration_ms',
        'response_bytes',
        'created_at',
    ];

    protected $casts = [
        'summary' => 'array',
        'created_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(IntegrationClient::class, 'client_id');
    }
}
