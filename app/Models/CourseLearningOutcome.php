<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseLearningOutcome extends Model
{
    use SoftDeletes;

    protected $table = 'course_learning_outcomes';
    protected $primaryKey = 'clo_id';
    public $incrementing = true;

    protected $fillable = [
        'course_id',
        'code',
        'domain',
        'description',
        'weight',
        'plo_id',
        'plo_weight',
        'order',
        'is_active'
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'plo_weight' => 'decimal:2',
        'order' => 'integer',
        'is_active' => 'boolean',
        'course_id' => 'integer',
        'plo_id' => 'integer',
    ];

    protected $hidden = [
        'deleted_at'
    ];

    protected $appends = [
        'domain_label',
        'domain_prefix'
    ];

    /**
     * ✅ علاقة مع المقرر
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    /**
     * ✅ علاقة مع مخرج تعلم البرنامج
     */
    public function programLearningOutcome(): BelongsTo
    {
        return $this->belongsTo(ProgramLearningOutcome::class, 'plo_id', 'plo_id');
    }

    /**
     * ✅ Accessor: اسم المجال بالعربية
     */
    public function getDomainLabelAttribute(): string
    {
        $labels = [
            'Knowledge' => 'المعرفة',
            'Intellectual' => 'الفكري',
            'Professional' => 'المهني',
            'General' => 'العام',
        ];
        
        return $labels[$this->domain] ?? $this->domain;
    }

    /**
     * ✅ Accessor: بادئة المجال (a, b, c, d)
     */
    public function getDomainPrefixAttribute(): string
    {
        $prefixes = [
            'Knowledge' => 'a',
            'Intellectual' => 'b',
            'Professional' => 'c',
            'General' => 'd',
        ];
        
        return $prefixes[$this->domain] ?? 'a';
    }

    /**
     * ✅ Scope: حسب المقرر
     */
    public function scopeForCourse($query, int $courseId)
    {
        return $query->where('course_id', $courseId);
    }

    /**
     * ✅ Scope: حسب المجال
     */
    public function scopeByDomain($query, string $domain)
    {
        return $query->where('domain', $domain);
    }

    /**
     * ✅ Scope: الترتيب
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('domain')->orderBy('order');
    }

    /**
     * ✅ Scope: النشطة فقط
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * ✅ دالة: التحقق من مجموع الأوزان
     */
    public static function getTotalWeight(int $courseId, ?int $excludeCloId = null): float
    {
        $query = self::where('course_id', $courseId);
        
        if ($excludeCloId) {
            $query->where('clo_id', '!=', $excludeCloId);
        }
        
        return (float) $query->sum('weight');
    }

    /**
     * ✅ دالة: عدد المخرجات حسب المجال
     */
    public static function countByDomain(int $courseId, string $domain): int
    {
        return self::where('course_id', $courseId)
            ->where('domain', $domain)
            ->count();
    }

    /**
     * ✅ دالة: توليد الرمز التالي
     */
    public static function generateNextCode(int $courseId, string $domain): string
    {
        $prefix = self::getDomainPrefixStatic($domain);
        
        $maxCode = self::where('course_id', $courseId)
            ->where('domain', $domain)
            ->where('code', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(code, 2) AS UNSIGNED) DESC')
            ->value('code');
        
        if (!$maxCode) {
            return $prefix . '1';
        }
        
        $number = (int) substr($maxCode, 1);
        return $prefix . ($number + 1);
    }

    /**
     * ✅ دالة مساعدة: الحصول على بادئة المجال (static)
     */
    private static function getDomainPrefixStatic(string $domain): string
    {
        $prefixes = [
            'Knowledge' => 'a',
            'Intellectual' => 'b',
            'Professional' => 'c',
            'General' => 'd',
        ];
        
        return $prefixes[$domain] ?? 'a';
    }

    /**
     * ✅ دالة: التحقق من الحد الأقصى للمخرجات
     */
    public static function canAddMore(int $courseId): bool
    {
        return self::where('course_id', $courseId)->count() < 8;
    }
}