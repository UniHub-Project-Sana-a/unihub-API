<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'courses';
    protected $primaryKey = 'course_id';
    public $timestamps = true;
    protected $guarded = ['course_id'];

    protected $fillable = [
        'course_name',
        'course_code',
        'is_active',
        'college_id',
        'department_id',
        'program_id',
        'level_id',
        'semester_id',
        'block_id',       
        'credit_hours',
        'course_parts',  
        'weight',   
        'category', 
        'teaching_language',
        'notes',
        'is_approved',
        'approval_date',
        'approved_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'course_parts' => 'array',
        'weight' => 'decimal:2',
        'credit_hours' => 'integer',
        'is_approved' => 'boolean',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    // ✅ العلاقات الأساسية
    public function college()
    {
        return $this->belongsTo(College::class, 'college_id', 'college_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id', 'program_id');
    }

    public function level()
    {
        return $this->belongsTo(Level::class, 'level_id', 'level_id');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_id', 'semester_id');
    }

    public function block()
    {
        return $this->belongsTo(Block::class, 'block_id', 'id');
    }

    // ✅ المتطلبات (Many-to-Many عبر الجدول الوسيط)
    public function prerequisites()
    {
        return $this->belongsToMany(
            Course::class,
            'course_prerequisites',
            'course_id',           
            'prerequisite_course_id',
            'course_id',
            'course_id'
        )->withPivot('type')
          ->wherePivot('type', 'prerequisite')
          ->withTimestamps();
    }

    public function corequisites()
    {
        return $this->belongsToMany(
            Course::class,
            'course_prerequisites',
            'course_id',
            'prerequisite_course_id',
            'course_id',
            'course_id'
        )->withPivot('type')
          ->wherePivot('type', 'corequisite')
          ->withTimestamps();
    }

    // ✅ المقررات التي تعتمد على هذا المقرر
    public function requiredBy()
    {
        return $this->belongsToMany(
            Course::class,
            'course_prerequisites',
            'prerequisite_course_id',
            'course_id',
            'course_id',
            'course_id'
        )->withPivot('type')
          ->withTimestamps();
    }

    // ✅ علاقات أخرى
    public function timetables()
    {
        return $this->hasMany(Timetable::class, 'course_id', 'course_id');
    }

    public function makeupLectureRequests()
    {
        return $this->hasMany(MakeupLecturesRequest::class, 'course_id', 'course_id');
    }

    public function studentExcuseSubmissions()
    {
        return $this->hasMany(StudentExcuseSubmission::class, 'course_id', 'course_id');
    }

    public function studentAttendance()
    {
        return $this->hasManyThrough(
            StudentAttendance::class,
            Timetable::class,
            'course_id',
            'timetable_id',
            'course_id',
            'timetable_id'
        );
    }

    // ============================================================
    // العلاقات الجديدة (أضفها)
    // ============================================================

    /**
     * وصف المقرر
     */
    public function description()
    {
        return $this->hasOne(CourseDescription::class, 'course_id', 'course_id');
    }

    /**
     * مخرجات تعلم المقرر
     */
    public function learningOutcomes()
    {
        return $this->hasMany(CourseLearningOutcome::class, 'course_id', 'course_id')
            ->orderBy('order');
    }

    /**
     * المواضيع والوحدات
     */
    public function topics()
    {
        return $this->hasMany(CourseTopic::class, 'course_id', 'course_id')
            ->orderBy('week');
    }

    /**
     * التكليفات والأنشطة
     */
    public function assignments()
    {
        return $this->hasMany(CourseAssignment::class, 'course_id', 'course_id');
    }

    /**
     * طرق التقييم
     */
    public function assessments()
    {
        return $this->hasMany(CourseAssessment::class, 'course_id', 'course_id')
            ->orderBy('order');
    }

    /**
     * المصادر والمراجع
     */
    public function references()
    {
        return $this->hasMany(CourseReference::class, 'course_id', 'course_id')
            ->orderBy('order');
    }

    /**
     * الضوابط والسياسات
     */
    public function policies()
    {
        return $this->hasMany(CoursePolicy::class, 'course_id', 'course_id')
            ->orderBy('order');
    }

    // ✅ Accessors
    public function getCalculatedCreditHoursAttribute()
    {
        if (!$this->course_parts) {
            return $this->credit_hours;
        }

        return collect($this->course_parts)->sum(function ($part) {
            return round(($part['actual_hours'] ?? 0) * ($part['rate'] ?? 1));
        });
    }

    // ✅ Methods
    public function validateCreditHours()
    {
        $calculated = $this->calculated_credit_hours;
        $stated = $this->credit_hours;

        return abs($calculated - $stated) < 0.01;
    }

    public function getAllPrerequisites()
    {
        return [
            'prerequisites' => $this->prerequisites()->get(),
            'corequisites' => $this->corequisites()->get(),
        ];
    }

    public function hasPrerequisites()
    {
        return $this->prerequisites()->count() > 0;
    }

    public function hasCorequisites()
    {
        return $this->corequisites()->count() > 0;
    }

    // ✅ Scopes
    public function scopeForAcademicSystem($query, $system, $blockBased = false)
    {
        if ($system === 'semester' && !$blockBased) {
            // الفصول: يجب أن يكون لديه semester و level
            return $query->whereNotNull('semester_id')
                         ->whereNotNull('level_id')
                         ->whereNull('block_id');
        }

        if ($system === 'semester' && $blockBased) {
            // الفصول + بلوكات: يجب أن يكون لديه block و level
            return $query->whereNotNull('block_id')
                         ->whereNotNull('level_id')
                         ->whereNull('semester_id');
        }

        if ($system === 'credit' && !$blockBased) {
            // الساعات: لا يجب أن يكون لديه شيء
            return $query->whereNull('semester_id')
                         ->whereNull('block_id')
                         ->whereNull('level_id');
        }

        if ($system === 'credit' && $blockBased) {
            // الساعات + بلوكات: يجب أن يكون لديه block فقط
            return $query->whereNotNull('block_id')
                         ->whereNull('semester_id')
                         ->whereNull('level_id');
        }

        return $query;
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getRouteKeyName()
    {
        return 'course_id';
    }
}