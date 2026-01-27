<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Support\Facades\Auth;
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
use App\Models\LecturerPayout;
use App\Models\FinancialCycle;

class LecturersController extends Controller
{
    // GET /api/v1/lecturers?college_id=...
    public function index(Request $request)
    {
        $query = Lecturer::query()->with([
            'user:user_id,full_name,email,phone,academic_number',
            'academicTitle:title_id,title_name,title_code',
            'department:department_id,department_name,department_code',
        ]);

        if ($request->filled('user_id')) {
            $query->where('user_id', (int)$request->user_id);
        }

        // ✅ فلتر حسب can_teach_externally
        if ($request->filled('can_teach_externally')) {
            $canTeach = filter_var($request->query('can_teach_externally'), FILTER_VALIDATE_BOOLEAN);
            $query->where('can_teach_externally', $canTeach);
        }

        // ✅ فلتر حسب college_id
        if ($request->filled('college_id')) {
            $query->where('college_id', (int)$request->college_id);
        }
        
        // ✅ فلتر لاستثناء كلية معينة
        if ($request->filled('exclude_college_id')) {
            $query->where('college_id', '!=', (int)$request->query('exclude_college_id'));
        }

        // يمكنك إضافة فلاتر أخرى هنا مثل department_id لو احتجت

        // تغيير بسيط: استخدم Resources لتنسيق الاستجابة بشكل أفضل (اختياري ولكن موصى به)
        // return \App\Http\Resources\V1\LecturerResource::collection($query->get());
        return response()->json($query->get());
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
            'can_teach_externally' => ['sometimes', 'boolean'], 
        ]);

        $lec = Lecturer::create($data);
        return response()->json($lec->load(['user', 'department', 'academicTitle']), 201);
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
            'can_teach_externally' => ['sometimes', 'boolean'],
        ]);

        $lecturer->update($data);
        return response()->json($lecturer->load(['user', 'department', 'academicTitle']));
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
            'college_id' => ['required', 'integer', 'exists:colleges,college_id'],
            'file'       => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);
    
        $collegeId = (int) $request->college_id;
        $file = $request->file('file');
        $path = $file->getRealPath();
    
        $lecturerType = UserType::firstOrCreate(
            ['user_type_code' => 'lecturer'],
            ['user_type_name' => 'محاضر']
        );
    
        $imported = 0;
        $updated  = 0;
        $skipped  = 0;
        $errors   = [];
    
        if (($handle = fopen($path, 'r')) === false) {
            return response()->json(['message' => 'تعذر قراءة الملف'], 422);
        }

        $firstLine = fgets($handle);
        rewind($handle); // إعادة المؤشر للبداية
        $delimiter = (str_contains($firstLine, ';')) ? ';' : ',';
    
        $header = fgetcsv($handle, 0,  $delimiter);
        if (!$header) {
            fclose($handle);
            return response()->json(['message' => 'ملف CSV بدون رؤوس أعمدة'], 422);
        }

        $header[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header[0]);
    
        // تنظيف أسماء الأعمدة وفهرستها
        $headerMap = array_flip(array_map(fn($h) => trim(mb_strtolower($h)), $header));
        
        $rowNum = 1;
    
        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rowNum++;
    
                // دالة مساعدة لجلب القيمة من الصف بناءً على اسم العمود
                $get = function (string $key) use ($headerMap, $row) {
                    $index = $headerMap[$key] ?? null;
                    return ($index !== null && isset($row[$index])) ? trim($row[$index]) : null;
                };
    
                // تخطي الصفوف الفارغة تمامًا
                if (empty(array_filter($row, fn($val) => trim($val) !== ''))) {
                    continue;
                }
    
                // --- 1. جلب البيانات من الصف ---
                $fullName              = $get('full_name');
                $email                 = $get('email');
                $phone                 = $get('phone');
                $academicNumber        = $get('academic_number');
                $genderVal             = $get('gender');
                $departmentCode        = $get('department_code');
                $titleCode             = $get('title_code');
                $hireDate              = $get('hire_date');
                $statusVal             = $get('status');
                $canTeachExternallyVal = $get('can_teach_externally');
    
                // --- 2. التحقق من الحقول الإجبارية الأساسية ---
                $requiredFields = compact('fullName', 'email', 'phone', 'academicNumber', 'genderVal', 'departmentCode', 'hireDate');
                if (in_array(null, $requiredFields, true)) {
                    $skipped++;
                    $errors[] = "سطر {$rowNum}: الحقول الإجبارية (full_name, email, phone, academic_number, gender, department_code, hire_date) يجب أن تكون موجودة وتحتوي على قيمة.";
                    continue;
                }
    
                // --- 3. التحقق من الكيانات المرتبطة (القسم والدرجة) ---
                $department = Department::where('department_code', $departmentCode)->where('college_id', $collegeId)->first();
                if (!$department) {
                    $skipped++;
                    $errors[] = "سطر {$rowNum}: رمز القسم '{$departmentCode}' غير موجود أو لا يتبع هذه الكلية.";
                    continue;
                }
    
                $title = null;
                if ($titleCode) {
                    $title = AcademicTitle::where('title_code', $titleCode)->where('college_id', $collegeId)->first();
                    if (!$title) {
                        $skipped++;
                        $errors[] = "سطر {$rowNum}: رمز الدرجة الأكاديمية '{$titleCode}' غير موجود أو لا يتبع هذه الكلية.";
                        continue;
                    }
                }
    
                // --- 4. تحويل القيم ---
                $gender = $this->mapGender($genderVal);
                if ($gender === null) {
                    $skipped++;
                    $errors[] = "سطر {$rowNum}: قيمة الجنس '{$genderVal}' غير صالحة.";
                    continue;
                }
                $status = $this->mapStatus($statusVal);
                $canTeachExternally = ($canTeachExternallyVal === null || $canTeachExternallyVal === '') ? false : $this->mapStatus($canTeachExternallyVal);
    
                // --- 5. التعامل مع سجل المستخدم (User) ---
                $user = User::where('academic_number', $academicNumber)->first();
                $userExists = (bool) $user;
    
                // التحقق من تضارب البريد الإلكتروني أو الهاتف مع مستخدمين آخرين
                $conflictQuery = User::where(fn($q) => $q->where('email', $email)->orWhere('phone', $phone));
                if($userExists) $conflictQuery->where('user_id', '!=', $user->user_id);
                if($conflictQuery->exists()){
                    $skipped++;
                    $errors[] = "سطر {$rowNum}: البريد الإلكتروني أو رقم الهاتف مستخدم بالفعل من قبل مستخدم آخر.";
                    continue;
                }
    
                $userData = [
                    'full_name'       => $fullName,
                    'email'           => $email,
                    'phone'           => $phone,
                    'college_id'      => $collegeId,
                    'gender'          => $gender,
                    'user_type_id'    => $lecturerType->user_type_id,
                ];
                
                if(!$userExists) {
                    $userData['password'] = Hash::make('12345678'); // كلمة مرور افتراضية للجدد فقط
                    $userData['academic_number'] = $academicNumber;
                }
                
                $user = User::updateOrCreate(['academic_number' => $academicNumber], $userData);
    
                // --- 6. التعامل مع سجل المحاضر (Lecturer) ---
                $lecturerData = [
                    'college_id'           => $collegeId,
                    'department_id'        => $department->department_id,
                    'title_id'             => $title?->title_id,
                    'hire_date'            => $hireDate,
                    'status'               => $status,
                    'can_teach_externally' => $canTeachExternally,
                ];
    
                $lecturer = Lecturer::updateOrCreate(['user_id' => $user->user_id], $lecturerData);
                
                // تحديث العدادات
                if ($lecturer->wasRecentlyCreated) $imported++;
                elseif ($lecturer->wasChanged()) $updated++;
    
            } // نهاية حلقة while
    
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            return response()->json([
                'message' => 'حدث خطأ فادح أثناء الاستيراد.',
                'error'   => $e->getMessage(),
                'line'    => "Line {$e->getLine()} in {$e->getFile()}",
            ], 500);
        }
    
        fclose($handle);
    
        return response()->json([
            'message'  => 'تمت عملية الاستيراد بنجاح.',
            'imported' => $imported,
            'updated'  => $updated,
            'skipped'  => $skipped,
            'errors'   => $errors,
        ], 200);
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

        /**
     * عرض المستحقات المالية للمحاضر مع الفلاتر
     */
    public function getFinancialDues(Request $request)
    {
        // 1. التحقق من المدخلات
        $request->validate([
            'year' => 'required|numeric',
            'month' => 'required|numeric|min:1|max:12',
            'college_id' => 'nullable|exists:colleges,college_id',
            'status' => 'nullable|in:paid,pending,approved', // paid = تم الصرف, pending = قيد الانتظار
        ]);

        // تحديد المحاضر الحالي (يفترض وجود Middleware للمصادقة)
        // $lecturerId = auth()->user()->lecturer_id; 
        // سنستخدم ID ثابت للتجربة إذا لم تكن المصادقة جاهزة، غيره لاحقاً:
        $lecturerId = $request->user()->lecturer_id;

        // تنسيق الشهر والسنة ليطابق قاعدة البيانات (MM-YYYY)
        // مثال: شهر 1 سنة 2026 يصبح "01-2026"
        $monthYear = sprintf('%02d-%d', $request->month, $request->year);

        // 2. بناء الاستعلام الأساسي
        $query = LecturerPayout::with(['cycle.college'])
            ->where('lecturer_id', $lecturerId)
            ->whereHas('cycle', function ($q) use ($monthYear) {
                $q->where('month_year', $monthYear);
            });

        // تطبيق فلتر الكلية إذا تم اختياره
        if ($request->filled('college_id') && $request->college_id != 'all') {
            $query->whereHas('cycle', function ($q) use ($request) {
                $q->where('college_id', $request->college_id);
            });
        }

        // تطبيق فلتر الحالة (للتبديل بين شاشات "قيد الانتظار" و "تم الصرف")
        if ($request->filled('status')) {
            // ملاحظة: في الواجهة "قيد الانتظار" قد تشمل pending و approved ولم تدفع بعد
            if ($request->status == 'pending') {
                $query->whereIn('status', ['pending', 'approved']);
            } else {
                $query->where('status', $request->status);
            }
        }

        // تنفيذ الاستعلام لجلب البيانات
        $payouts = $query->get();

        // 3. حساب الإجمالي (للعرض في البطاقة الكبيرة العلوية)
        $totalAmount = $payouts->sum('net_amount');

        // 4. تجهيز قائمة الكليات التي درس فيها المحاضر (من أجل الفلتر الأفقي في الواجهة)
        // نجلب كل الكليات التي لدى المحاضر مستحقات فيها لهذا الشهر بغض النظر عن الفلتر الحالي
        $availableColleges = LecturerPayout::where('lecturer_id', $lecturerId)
            ->whereHas('cycle', function ($q) use ($monthYear) {
                $q->where('month_year', $monthYear);
            })
            ->with('cycle.college')
            ->get()
            ->pluck('cycle.college')
            ->unique('college_id')
            ->values()
            ->map(function ($college) {
                return [
                    'id' => $college->college_id,
                    'name' => $college->name, // تأكد أن اسم العمود في جدول الكليات هو name أو name_ar
                ];
            });

        // 5. تنسيق البيانات للواجهة (Mapping)
        $formattedPayouts = $payouts->map(function ($payout) {
            return [
                'id' => $payout->payout_id,
                'college_name' => $payout->cycle->college->name ?? 'غير محدد',
                'amount' => number_format($payout->net_amount, 0), // تنسيق الرقم بدون كسور واضحة
                'currency' => 'ريال',
                // وصف العملية الحسابية كما في الصورة (عدد ساعات * سعر الساعة)
                // ملاحظة: الجدول يخزن إجمالي الساعات، سنفترض عدد المحاضرات أو نعرض الساعات فقط
                'details_text' => "{$payout->total_hours} ساعة × " . number_format($payout->hourly_rate, 0) . " ريال/ساعة",
                'status_label' => self::getStatusLabel($payout->status),
                'status_color' => self::getStatusColor($payout->status),
                'date' => $payout->created_at->format('Y-m-d'),
            ];
        });

        // 6. إرجاع الرد JSON
        return response()->json([
            'status' => true,
            'message' => 'تم جلب البيانات بنجاح',
            'data' => [
                'summary' => [
                    'total_amount' => number_format($totalAmount, 0),
                    'currency' => 'ريال يمني',
                    'period' => $monthYear
                ],
                'filters' => [
                    'colleges' => $availableColleges
                ],
                'payouts' => $formattedPayouts
            ]
        ]);
    }

    // دوال مساعدة لتنسيق النصوص والألوان
    private static function getStatusLabel($status)
    {
        return match ($status) {
            'paid' => 'تم الصرف',
            'approved' => 'معتمد',
            'pending' => 'قيد المراجعة',
            default => $status,
        };
    }

    private static function getStatusColor($status)
    {
        return match ($status) {
            'paid' => '#28a745', // أخضر
            'approved' => '#17a2b8', // سماوي
            'pending' => '#ffc107', // أصفر
            default => '#6c757d',
        };
    }
}