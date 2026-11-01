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
use Illuminate\Support\Facades\Log;

class LecturersController extends Controller
{
    // GET /api/v1/lecturers?college_id=...&q=...
    public function index(Request $request)
    {
        try {
            $query = Lecturer::query()->with([
                'user:user_id,full_name,email,phone,academic_number',
                'academicTitle:title_id,title_name,title_code,hourly_price',
                'department:department_id,department_name,department_code',
                'college:college_id,college_name',
            ]);

            // ✅ محرك البحث الشامل بالاسم والرقم الوظيفي والبريد والهاتف
            if ($request->filled('q')) {
                $q = $request->query('q');
                $query->where(function ($sub) use ($q) {
                    $sub->whereHas('user', function ($uq) use ($q) {
                        $uq->where('full_name', 'like', "%{$q}%")
                           ->orWhere('academic_number', 'like', "%{$q}%")
                           ->orWhere('email', 'like', "%{$q}%")
                           ->orWhere('phone', 'like', "%{$q}%");
                    })->orWhereHas('department', function ($dq) use ($q) {
                        $dq->where('department_name', 'like', "%{$q}%");
                    })->orWhereHas('academicTitle', function ($tq) use ($q) {
                        $tq->where('title_name', 'like', "%{$q}%");
                    });
                });
            }

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

            // ✅ فلتر حسب department_id
            if ($request->filled('department_id')) {
                $query->where('department_id', (int)$request->department_id);
            }

            // ✅ فلتر حسب title_id (الدرجة العلمية)
            if ($request->filled('title_id')) {
                $query->where('title_id', (int)$request->title_id);
            }

            // ✅ فلتر حسب الحالة الوظيفية
            if ($request->has('status') && $request->query('status') !== '') {
                $query->where('status', filter_var($request->query('status'), FILTER_VALIDATE_BOOLEAN));
            }
            
            // ✅ فلتر لاستثناء كلية معينة
            if ($request->filled('exclude_college_id')) {
                $query->where('college_id', '!=', (int)$request->query('exclude_college_id'));
            }

            $perPage = (int) $request->query('per_page', 0);
            if ($perPage > 0 && !$request->boolean('all')) {
                return response()->json($query->paginate($perPage));
            }

            return response()->json($query->get());
        } catch (\Throwable $e) {
            Log::error('LecturersController index error: ' . $e->getMessage());
            return response()->json([
                'message' => 'تعذر جلب المحاضرين: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
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

    /**
     * سلة المحذوفات (Soft Delete) لأعضاء هيئة التدريس (المحاضرين).
     * POST /api/v1/lecturers/trash
     */
    public function trash(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:list,restore,force_delete',
            'college_id' => 'required|integer|min:1',
            'type' => 'required_if:action,restore,force_delete|in:lecturers',
            'id' => 'required_if:action,restore,force_delete|integer|min:1',
        ]);

        $collegeId = (int) $validated['college_id'];

        $queries = [
            'lecturers' => fn () => Lecturer::onlyTrashed()->where('college_id', $collegeId),
        ];

        if ($validated['action'] === 'list') {
            $labels = [
                'lecturers' => 'أعضاء هيئة التدريس',
            ];

            $groups = [];
            foreach ($queries as $type => $queryFactory) {
                $groups[] = [
                    'type' => $type,
                    'label' => $labels[$type],
                    'records' => $queryFactory()
                        ->with(['user' => fn ($q) => $q->withTrashed()])
                        ->get()
                        ->map(function ($record) {
                            $data = $record->getAttributes();
                            $data['deleted_at'] = $record->deleted_at?->toISOString();

                            $name = $record->user?->full_name;

                            return [
                                'id' => $record->getKey(),
                                'name' => $name ?: ('محاضر #' . $record->getKey()),
                                'deleted_at' => $record->deleted_at?->toISOString(),
                                'data' => $data,
                            ];
                        })->values(),
                ];
            }

            return response()->json(['groups' => $groups]);
        }

        $type = $validated['type'];
        $record = $queries[$type]()->whereKey((int) $validated['id'])->firstOrFail();

        DB::transaction(function () use ($record, $validated) {
            if ($validated['action'] === 'restore') {
                $record->restore();
            } else {
                $record->forceDelete();
            }
        });

        return response()->json([
            'message' => $validated['action'] === 'restore'
                ? 'تمت استعادة العنصر بنجاح'
                : 'تم حذف العنصر نهائياً',
        ]);
    }

    // POST /api/v1/lecturers/import-csv
    public function importCsv(Request $request)
    {
        $request->validate([
            'college_id' => ['required', 'integer', 'exists:colleges,college_id'],
            'file'       => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:20480'],
        ]);
    
        $collegeId = (int) $request->college_id;
        $file = $request->file('file');
        $path = $file->getRealPath();
        $ext = strtolower($file->getClientOriginalExtension());
    
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
            if ($text === null || $text === '') return null;
            $text = (string)$text;
            
            // معالجة ترميز إكسل العربي (Windows-1256)
            if (!mb_check_encoding($text, 'UTF-8')) {
                $text = @iconv('WINDOWS-1256', 'UTF-8//IGNORE', $text);
            }
    
            // إزالة علامة BOM
            $text = str_replace("\xEF\xBB\xBF", '', $text);
            
            $trimmed = trim($text);
            return $trimmed === '' ? null : $trimmed;
        };

        $allRows = [];
        $rawHeader = null;

        if (in_array($ext, ['xlsx', 'xls'])) {
            if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
                return response()->json(['message' => 'مكتبة معالجة الإكسل غير متوفرة'], 500);
            }
            $type = \PhpOffice\PhpSpreadsheet\IOFactory::identify($path);
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($type);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($path);
            $sheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            if (!$sheet || count($sheet) < 2) {
                return response()->json(['message' => 'ملف الإكسل فارغ'], 422);
            }
            // تحديد سطر الرأس (الأول أو الذي يحتوي على academic_number/الرقم)
            $headerIndex = 0;
            foreach ($sheet as $idx => $pRow) {
                $joined = mb_strtolower(implode(' ', array_filter($pRow)));
                if (str_contains($joined, 'academic_number') || str_contains($joined, 'academicnumber') || str_contains($joined, 'full_name') || str_contains($joined, 'الرقم') || str_contains($joined, 'الاسم')) {
                    $headerIndex = $idx;
                    break;
                }
            }
            $rawHeader = $sheet[$headerIndex];
            $allRows = array_slice($sheet, $headerIndex + 1);
        } else {
            if (($handle = fopen($path, 'r')) === false) {
                return response()->json(['message' => 'تعذر فتح الملف'], 422);
            }
            $firstLine = fgets($handle);
            $delimiter = (str_contains($firstLine, ';')) ? ';' : ',';
            rewind($handle);
            $rawHeader = fgetcsv($handle, 0, $delimiter);
            if (!$rawHeader) {
                fclose($handle);
                return response()->json(['message' => 'الملف فارغ'], 422);
            }
            while (($r = fgetcsv($handle, 0, $delimiter)) !== false) {
                $allRows[] = $r;
            }
            fclose($handle);
        }
    
        // تنظيف وتوحيد رؤوس الأعمدة
        $cleanHeader = array_map(function($h) use ($cleanText) {
            $name = $cleanText($h) ?? '';
            $norm = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $name));
            return match ($name) {
                'الرقم الوظيفي', 'الرقم الأكاديمي', 'الرقم' => 'academic_number',
                'الاسم', 'اسم المحاضر', 'الاسم الكامل' => 'full_name',
                'البريد', 'البريد الإلكتروني', 'الإيميل' => 'email',
                'الهاتف', 'الجوال', 'رقم الهاتف' => 'phone',
                'كود القسم', 'القسم' => 'department_code',
                'الرتبة', 'اللقب الأكاديمي', 'كود الرتبة' => 'title_code',
                'تاريخ التعيين', 'التعيين' => 'hire_date',
                'الجنس', 'النوع' => 'gender',
                default => $norm,
            };
        }, $rawHeader);
    
        $headerMap = array_flip($cleanHeader);
        $rowNum = 1;
    
        DB::beginTransaction();
        try {
            foreach ($allRows as $row) {
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