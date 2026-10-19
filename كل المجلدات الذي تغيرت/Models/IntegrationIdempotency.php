<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * سجل إعادة المحاولة الآمنة (Idempotency) لطلبات التكامل الخارجي.
 *
 * لكل (client_id + idempotency_key) سجل واحد يحمل بصمة الطلب وردّه المخزَّن.
 */
class IntegrationIdempotency extends Model
{
    protected $table = 'integration_idempotency';

    // created_at فقط (لا updated_at)
    public $timestamps = false;

    protected $fillable = [
        'client_id',
        'idempotency_key',
        'request_hash',
        'http_status',
        'response',
        'created_at',
        'expires_at',
    ];

    protected $casts = [
        'response' => 'array',
        'created_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}
