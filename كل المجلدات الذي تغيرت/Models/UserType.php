<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserType extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'user_types';
    protected $primaryKey = 'user_type_id';
    public $timestamps = true;

    protected $fillable = [
        'user_type_name',
        'user_type_code',
    ];

    // Relations
    public function users()
    {
        return $this->hasMany(User::class, 'user_type_id', 'user_type_id');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_type_permissions', 'user_type_id', 'permission_id')
            ->withPivot('college_id')
            ->withTimestamps();
    }

    public function userTypePermissions()
    {
        return $this->hasMany(UserTypePermission::class, 'user_type_id', 'user_type_id');
    }
}