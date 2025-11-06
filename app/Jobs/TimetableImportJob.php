<?php

namespace App\Jobs;

use App\Models\TimetableImportLog;
use App\Models\Timetable; // → استيراد موديل Timetable
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\PdfToText\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class TimetableImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $logId,
        public string $source,
        public int $collegeId,
        public ?int $departmentId = null
    ) {}

    public function handle(): void
    {
        $log = TimetableImportLog::find($this->logId);
        if (!$log) return;

        $imported = 0; $conflicts = 0; $skipped = 0; $errors = [];

        try {
            $ext = strtolower(pathinfo($this->source, PATHINFO_EXTENSION));
            $isUrl = filter_var($this->source, FILTER_VALIDATE_URL);

            $rows = [];
            if ($isUrl) {
                $response = Http::timeout(30)->get($this->source);
                if (!$response->ok()) throw new \RuntimeException('فشل جلب البيانات من API');
                $rows = $this->mapApiRows($response->json());
            } elseif ($ext === 'csv' || $ext === 'txt') {
                $rows = $this->mapCsvRows(Storage::path($this->source));
            } elseif ($ext === 'xlsx' || $ext === 'xls') {
                $sheets = Excel::toArray([], Storage::path($this->source));
                $rows = $this->mapExcelRows($sheets);
            } elseif ($ext === 'pdf') {
                $text = Pdf::getText(Storage::path($this->source), null, ['-layout', '-enc', 'UTF-8']);
                $rows = $this->parseTimetableText($text);
            } else {
                throw new \RuntimeException('صيغة ملف غير مدعومة');
            }

            DB::beginTransaction();

            $academicYear = date('Y') . '/' . (date('Y') + 1);
            $startDate = date('Y') . '-09-01';
            $endDate = (date('Y') + 1) . '-01-15';

            foreach ($rows as $i => $r) {
                $rowNo = $i + 1;
                try {
                    $timetableRow = $this->mapToTimetableRow($r, $this->collegeId, $this->departmentId, $academicYear, $startDate, $endDate);
                    Timetable::create($timetableRow);
                    $imported++;
                } catch (\Illuminate\Database\QueryException $ex) {
                    if ($ex->errorInfo[1] === 1062) {
                        $conflicts++;
                        $errors[] = "سطر {$rowNo}: تعارض في الجدول ({$r['room']}، {$r['day']}، {$r['time']})";
                    } else {
                        $skipped++; $errors[] = "سطر {$rowNo}: خطأ قاعدة بيانات - {$ex->getMessage()}";
                    }
                } catch (\RuntimeException $e) {
                    $skipped++; $errors[] = "سطر {$rowNo}: {$e->getMessage()}";
                }
            }

            DB::commit();

            $status = 'نجح';
            if ($conflicts > 0) $status = 'نجح مع تعارضات';
            elseif ($skipped > 0) $status = 'نجح مع أخطاء';

            $log->update([
                'status' => $status,
                'items'  => $imported,
                'notes'  => "تم: {$imported}, تخطي: {$skipped}, تعارض: {$conflicts}. " . ($errors ? 'الأخطاء: ' . implode('; ', array_slice($errors, 0, 3)) : 'لا توجد أخطاء'),
            ]);

        } catch (\Throwable $e) {
            $log->update(['status'=>'فشل','notes' => $e->getMessage()]);
        }
    }

    private function parseTimetableText(string $text): array
    {
        $lines = preg_split('/\R/u', $text);
        $rows = [];
        $dayRegex = '(الأحد|الاثنين|الثلاثاء|الأربعاء|الخميس)';
        $timeRegex = '(\d{1,2}:\d{2}\s*-\s*\d{1,2}:\d{2})';
        $codeRegex = '([A-Za-z]{2,}\d{2,})';
        $roomRegex = '([A-Za-z0-9\-]+)';

        foreach ($lines as $ln) {
            $line = trim($ln);
            if ($line === '') continue;

            $pattern = "/^{$dayRegex}\s+{$timeRegex}\s+{$codeRegex}\s+(.*?)\s+(?:د\.|أ\.د\.)?\s*([^،]+)\s+{$roomRegex}\s*(?:المجموعة\s*(\S+))?$/u";
            if (preg_match($pattern, $line, $m)) {
                $rows[] = [
                    'day'      => $m[1],
                    'time'     => $m[2],
                    'code'     => $m[3],
                    'course'   => trim($m[4]),
                    'lecturer' => trim($m[5]),
                    'room'     => trim($m[6]),
                    'group'    => trim($m[7] ?? ''),
                ];
            }
        }
        return $rows;
    }
    
    private function parseTimeRange(string $time): array
    {
        preg_match('/(\d{1,2}:\d{2})\s*-\s*(\d{1,2}:\d{2})/', $time, $matches);
        if (count($matches) < 3) throw new \RuntimeException("تنسيق وقت غير صالح: {$time}");
        return [sprintf('%02d:%02d:00', ...explode(':',$matches[1])), sprintf('%02d:%02d:00', ...explode(':',$matches[2]))];
    }
    
    private function mapToTimetableRow(array $ev, ?int $collegeId, ?int $departmentId, string $academicYear, string $startDate, string $endDate): array
    {
        $day = \App\Models\Day::where('day_name',$ev['day'])->first();
        if(!$day) throw new \RuntimeException("اليوم {$ev['day']} غير معروف");
        [$start, $end] = $this->parseTimeRange($ev['time']);
        $period = \App\Models\Period::where('start_time',$start)->where('end_time',$end)->where('college_id',$collegeId)->first();
        if(!$period) throw new \RuntimeException("الفترة {$ev['time']} غير معروفة");
        $course = \App\Models\Course::where('course_code',$ev['code'])->first();
        if(!$course) throw new \RuntimeException("المقرر {$ev['code']} غير معروف");
        $lecturer = \App\Models\Lecturer::whereHas('user', fn($q)=>$q->where('full_name',$ev['lecturer']))->first();
        if(!$lecturer) throw new \RuntimeException("المحاضر {$ev['lecturer']} غير معروف");
        $classroom = \App\Models\Classroom::where('classroom_name',$ev['room'])->first();
        if(!$classroom) throw new \RuntimeException("القاعة {$ev['room']} غير معروفة");
        $group = \App\Models\StudentGroup::where('group_name',$ev['group'])->where('college_id',$collegeId)->first();
        if(!$group) throw new \RuntimeException("المجموعة {$ev['group']} غير معروفة");

        return [
            'course_id'=>$course->course_id, 'lecturer_id'=>$lecturer->lecturer_id,
            'group_id'=>$group->group_id, 'classroom_id'=>$classroom->classroom_id,
            'day_id'=>$day->day_id, 'period_id'=>$period->period_id,
            'lecture_type'=>0, 'status'=>1, 'start_date'=>$startDate, 'end_date'=>$endDate,
            'academic_year'=>$academicYear, 'college_id'=>$collegeId, 'department_id'=>$departmentId,
            'gender_type'=>0, 'lecture_hours'=>(strtotime($end)-strtotime($start))/3600,
        ];
    }

    private function mapCsvRows(string $path): array { /* كما هو */ return []; }
    private function mapExcelRows(array $sheets): array { /* كما هو */ return []; }
    private function mapApiRows($json): array { /* كما هو */ return []; }
}