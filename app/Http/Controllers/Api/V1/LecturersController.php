<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Lecturer;
use App\Models\User;
use App\Models\Department;
use App\Models\AcademicTitle;
use App\Models\UserType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class LecturersController extends Controller
{
    // GET /api/v1/lecturers?college_id=...
    public function index(Request $request)
    {
        $q = Lecturer::query()
            ->select(['lecturer_id','user_id','college_id','department_id','title_id','hire_date','status'])
            ->with([
                'user:user_id,full_name,email,phone,academic_number',
                'academicTitle:title_id,title_name,title_code',
                'department:department_id,department_name,department_code',
            ]);

        if ($request->filled('college_id')) {
            $q->where('college_id', (int)$request->college_id);
        }

        return response()->json($q->get());
    }

    // POST /api/v1/lecturers
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id'       => ['required','integer','exists:users,user_id','unique:lecturers,user_id'],
            'college_id'    => ['required','integer','exists:colleges,college_id'],
            'department_id' => ['required','integer','exists:departments,department_id'],
            'title_id'      => ['nullable','integer','exists:academic_titles,title_id'],
            'hire_date'     => ['required','date'],
            'status'        => ['required','boolean'],
        ]);

        $lec = Lecturer::create($data);
        return response()->json($lec->fresh(), 201);
    }

    // PUT /api/v1/lecturers/{lecturer}
    public function update(Request $request, Lecturer $lecturer)
    {
        $data = $request->validate([
            'user_id'       => ['sometimes','integer','exists:users,user_id', Rule::unique('lecturers','user_id')->ignore($lecturer->lecturer_id,'lecturer_id')],
            'college_id'    => ['sometimes','integer','exists:colleges,college_id'],
            'department_id' => ['sometimes','integer','exists:departments,department_id'],
            'title_id'      => ['nullable','integer','exists:academic_titles,title_id'],
            'hire_date'     => ['sometimes','date'],
            'status'        => ['sometimes','boolean'],
        ]);

        $lecturer->update($data);
        return response()->json($lecturer->fresh());
    }

    // DELETE /api/v1/lecturers/{lecturer}
    public function destroy(Lecturer $lecturer)
    {
        $lecturer->delete();
        return response()->json(['message' => 'Deleted']);
    }

    // POST /api/v1/lecturers/import-csv
    public function importCsv(Request $request)
    {
        $request->validate([
            'college_id' => ['required','integer','exists:colleges,college_id'],
            'file'       => ['required','file','mimes:csv,txt','max:10240'],
        ]);

        $collegeId = (int) $request->college_id;
        $file = $request->file('file');
        $path = $file->getRealPath();

        // تأكد من وجود نوع المستخدم "lecturer"
        $lecturerType = UserType::firstOrCreate(
            ['user_type_code' => 'lecturer'],
            ['user_type_name' => 'محاضر']
        );

        $imported = 0;
        $updated  = 0;
        $skipped  = 0;
        $errors   = [];

        if (($h = fopen($path, 'r')) === false) {
            return response()->json(['message' => 'تعذر قراءة الملف'], 422);
        }

        $header = fgetcsv($h, 0, ',');
        if (!$header) {
            fclose($h);
            return response()->json(['message' => 'ملف CSV بدون رؤوس أعمدة'], 422);
        }

        $header = array_map(fn($x) => trim(mb_strtolower($x)), $header);
        $idx = [
            'full_name'       => array_search('full_name', $header),
            'email'           => array_search('email', $header),
            'phone'           => array_search('phone', $header),
            'academic_number' => array_search('academic_number', $header),
            'gender'          => array_search('gender', $header),
            'department_code' => array_search('department_code', $header),
            'title_code'      => array_search('title_code', $header),
            'hire_date'       => array_search('hire_date', $header),
            'status'          => array_search('status', $header),
        ];

        $rowNum = 1;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($h, 0, ',')) !== false) {
                $rowNum++;

                // تخطي الصفوف الفارغة
                if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) continue;

                $get = function(string $k) use ($idx, $row) {
                    $i = $idx[$k];
                    return $i === false ? null : trim((string)($row[$i] ?? ''));
                };

                $fullName  = $get('full_name');
                $email     = $get('email');
                $phone     = $get('phone');
                $acadNo    = $get('academic_number');
                $genderVal = $get('gender');
                $deptCode  = $get('department_code');
                $titleCode = $get('title_code');
                $hireDate  = $get('hire_date');
                $statusVal = $get('status');

                // الأعمدة المطلوبة طبقاً لقيود الجداول
                if (!$fullName || !$email || !$phone || !$acadNo || !$genderVal || !$deptCode || !$hireDate) {
                    $skipped++;
                    $errors[] = "سطر {$rowNum}: أعمدة مطلوبة ناقصة (full_name,email,phone,academic_number,gender,department_code,hire_date)";
                    continue;
                }

                // القسم (لنفس الكلية)
                $department = Department::where('department_code', $deptCode)
                    ->where('college_id', $collegeId)->first();
                if (!$department) {
                    $skipped++;
                    $errors[] = "سطر {$rowNum}: department_code غير موجود أو لا يتبع الكلية المحددة ({$deptCode})";
                    continue;
                }

                // الدرجة الأكاديمية (اختياري)
                $title = null;
                if ($titleCode) {
                    $title = AcademicTitle::where('title_code', $titleCode)
                        ->where('college_id', $collegeId)->first();
                    if (!$title) {
                        $skipped++;
                        $errors[] = "سطر {$rowNum}: title_code غير موجود أو لا يتبع الكلية المحددة ({$titleCode})";
                        continue;
                    }
                }

                // تحويلات
                $gender = $this->mapGender($genderVal); // 1/2
                if ($gender === null) {
                    $skipped++;
                    $errors[] = "سطر {$rowNum}: gender غير صالح";
                    continue;
                }
                $status = $this->mapStatus($statusVal); // true/false

                // ابحث عن المستخدم عبر academic_number (Unique)
                $user = User::where('academic_number', $acadNo)->first();

                if ($user) {
                    // تحقق تضارب email/phone مع آخرين
                    $emailUsedByOther = User::where('email', $email)->where('user_id', '!=', $user->user_id)->exists();
                    if ($emailUsedByOther) {
                        $skipped++;
                        $errors[] = "سطر {$rowNum}: البريد مستخدم بواسطة مستخدم آخر";
                        continue;
                    }
                    $phoneUsedByOther = User::where('phone', $phone)->where('user_id', '!=', $user->user_id)->exists();
                    if ($phoneUsedByOther) {
                        $skipped++;
                        $errors[] = "سطر {$rowNum}: رقم الجوال مستخدم بواسطة مستخدم آخر";
                        continue;
                    }

                    // تحديث المستخدم
                    $user->update([
                        'full_name'    => $fullName,
                        'email'        => $email,
                        'phone'        => $phone,
                        'college_id'   => $collegeId,
                        'gender'       => $gender,
                        'user_type_id' => $lecturerType->user_type_id, // جعله محاضر
                    ]);
                } else {
                    // تحقق من تضارب قبل الإنشاء
                    if (User::where('email', $email)->exists()) {
                        $skipped++;
                        $errors[] = "سطر {$rowNum}: البريد مستخدم بالفعل";
                        continue;
                    }
                    if (User::where('phone', $phone)->exists()) {
                        $skipped++;
                        $errors[] = "سطر {$rowNum}: رقم الجوال مستخدم بالفعل";
                        continue;
                    }

                    // إنشاء مستخدم جديد (كمحاضر)
                    $user = User::create([
                        'full_name'       => $fullName,
                        'email'           => $email,
                        'phone'           => $phone,
                        'college_id'      => $collegeId,
                        'password'        => Hash::make('12345678'), // كلمة مرور افتراضية
                        'academic_number' => $acadNo,
                        'gender'          => $gender,
                        'user_type_id'    => $lecturerType->user_type_id,
                    ]);
                }

                // أنشئ/حدّث Lecturer (user_id فريد)
                $lecturer = Lecturer::where('user_id', $user->user_id)->first();
                if ($lecturer) {
                    $lecturer->update([
                        'college_id'    => $collegeId,
                        'department_id' => $department->department_id,
                        'title_id'      => $title?->title_id,
                        'hire_date'     => $hireDate,
                        'status'        => $status,
                    ]);
                    $updated++;
                } else {
                    Lecturer::create([
                        'user_id'       => $user->user_id,
                        'college_id'    => $collegeId,
                        'department_id' => $department->department_id,
                        'title_id'      => $title?->title_id,
                        'hire_date'     => $hireDate,
                        'status'        => $status,
                    ]);
                    $imported++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($h);
            return response()->json([
                'message' => 'فشل استيراد الملف',
                'error'   => $e->getMessage(),
            ], 500);
        }

        fclose($h);

        return response()->json([
            'message'  => 'تمت عملية الاستيراد',
            'imported' => $imported,
            'updated'  => $updated,
            'skipped'  => $skipped,
            'errors'   => $errors,
        ]);
    }

    private function mapGender($val): ?int
    {
        if ($val === null) return null;
        $v = mb_strtolower(trim((string)$val));
        return match ($v) {
            '1', 'm', 'ذكر' => 1,
            '2', 'f', 'أنثى', 'انثى' => 2,
            default => null,
        };
    }

    private function mapStatus($val): bool
    {
        $v = mb_strtolower(trim((string)$val));
        return in_array($v, ['1','true','متفرغ','fulltime','yes','y'], true);
    }
}