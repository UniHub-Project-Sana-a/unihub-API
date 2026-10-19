<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserTypePermission extends Model
{
    use HasFactory;

    protected $table = 'user_type_permissions';
    // مفتاح مركب (user_type_id, permission_id, college_id) — سنجعل النموذج بدون زيادة تلقائية
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'user_type_id',
        'permission_id',
        'college_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'user_type_id' => 'integer',
        'permission_id' => 'integer',
        'college_id' => 'integer',
    ];

    public function userType()
    {
        return $this->belongsTo(UserType::class, 'user_type_id', 'user_type_id');
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class, 'permission_id', 'permission_id');
    }

    public function college()
    {
        return $this->belongsTo(College::class, 'college_id', 'college_id');
    }
}