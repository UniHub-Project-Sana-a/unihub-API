<?php

namespace App\Models;
// مهم مهم جدا نفذ امر php artisan storage:link في متصفح الجامعة















































use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class College extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'colleges';
    protected $primaryKey = 'college_id';
    public $timestamps = true;
    protected $appends = ['logoUrl'];

    protected $fillable = [
        'college_name',
        'college_code',
        'college_logo',
    ];

    // Relations
    public function departments()
    {
        return $this->hasMany(Department::class, 'college_id', 'college_id');
    }

    public function buildings()
    {
        return $this->hasMany(Building::class, 'college_id', 'college_id');
    }

    public function periods()
    {
        return $this->hasMany(Period::class, 'college_id', 'college_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'college_id', 'college_id');
    }

    public function lecturers()
    {
        return $this->hasMany(Lecturer::class, 'college_id', 'college_id');
    }

    public function academicTitles()
    {
        return $this->hasMany(AcademicTitle::class, 'college_id', 'college_id');
    }

    public function timetables()
    {
        return $this->hasMany(Timetable::class, 'college_id', 'college_id');
    }

    public function userTypePermissions()
    {
        return $this->hasMany(UserTypePermission::class, 'college_id', 'college_id');
    }

    public function getLogoUrlAttribute()
    {
        if ($this->college_logo) {
            // سيقوم بتحويل colleges/15.png إلى http://domain.com/storage/colleges/15.png
            return asset('storage/' . $this->college_logo);
        }
        return null;
    }

    public function studentGroups()
    {
        // الكلية تملك مجموعات طلابية كثيرة
        return $this->hasMany(StudentGroup::class, 'college_id', 'college_id');
    }
}