<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CoursePolicy extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'course_policies';
    protected $primaryKey = 'policy_id';
    protected $guarded = ['policy_id'];

    protected $fillable = [
        'course_id',
        'policy_number',
        'title',
        'content',
        'is_fixed',
        'order',
    ];

    protected $casts = [
        'is_fixed' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ============================================================
    // الثوابت (Constants)
    // ============================================================

    const FIXED_POLICIES = [
        1 => 'الحضور والغياب',
        2 => 'الحضور المتأخر',
        3 => 'ضوابط الاختبار',
        4 => 'التكليفات والمهام والمشاريع',
        5 => 'الغش',
        6 => 'التزوير وانتحال الهوية',
        7 => 'سياسات أخرى',
    ];

    // ============================================================
    // العلاقات
    // ============================================================

    /**
     * المقرر الذي ينتمي له الضابط
     */
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    // ============================================================
    // Scopes
    // ============================================================

    /**
     * فلترة الضوابط الثابتة
     */
    public function scopeFixed($query)
    {
        return $query->where('is_fixed', true);
    }

    /**
     * فلترة الضوابط المضافة
     */
    public function scopeAdditional($query)
    {
        return $query->where('is_fixed', false);
    }

    /**
     * فلترة حسب المقرر
     */
    public function scopeByCourse($query, $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    // ============================================================
    // الدوال المساعدة
    // ============================================================

    /**
     * الحصول على اسم الضابط الثابت
     */
    public static function getFixedPolicyName($policyNumber)
    {
        return self::FIXED_POLICIES[$policyNumber] ?? null;
    }

    /**
     * التحقق من أن الضابط ثابت
     */
    public function isFixed()
    {
        return $this->is_fixed === true;
    }

    /**
     * التحقق من أن الضابط يمكن تعديله
     */
    public function canBeEdited()
    {
        return $this->is_fixed === false;
    }
}