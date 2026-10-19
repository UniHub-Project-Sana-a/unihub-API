# =====================================================================
#  UniHub — Integration API — Smoke Test (PowerShell)
# ---------------------------------------------------------------------
#  اختبار HTTP شامل لواجهات التكامل الخارجي ضد خادم يعمل فعلياً.
#
#  الاستخدام:
#     powershell -ExecutionPolicy Bypass -File docs\integration_smoke_test.ps1 `
#         -BaseUrl "http://127.0.0.1:8099" `
#         -Token   "التوكن-الذي-أصدره-أمر-integration:client"
#
#  ملاحظة: يجب أن يكون عنوان جهازك مضافاً في allowed_ips للعميل.
# =====================================================================

param(
    [string]$BaseUrl = "http://127.0.0.1:8099",
    [Parameter(Mandatory = $true)][string]$Token
)

$ErrorActionPreference = "Stop"

$script:Passed = 0
$script:Failed = 0
$TmpDir = Join-Path $env:TEMP ("unihub-int-" + [guid]::NewGuid().ToString("N"))
New-Item -ItemType Directory -Path $TmpDir | Out-Null

function Write-Result {
    param([string]$Name, [bool]$Ok, [string]$Detail = "")
    if ($Ok) {
        $script:Passed++
        Write-Host ("  [PASS] {0}" -f $Name) -ForegroundColor Green
    } else {
        $script:Failed++
        Write-Host ("  [FAIL] {0} {1}" -f $Name, $Detail) -ForegroundColor Red
    }
}

function Invoke-Api {
    param(
        [string]$Method,
        [string]$Path,
        [string]$Body = "",
        [hashtable]$ExtraHeaders = @{},
        [string]$Authorization = ""
    )

    $bodyFile = Join-Path $TmpDir ([guid]::NewGuid().ToString("N") + ".json")
    $respFile = Join-Path $TmpDir "resp.json"

    if ($Authorization -eq "") { $Authorization = "Bearer $Token" }

    $curlArgs = @(
        "-s", "-o", $respFile, "-w", "%{http_code}", "-X", $Method,
        "-H", "Authorization: $Authorization",
        "-H", "Accept: application/json"
    )

    if ($Body -ne "") {
        [System.IO.File]::WriteAllText($bodyFile, $Body, (New-Object System.Text.UTF8Encoding($false)))
        $curlArgs += @("-H", "Content-Type: application/json", "--data-binary", "@$bodyFile")
    }

    foreach ($key in $ExtraHeaders.Keys) {
        $curlArgs += @("-H", ("{0}: {1}" -f $key, $ExtraHeaders[$key]))
    }

    $curlArgs += ("{0}{1}" -f $BaseUrl, $Path)

    $status = [int](& curl.exe @curlArgs)
    $raw = ""
    if (Test-Path $respFile) { $raw = Get-Content $respFile -Raw }

    $json = $null
    try { $json = $raw | ConvertFrom-Json } catch { $json = $null }

    return [pscustomobject]@{ Status = $status; Raw = $raw; Json = $json }
}

Write-Host ""
Write-Host "=== UniHub Integration API — Smoke Test ===" -ForegroundColor Cyan
Write-Host ("Base URL: {0}" -f $BaseUrl)
Write-Host ""

# ---------------------------------------------------------------------
# 0) Ping (المصادقة بعاملين)
# ---------------------------------------------------------------------
Write-Host "[1] فحص الاتصال والمصادقة (ping)"
$ping = Invoke-Api -Method "POST" -Path "/api/external/v1/ping"
Write-Result "ping يعيد 200 وحالة success" (($ping.Status -eq 200) -and ($ping.Json.status -eq "success")) ("(status={0})" -f $ping.Status)

# رفض بلا توكن
$noAuth = Invoke-Api -Method "POST" -Path "/api/external/v1/ping" -Authorization "Bearer invalid"
Write-Result "التوكن غير الصحيح يُرفض 401" ($noAuth.Status -eq 401) ("(status={0})" -f $noAuth.Status)

# ---------------------------------------------------------------------
# 1) كليات (Upsert)
# ---------------------------------------------------------------------
Write-Host "[2] كليات (upsert)"
$collegeBody = '{ "data": [ { "college_code": "SMOKE-ENG", "college_name": "Smoke Engineering" } ] }'
$college = Invoke-Api -Method "POST" -Path "/api/external/v1/colleges" -Body $collegeBody
Write-Result "إنشاء كلية" (($college.Status -eq 200) -and (($college.Json.inserted_records + $college.Json.updated_records) -ge 1)) ("(status={0})" -f $college.Status)

