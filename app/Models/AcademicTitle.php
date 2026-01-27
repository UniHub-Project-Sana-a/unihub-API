<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicTitle extends Model
{
    use SoftDeletes;

    protected $table = 'academic_titles';
    protected $primaryKey = 'title_id';
    public $timestamps = true;

    protected $fillable = [
        'title_name',
        'title_code',
        'hourly_price',
        'college_id',
    ];

    protected $casts = [
        'hourly_price'  => 'decimal:2',
    ];

    public function getRouteKeyName()
    {
        return 'title_id';
    }

    public function college()
    {
        return $this->belongsTo(College::class, 'college_id', 'college_id');
    }
}