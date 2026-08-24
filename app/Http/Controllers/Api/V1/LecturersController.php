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
    
        $lecturerType = \App\Models\UserType::firstOrCreate(
            ['user_type_code' => 'lecturer'],
            ['user_type_name' => 'محاضر']
        );
    
        // جلب البيانات المرجعية
        $departments = \App\Models\Department::where('college_id', $collegeId)->pluck('department_id', 'department_code')->toArray();
        $titles = \App\Models\AcademicTitle::where('college_id', $collegeId)->pluck('title_id', 'title_code')->toArray();
    
        $imported = 0; $updated = 0; $skipped = 0; $errors = [];
    
        // دالة تحويل الترميز وتنظيف النصوص
        $cleanText = function ($text) {
            if (empty($text)) return null;
            
            // معالجة ترميز إكسل العربي (Windows-1256)
            if (!mb_check_encoding($text, 'UTF-8')) {
                $text = @iconv('WINDOWS-1256', 'UTF-8//IGNORE', $text);
            }
    
            // إزالة علامة BOM (مهم جداً للتعرف على أول عمود)
            $text = str_replace("\xEF\xBB\xBF", '', $text);
            
            $trimmed = trim($text);
            return $trimmed === '' ? null : $trimmed;
        };
    
        if (($handle = fopen($path, 'r')) === false) {
            return response()->json(['message' => 'تعذر فتح الملف'], 422);
        }
    
        // 1. تحديد الفاصل وقراءة رأس الصفحة
        $firstLine = fgets($handle);
        $delimiter = (str_contains($firstLine, ';')) ? ';' : ',';
        rewind($handle); // العودة للبداية تماماً
    
        // 2. قراءة رؤوس الأعمدة
        $rawHeader = fgetcsv($handle, 0, $delimiter);
        if (!$rawHeader) {
            fclose($handle);
            return response()->json(['message' => 'الملف فارغ'], 422);
        }
    
        // تنظيف رؤوس الأعمدة (للبحث عن full_name, email الخ)
        $cleanHeader = array_map(function($h) use ($cleanText) {
            $name = $cleanText($h) ?? '';
            // إزالة أي رموز غير نصية قد تختبئ في الرأس
            return strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $name));
        }, $rawHeader);
    
        $headerMap = array_flip($cleanHeader);
        
        $rowNum = 1; // السطر الأول هو العنوان
    
        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rowNum++;
    
                // دالة جلب القيمة بناءً على اسم العمود
                $get = function ($key) use ($headerMap, $row, $cleanText) {
                    $idx = $headerMap[$key] ?? null;
                    return ($idx !== null && isset($row[$idx])) ? $cleanText($row[$idx]) : null;
                };
    
                if (empty(array_filter($row))) continue;
    
                $fullName       = $get('fullname') ?? $get('full_name');
                $email          = $get('email');
                $phone          = $get('phone');
                $academicNumber = $get('academicnumber') ?? $get('academic_number');
                $deptCode       = $get('departmentcode') ?? $get('department_code');
                $titleCode      = $get('titlecode') ?? $get('title_code');
                $hireDateRaw    = $get('hiredate') ?? $get('hire_date');
                $genderVal      = $get('gender');
    
                // تحقق دقيق من الحقول المفقودة
                if (!$fullName || !$email || !$academicNumber || !$deptCode) {
                    $missing = [];
                    if(!$fullName) $missing[] = 'الاسم';
                    if(!$email) $missing[] = 'الإيميل';
                    if(!$academicNumber) $missing[] = 'الرقم الأكاديمي';
                    if(!$deptCode) $missing[] = 'كود القسم';
                    
                    $skipped++;
                    $errors[] = "سطر {$rowNum}: بيانات مفقودة (" . implode(', ', $missing) . ").";
                    continue;
                }
    
                // التحقق من القسم
                if (!isset($departments[$deptCode])) {
                    $skipped++;
                    $errors[] = "سطر {$rowNum}: القسم '{$deptCode}' غير موجود بهذه الكلية.";
                    continue;
                }
    
                // التحقق من التاريخ
                try {
                    $hireDate = \Carbon\Carbon::parse($hireDateRaw)->format('Y-m-d');
                } catch (\Exception $e) {
                    $skipped++;
                    $errors[] = "سطر {$rowNum}: التاريخ '{$hireDateRaw}' غير صالح.";
                    continue;
                }
    
                // فحص الازدواجية
                $existingUser = \App\Models\User::where('academic_number', $academicNumber)->first();
                $duplicate = \App\Models\User::where(function($q) use ($email, $phone) {
                        $q->where('email', $email);
                        if ($phone) $q->orWhere('phone', $phone);
                    })
                    ->when($existingUser, fn($q) => $q->where('user_id', '!=', $existingUser->user_id))
                    ->exists();
    
                if ($duplicate) {
                    $skipped++;
                    $errors[] = "سطر {$rowNum}: الإيميل أو الهاتف مستخدم مسبقاً.";
                    continue;
                }
    
                // تنفيذ الحفظ
                $user = \App\Models\User::updateOrCreate(
                    ['academic_number' => $academicNumber],
                    [
                        'full_name'    => $fullName,
                        'email'        => $email,
                        'phone'        => $phone,
                        'college_id'   => $collegeId,
                        'gender'       => (int)$genderVal ?: 1,
                        'user_type_id' => $lecturerType->user_type_id,
                        'password'     => $existingUser ? $existingUser->password : \Illuminate\Support\Facades\Hash::make('12345678'),
                    ]
                );
    
                \App\Models\Lecturer::updateOrCreate(
                    ['user_id' => $user->user_id],
                    [
                        'college_id'           => $collegeId,
                        'department_id'        => $departments[$deptCode],
                        'title_id'             => $titles[$titleCode] ?? null,
                        'hire_date'            => $hireDate,
                        'status'               => true,
                    ]
                );
    
                $existingUser ? $updated++ : $imported++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'خطأ سطر ' . $rowNum . ': ' . $e->getMessage()], 500);
        } finally {
            fclose($handle);
        }
    
        return response()->json([
            'message' => 'تمت المعالجة',
            'imported' => $imported, 'updated' => $updated, 'skipped' => $skipped, 'errors' => $errors,
        ], 200, [], JSON_UNESCAPED_UNICODE);
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