# ---------------------------------------------------------------------
# 2) قسم
# ---------------------------------------------------------------------
Write-Host "[3] أقسام (ربط بالكلية)"
$deptBody = '{ "data": [ { "department_code": "SMOKE-CE", "department_name": "Smoke Civil", "college_code": "SMOKE-ENG" } ] }'
$dept = Invoke-Api -Method "POST" -Path "/api/external/v1/departments" -Body $deptBody
Write-Result "إنشاء قسم مع حل المفتاح الأجنبي" (($dept.Status -eq 200) -and (($dept.Json.inserted_records + $dept.Json.updated_records) -ge 1)) ("(status={0})" -f $dept.Status)

# ---------------------------------------------------------------------
# 3) طالب (ينشئ users + students)
# ---------------------------------------------------------------------
Write-Host "[4] طلاب (ينشئ الحساب تلقائياً)"
$stamp = Get-Date -Format "yyyyMMddHHmmss"
$studentBody = @"
{ "data": [ {
  "full_name": "Smoke Student",
  "academic_number": "SMOKE-$stamp",
  "email": "smoke-$stamp@univ.edu",
  "gender": "male",
  "college_code": "SMOKE-ENG",
  "department_code": "SMOKE-CE"
} ] }
"@
$student = Invoke-Api -Method "POST" -Path "/api/external/v1/students" -Body $studentBody
Write-Result "إنشاء طالب + حساب مستخدم" (($student.Status -eq 200) -and ($student.Json.inserted_records -ge 1)) ("(status={0})" -f $student.Status)

# ---------------------------------------------------------------------
# 5) التوسعة الأكاديمية (بلوكات/مخرجات/محتوى/تقويم/طرق/تسجيل)
# ---------------------------------------------------------------------
Write-Host "[5] التوسعة الأكاديمية (موارد البنية الأكاديمية الجديدة)"

function Test-Upsert {
    param([string]$Resource, [string]$Body)
    $r = Invoke-Api -Method "POST" -Path ("/api/external/v1/" + $Resource) -Body $Body
    $ok = ($r.Status -eq 200) -and (($r.Json.inserted_records + $r.Json.updated_records) -ge 1)
    Write-Result ("إدخال/تحديث: " + $Resource) $ok ("(status={0}, inserted={1}, updated={2}, skipped={3})" -f $r.Status, $r.Json.inserted_records, $r.Json.updated_records, $r.Json.skipped_records)
}

# كائنات المسار المطلوبة (مرجعية) لأبوية الموارد الجديدة
$prog = '{ "department_code": "SMOKE-CE", "program_name": "Smoke Program", "academic_system": "semester", "block_based": true }'
$lvl  = '{ "program": ' + $prog + ', "level_number": 1 }'
$sem  = '{ "level": ' + $lvl + ', "semester_name": "Smoke Semester", "academic_year": "2026", "term_number": 1 }'

