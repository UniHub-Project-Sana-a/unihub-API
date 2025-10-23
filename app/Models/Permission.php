<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'permissions';
    protected $primaryKey = 'permission_id';
    public $timestamps = true;

    protected $fillable = [
        'permission_key',
        'permission_name',
        'description',
    ];

    public function userTypes()
    {
        return $this->belongsToMany(UserType::class, 'user_type_permissions', 'permission_id', 'user_type_id')
            ->withPivot('college_id')
            ->withTimestamps();
    }

    public function userTypePermissions()
    {
        return $this->hasMany(UserTypePermission::class, 'permission_id', 'permission_id');
    }
}