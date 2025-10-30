<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\StudentGroup;
use App\Models\Student;
use App\Models\User;
use App\Models\UserType;
use App\Models\Department;
use App\Models\Program;
use App\Models\Level;
use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class StudentGroupsController extends Controller
{
    // إنشاء/إيجاد مجموعة وإلحاق طلاب بها عبر academic_numbers
    public function upsertAndAttach(Request $request)
    {
        $data = $request->validate([
            'college_id'         => ['required','integer','exists:colleges,college_id'],
            'group_name'         => ['required','string','max:100'],
            'academic_numbers'   => ['required','array','min:1'],
            'academic_numbers.*' => ['string','max:50'],
        ]);

        $group = StudentGroup::firstOrCreate(
            ['college_id' => $data['college_id'], 'group_name' => $data['group_name']],
            []
        );

        $studentIds = Student::query()
            ->where('college_id', $data['college_id'])
            ->whereHas('user', fn($uq) => $uq->whereIn('academic_number', $data['academic_numbers']))
            ->pluck('student_id')
            ->all();

        $group->students()->syncWithoutDetaching($studentIds);

        return response()->json([
            'message'   => 'Attached',
            'group_id'  => $group->group_id,
            'attached'  => count($studentIds),
        ]);
    }

    // استيراد CSV: ينشئ Users (نوع student) + Students ثم يلحِقهم بالمجموعة
    public function importCsv(Request $request)
    {
        $data = $request->validate([
            'college_id'    => ['required','integer','exists:colleges,college_id'],
            'department_id' => ['required','integer','exists:departments,department_id'],
            'program_id'    => ['required','integer','exists:programs,program_id'],
            'level_id'      => ['required','integer','exists:levels,level_id'],
            'semester_id'   => ['nullable','integer','exists:semesters,semester_id'],
            'course_id'     => ['nullable','integer'], // اختياري
            'cohort'        => ['required','string','max:50'],
            'group_name'    => ['required','string','max:100'],
            'file'          => ['required','file','mimes:csv,txt','max:10240'],
        ]);

        $path = $request->file('file')->getRealPath();

        $studentType = UserType::firstOrCreate(
            ['user_type_code' => 'student'],
            ['user_type_name' => 'طالب']
        );

        $imported = 0; $updated = 0; $skipped = 0; $errors = [];

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
            'status'          => array_search('status', $header),
        ];

        DB::beginTransaction();
        try {
            $studentIds = [];

            $rowNum = 1;
            while (($row = fgetcsv($h, 0, ',')) !== false) {
                $rowNum++;
                if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) continue;

                $get = function (string $k) use ($idx, $row) {
                    $i = $idx[$k];
                    return $i === false ? null : trim((string)($row[$i] ?? ''));
                };

                $fullName  = $get('full_name');
                $email     = $get('email');
                $phone     = $get('phone');
                $acadNo    = $get('academic_number');
                $genderVal = $get('gender');
                $statusVal = $get('status');

                if (!$fullName || !$email || !$phone || !$acadNo || !$genderVal) {
                    $skipped++; $errors[] = "سطر {$rowNum}: حقول مطلوبة ناقصة (full_name,email,phone,academic_number,gender)";
                    continue;
                }

                $gender = $this->mapGender($genderVal);     // 1/2
                $status = $this->mapStatus($statusVal);     // true/false
                if ($gender === null) {
                    $skipped++; $errors[] = "سطر {$rowNum}: gender غير صالح";
                    continue;
                }

                $res = $this->upsertUserAndStudent([
                    'full_name'   => $fullName,
                    'email'       => $email,
                    'phone'       => $phone,
                    'academic_no' => $acadNo,
                    'gender'      => $gender,
                    'status'      => $status,
                    'college_id'  => (int) $data['college_id'],
                    'department_id' => (int) $data['department_id'],
                    'program_id'    => (int) $data['program_id'],
                    'level_id'      => (int) $data['level_id'],
                    // يمكنك تخزين cohort/semester_id/course_id في جداول أخرى عند الحاجة
                ], $studentType->user_type_id, $rowNum);

                if ($res['ok']) {
                    $studentIds[] = $res['student_id'];
                    $res['created'] ? $imported++ : $updated++;
                } else {
                    $skipped++; $errors[] = $res['error'];
                }
            }

            // upsert group + attach students
            $group = StudentGroup::firstOrCreate(
                ['college_id' => (int)$data['college_id'], 'group_name' => $data['group_name']],
                []
            );
            $group->students()->syncWithoutDetaching($studentIds);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($h);
            return response()->json(['message' => 'فشل الاستيراد', 'error' => $e->getMessage()], 500);
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

    // استيراد من مصدر خارجي (JSON)
    public function importExternal(Request $request)
    {
        $data = $request->validate([
            'source_url'   => ['required','url'],
            'college_id'   => ['required','integer','exists:colleges,college_id'],
            'department_id'=> ['required','integer','exists:departments,department_id'],
            'program_id'   => ['required','integer','exists:programs,program_id'],
            'level_id'     => ['required','integer','exists:levels,level_id'],
            'semester_id'  => ['nullable','integer','exists:semesters,semester_id'],
            'course_id'    => ['nullable','integer'],
            'cohort'       => ['required','string','max:50'],
            'group_name'   => ['required','string','max:100'],
        ]);

        $studentType = UserType::firstOrCreate(
            ['user_type_code' => 'student'],
            ['user_type_name' => 'طالب']
        );

        $resp = Http::timeout(30)->get($data['source_url']);
        if (!$resp->ok()) {
            return response()->json(['message' => 'تعذر جلب البيانات من المصدر الخارجي'], 422);
        }

        $rows = $resp->json();
        if (!is_array($rows)) {
            return response()->json(['message' => 'تنسيق بيانات غير مدعوم من المصدر الخارجي'], 422);
        }

        $imported=0; $updated=0; $skipped=0; $errors=[];

        DB::beginTransaction();
        try {
            $studentIds = [];

            $rowNum = 0;
            foreach ($rows as $row) {
                $rowNum++;
                $fullName  = trim((string)($row['full_name'] ?? ''));
                $email     = trim((string)($row['email'] ?? ''));
                $phone     = trim((string)($row['phone'] ?? ''));
                $acadNo    = trim((string)($row['academic_number'] ?? ''));
                $genderVal = trim((string)($row['gender'] ?? ''));
                $statusVal = trim((string)($row['status'] ?? '1'));

                if (!$fullName || !$email || !$phone || !$acadNo || !$genderVal) {
                    $skipped++; $errors[] = "سطر {$rowNum}: حقول مطلوبة ناقصة";
                    continue;
                }

                $gender = $this->mapGender($genderVal);
                $status = $this->mapStatus($statusVal);
                if ($gender === null) {
                    $skipped++; $errors[] = "سطر {$rowNum}: gender غير صالح";
                    continue;
                }

                $res = $this->upsertUserAndStudent([
                    'full_name'   => $fullName,
                    'email'       => $email,
                    'phone'       => $phone,
                    'academic_no' => $acadNo,
                    'gender'      => $gender,
                    'status'      => $status,
                    'college_id'  => (int) $data['college_id'],
                    'department_id' => (int) $data['department_id'],
                    'program_id'    => (int) $data['program_id'],
                    'level_id'      => (int) $data['level_id'],
                ], $studentType->user_type_id, $rowNum);

                if ($res['ok']) {
                    $studentIds[] = $res['student_id'];
                    $res['created'] ? $imported++ : $updated++;
                } else {
                    $skipped++; $errors[] = $res['error'];
                }
            }

            $group = StudentGroup::firstOrCreate(
                ['college_id' => (int)$data['college_id'], 'group_name' => $data['group_name']],
                []
            );
            $group->students()->syncWithoutDetaching($studentIds);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'فشل الاستيراد', 'error' => $e->getMessage()], 500);
        }

        return response()->json([
            'message'  => 'تمت عملية الاستيراد',
            'imported' => $imported,
            'updated'  => $updated,
            'skipped'  => $skipped,
            'errors'   => $errors,
        ]);
    }

    // Helpers
    private function upsertUserAndStudent(array $data, int $studentTypeId, int $rowNum): array
    {
        // ابحث المستخدم عبر academic_number أولًا
        $user = User::where('academic_number', $data['academic_no'])->first();

        // تحقق تضارب email/phone لمستخدم آخر
        if ($user) {
            $emailUsedByOther = User::where('email', $data['email'])->where('user_id', '!=', $user->user_id)->exists();
            if ($emailUsedByOther) return ['ok'=>false, 'error'=>"سطر {$rowNum}: البريد مستخدم بواسطة مستخدم آخر"];
            $phoneUsedByOther = User::where('phone', $data['phone'])->where('user_id', '!=', $user->user_id)->exists();
            if ($phoneUsedByOther) return ['ok'=>false, 'error'=>"سطر {$rowNum}: رقم الجوال مستخدم بواسطة مستخدم آخر"];

            $user->update([
                'full_name'    => $data['full_name'],
                'email'        => $data['email'],
                'phone'        => $data['phone'],
                'college_id'   => $data['college_id'],
                'gender'       => $data['gender'],
                'user_type_id' => $studentTypeId,
            ]);
            $created = false;
        } else {
            if (User::where('email', $data['email'])->exists())  return ['ok'=>false, 'error'=>"سطر {$rowNum}: البريد مستخدم بالفعل"];
            if (User::where('phone', $data['phone'])->exists())  return ['ok'=>false, 'error'=>"سطر {$rowNum}: رقم الجوال مستخدم بالفعل"];

            $user = User::create([
                'full_name'       => $data['full_name'],
                'email'           => $data['email'],
                'phone'           => $data['phone'],
                'college_id'      => $data['college_id'],
                'password'        => Hash::make('12345678'),
                'academic_number' => $data['academic_no'],
                'gender'          => $data['gender'],
                'user_type_id'    => $studentTypeId,
            ]);
            $created = true;
        }

        // upsert Student
        $student = Student::where('user_id', $user->user_id)->first();
        if ($student) {
            $student->update([
                'college_id'    => $data['college_id'],
                'department_id' => $data['department_id'],
                'program_id'    => $data['program_id'] ?? null,
                'level_id'      => $data['level_id'],
                'status'        => $data['status'],
            ]);
        } else {
            $student = Student::create([
                'user_id'       => $user->user_id,
                'college_id'    => $data['college_id'],
                'department_id' => $data['department_id'],
                'program_id'    => $data['program_id'] ?? null,
                'level_id'      => $data['level_id'],
                'status'        => $data['status'],
            ]);
        }

        return ['ok'=>true, 'created'=>$created, 'student_id'=>$student->student_id];
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
        return in_array($v, ['1','true','نشط','active','yes','y'], true);
    }

    public function students(\App\Models\StudentGroup $student_group)
{
    $students = $student_group->students()
        ->with(['user:user_id,full_name,gender,academic_number'])
        ->get()
        ->map(function ($s) {
            return [
                'student_id' => $s->student_id,
                'user' => [
                    'full_name' => $s->user?->full_name,
                    'gender' => $s->user?->gender,
                    'academic_number' => $s->user?->academic_number,
                ],
            ];
        });

    return response()->json($students);
}
}