Test-Upsert "programs" ('{ "data": [ { "department_code": "SMOKE-CE", "program_name": "Smoke Program", "academic_system": "semester", "block_based": true } ] }')
Test-Upsert "levels" ('{ "data": [ { "program": ' + $prog + ', "level_number": 1, "level_name": "Smoke Level 1" } ] }')
Test-Upsert "semesters" ('{ "data": [ { "level": ' + $lvl + ', "semester_name": "Smoke Semester", "academic_year": "2026", "term_number": 1 } ] }')
Test-Upsert "courses" ('{ "data": [ { "course_code": "SMOKE-CS101", "course_name": "Smoke CS", "college_code": "SMOKE-ENG", "department_code": "SMOKE-CE" } ] }')
Test-Upsert "blocks" ('{ "data": [ { "program": ' + $prog + ', "level": ' + $lvl + ', "block_number": 1, "block_name": "Smoke Block", "type": "compulsory", "weeks": 16 } ] }')
Test-Upsert "program_learning_outcomes" ('{ "data": [ { "program": ' + $prog + ', "code": "PLO1", "domain": "Knowledge", "description": "Smoke PLO" } ] }')
Test-Upsert "course_learning_outcomes" ('{ "data": [ { "course_code": "SMOKE-CS101", "code": "a1", "domain": "Knowledge", "description": "Smoke CLO" } ] }')
Test-Upsert "course_topics" ('{ "data": [ { "course_code": "SMOKE-CS101", "part": "نظري", "week": 1, "unit_name": "Smoke Unit", "hours": 3 } ] }')
Test-Upsert "course_assignments" ('{ "data": [ { "course_code": "SMOKE-CS101", "part": "نظري", "title": "Smoke Assignment", "week": 2, "assignment_type": "homework" } ] }')
Test-Upsert "assessment_methods" ('{ "data": [ { "program": ' + $prog + ', "name": "Smoke Method", "category": "exam" } ] }')
Test-Upsert "teaching_strategies" ('{ "data": [ { "program": ' + $prog + ', "name": "Smoke Strategy", "category": "lecture" } ] }')
Test-Upsert "course_assessments" ('{ "data": [ { "college_code": "SMOKE-ENG", "course_code": "SMOKE-CS101", "semester": ' + $sem + ', "name": "Smoke Assessment", "max_score": 20, "assessment_type": "midterm_exam" } ] }')
Test-Upsert "outcome_assessment_method" ('{ "data": [ { "course_code": "SMOKE-CS101", "code": "a1", "method": { "program": ' + $prog + ', "name": "Smoke Method" } } ] }')
Test-Upsert "student_groups" ('{ "data": [ { "college_code": "SMOKE-ENG", "department_code": "SMOKE-CE", "program": ' + $prog + ', "level": ' + $lvl + ', "semester": ' + $sem + ', "group_name": "SMOKE-G1" } ] }')
Test-Upsert "student_group_members" ('{ "data": [ { "academic_number": "SMOKE-' + $stamp + '", "college_code": "SMOKE-ENG", "department_code": "SMOKE-CE", "program": ' + $prog + ', "level": ' + $lvl + ', "semester": ' + $sem + ', "group_name": "SMOKE-G1" } ] }')
Test-Upsert "student_grades" ('{ "data": [ { "academic_number": "SMOKE-' + $stamp + '", "assessment": { "college_code": "SMOKE-ENG", "course_code": "SMOKE-CS101", "semester": ' + $sem + ', "name": "Smoke Assessment", "max_score": 20 }, "score": 17.5 } ] }')

# حذف ناعم لمورد جديد (درس) ثم التحقق
$topicDelete = Invoke-Api -Method "DELETE" -Path "/api/external/v1/course_topics" -Body ('{ "data": [ { "course_code": "SMOKE-CS101", "part": "نظري", "week": 1, "unit_name": "Smoke Unit" } ] }')
Write-Result "حذف/تعطيل مورد جديد (course_topics)" (($topicDelete.Status -eq 200) -and ($topicDelete.Json.deactivated_records -ge 1)) ("(status={0}, deactivated={1})" -f $topicDelete.Status, $topicDelete.Json.deactivated_records)

# ---------------------------------------------------------------------
# 6) إعادة المحاولة الآمنة (Idempotency)
# ---------------------------------------------------------------------
Write-Host "[6] إعادة المحاولة الآمنة (Idempotency-Key)"
$idemKey = [guid]::NewGuid().ToString()
$idemBody = '{ "data": [ { "college_code": "SMOKE-MED", "college_name": "Smoke Medicine" } ] }'
$first = Invoke-Api -Method "POST" -Path "/api/external/v1/colleges" -Body $idemBody -ExtraHeaders @{ "Idempotency-Key" = $idemKey }
$second = Invoke-Api -Method "POST" -Path "/api/external/v1/colleges" -Body $idemBody -ExtraHeaders @{ "Idempotency-Key" = $idemKey }
$replayed = ($second.Status -eq 200) -and ($second.Json.inserted_records -eq $first.Json.inserted_records)
Write-Result "نفس المفتاح+الجسم => إعادة الرد دون تكرار" ($replayed) ("(first={0}, second={1})" -f $first.Status, $second.Status)

# ---------------------------------------------------------------------
# 7) مورد غير معروف => 404
# ---------------------------------------------------------------------
Write-Host "[7] مورد غير معروف => 404"
$unknown = Invoke-Api -Method "POST" -Path "/api/external/v1/not_a_resource" -Body '{ "data": [] }'
Write-Result "مورد غير معروف يعيد 404" ($unknown.Status -eq 404) ("(status={0})" -f $unknown.Status)

# ---------------------------------------------------------------------
# النتيجة
# ---------------------------------------------------------------------
Write-Host ""
Write-Host ("=== النتيجة: {0} ناجح، {1} فاشل ===" -f $script:Passed, $script:Failed) -ForegroundColor Cyan
Remove-Item -Recurse -Force $TmpDir -ErrorAction SilentlyContinue

if ($script:Failed -gt 0) { exit 1 } else { exit 0 }
