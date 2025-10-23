<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppVersion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'app_versions';
    protected $primaryKey = 'version_id';
    public $timestamps = true;

    protected $fillable = [
        'package_name',
        'version_number',
        'release_date',
        'is_mandatory_update',
        'platform',
        'description',
    ];

    protected $casts = [
        'release_date'        => 'date',
        'is_mandatory_update' => 'boolean',
    ];
}