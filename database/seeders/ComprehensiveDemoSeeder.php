<?php

namespace Database\Seeders;

use App\Models\AcademicTitle;
use App\Models\Block;
use App\Models\Building;
use App\Models\Classroom;
use App\Models\College;
use App\Models\Course;
use App\Models\Day;
use App\Models\Department;
use App\Models\Lecturer;
use App\Models\LectureSession;
use App\Models\Level;
use App\Models\Period;
use App\Models\Program;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentGroup;
use App\Models\StudentGroupMember;
use App\Models\Timetable;
use App\Models\User;
use App\Models\UserType;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ComprehensiveDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('🚀 Starting Comprehensive Demo Data Seeding for Sana\'a University...');

        // 1. Fetch User Types
        $userTypes = UserType::all()->keyBy('user_type_code');
        $adminType = $userTypes['admin'] ?? null;
        $deanType = $userTypes['dean'] ?? null;
        $deptHeadType = $userTypes['dept_head'] ?? null;
        $acadType = $userTypes['academic_affairs'] ?? null;
        $ctrlType = $userTypes['control'] ?? null;
        $roomMgrType = $userTypes['classroom_manager'] ?? null;
        $lecturerType = $userTypes['lecturer'] ?? null;
        $studentType = $userTypes['student'] ?? null;

        $passwordHash = Hash::make('password123');

        // 2. Seed Colleges
        $medCollege = College::updateOrCreate(
            ['college_code' => 'FMS-SU'],
            [
                'college_name' => 'كلية الطب والعلوم الصحية - جامعة صنعاء',
                'college_logo' => 'colleges/logos/fms_sanaa.png'
            ]
        );

        $dentCollege = College::updateOrCreate(
            ['college_code' => 'DENT-SU'],
            [
                'college_name' => 'كلية طب الأسنان - جامعة صنعاء',
                'college_logo' => 'colleges/logos/dent_sanaa.png'
            ]
        );

        $csCollege = College::updateOrCreate(
            ['college_code' => 'FCIT-SU'],
            [
                'college_name' => 'كلية الحاسوب وتكنولوجيا المعلومات',
                'college_logo' => 'colleges/logos/fcit_sanaa.png'
            ]
        );

        $this->command?->info("✅ Colleges seeded: {$medCollege->college_name}, {$dentCollege->college_name}, {$csCollege->college_name}");

        // 3. Academic Titles for Medical College
        $titles = [
            ['title_code' => 'PROF', 'title_name' => 'أستاذ دكتور (بروفيسور)', 'hourly_price' => 15000],
            ['title_code' => 'ASSOC_PROF', 'title_name' => 'أستاذ مشارك', 'hourly_price' => 12000],
            ['title_code' => 'ASST_PROF', 'title_name' => 'أستاذ مساعد', 'hourly_price' => 10000],
            ['title_code' => 'LECT', 'title_name' => 'مدرس سريري / أخصائي', 'hourly_price' => 7500],
            ['title_code' => 'TA', 'title_name' => 'معيد سريري', 'hourly_price' => 5000],
        ];

        $titleMap = [];
        foreach ($titles as $t) {
            $createdTitle = AcademicTitle::updateOrCreate(
                ['college_id' => $medCollege->college_id, 'title_code' => $t['title_code']],
                ['title_name' => $t['title_name'], 'hourly_price' => $t['hourly_price']]
            );
            $titleMap[$t['title_code']] = $createdTitle->title_id;
        }

        // 4. Periods for Medical College
        $periodsData = [
            ['name' => 'الفترة الأولى (08:00 - 10:00 ص)', 'start' => '08:00:00', 'end' => '10:00:00'],
            ['name' => 'الفترة الثانية (10:00 - 12:00 م)', 'start' => '10:00:00', 'end' => '12:00:00'],
            ['name' => 'الفترة الثالثة (12:00 - 02:00 م)', 'start' => '12:00:00', 'end' => '14:00:00'],
            ['name' => 'الفترة الرابعة (02:00 - 04:00 م)', 'start' => '14:00:00', 'end' => '16:00:00'],
        ];

        $periodMap = [];
        foreach ($periodsData as $p) {
            $createdPeriod = Period::updateOrCreate(
                ['college_id' => $medCollege->college_id, 'period_name' => $p['name']],
                ['start_time' => $p['start'], 'end_time' => $p['end']]
            );
            $periodMap[] = $createdPeriod;
        }

        // 5. Buildings & Classrooms for Medical College
        $bldRazi = Building::updateOrCreate(
            ['college_id' => $medCollege->college_id, 'building_code' => 'BLD-RAZI'],
            ['building_name' => 'مبنى الرازي - مجمع العلوم الطبية الأساسية', 'floors_count' => 4]
        );

        $bldSina = Building::updateOrCreate(
            ['college_id' => $medCollege->college_id, 'building_code' => 'BLD-SINA'],
            ['building_name' => 'مبنى ابن سينا - الأكاديمي والسريري', 'floors_count' => 3]
        );

        $classroomsData = [
            [
                'building_id' => $bldRazi->building_id,
                'name' => 'مدرج الشهيد الزبيري (المدرج الكبير)',
                'floor' => 1,
                'capacity' => 250,
                'type' => 0,
                'display' => 'smart_board',
                'has_pc' => true,
                'notes' => 'مدرج مجهز بنظام صوتي متطور وتكييف مركزي مخصص للدفعات الكبيرة'
            ],
            [
                'building_id' => $bldRazi->building_id,
                'name' => 'قاعة الرازي 1 (المستوى الأول)',
                'floor' => 1,
                'capacity' => 120,
                'type' => 0,
                'display' => 'projector',
                'has_pc' => true,
                'notes' => 'قاعة دراسية واسعة مجهزة لطلبة الدفعة 44'
            ],
            [
                'building_id' => $bldRazi->building_id,
                'name' => 'قاعة الرازي 2 (المستوى الثاني)',
                'floor' => 2,
                'capacity' => 120,
                'type' => 0,
                'display' => 'screen',
                'has_pc' => true,
                'notes' => 'قاعة محاضرات نظرية للمستوى الثاني'
            ],
            [
                'building_id' => $bldRazi->building_id,
                'name' => 'معمل التشريح والأجنة المركزي (Anatomy Lab)',
                'floor' => 0,
                'capacity' => 60,
                'type' => 1,
                'display' => 'projector',
                'has_pc' => true,
                'notes' => 'معمل تشريح مجهز بطاولات فورمالين ونماذج بلاستيكية تشريحية ثلاثية الأبعاد'
            ],
            [
                'building_id' => $bldRazi->building_id,
                'name' => 'معمل علم الأمراض والأنسجة (Pathology & Histology Lab)',
                'floor' => 2,
                'capacity' => 60,
                'type' => 1,
                'display' => 'screen',
                'has_pc' => true,
                'notes' => 'معمل مجهز بمجاهر إلكترونية متصلة بشبكة عرض مركزية'
            ],
            [
                'building_id' => $bldSina->building_id,
                'name' => 'مدرج ابن سينا التعليمي',
                'floor' => 1,
                'capacity' => 200,
                'type' => 0,
                'display' => 'projector',
                'has_pc' => true,
                'notes' => 'مدرج ابن سينا للمحاضرات التخصصية المشتركة'
            ],
            [
                'building_id' => $bldSina->building_id,
                'name' => 'مركز المهارات السريرية والمحاكاة (Clinical Skills & Simulation)',
                'floor' => 2,
                'capacity' => 50,
                'type' => 1,
                'display' => 'screen',
                'has_pc' => true,
                'notes' => 'مركز محاكاة سريرية لتدريب الطلاب على الإنعاش القلبي والفحص السريري'
            ],
            [
                'building_id' => $bldSina->building_id,
                'name' => 'قاعة التعلم بحل المشكلات (PBL Seminar Room 101)',
                'floor' => 2,
                'capacity' => 40,
                'type' => 0,
                'display' => 'smart_board',
                'has_pc' => true,
                'notes' => 'قاعة مخصصة لنظام الحلقات الدراسية وحل الحالات الطبية التفاعلية'
            ],
        ];

        $classroomMap = [];
        foreach ($classroomsData as $c) {
            $createdRoom = Classroom::updateOrCreate(
                ['college_id' => $medCollege->college_id, 'classroom_name' => $c['name']],
                [
                    'building_id' => $c['building_id'],
                    'floor' => $c['floor'],
                    'capacity' => $c['capacity'],
                    'classroom_type' => $c['type'],
                    'display_type' => $c['display'],
                    'has_computer' => $c['has_pc'],
                    'notes' => $c['notes'],
                    'latitude' => 15.369445,
                    'longitude' => 44.191007,
                    'allowed_distance' => 50.00,
                ]
            );
            $classroomMap[] = $createdRoom;
        }

        // 6. Departments in Medical College
        $deptMed = Department::updateOrCreate(
            ['college_id' => $medCollege->college_id, 'department_code' => 'MED-SURG'],
            ['department_name' => 'قسم الطب البشري والجراحة العامة']
        );

        $deptPharm = Department::updateOrCreate(
            ['college_id' => $medCollege->college_id, 'department_code' => 'CLIN-PHARM'],
            ['department_name' => 'قسم الصيدلة السريرية']
        );

        $deptLab = Department::updateOrCreate(
            ['college_id' => $medCollege->college_id, 'department_code' => 'MED-LAB'],
            ['department_name' => 'قسم المختبرات الطبية والعلوم الحيوية']
        );

        // 7. Academic Programs
        $progMbbs = Program::updateOrCreate(
            ['department_id' => $deptMed->department_id, 'program_name' => 'بكالوريوس الطب والجراحة العامة (MBBS)'],
            [
                'academic_system' => 'semester',
                'block_based' => true,
                'total_hours' => 245,
                'is_active' => true,
            ]
        );

        $progPharmD = Program::updateOrCreate(
            ['department_id' => $deptPharm->department_id, 'program_name' => 'دكتور صيدلة سريرية (PharmD)'],
            [
                'academic_system' => 'semester',
                'block_based' => false,
                'total_hours' => 195,
                'is_active' => true,
            ]
        );

        $progLab = Program::updateOrCreate(
            ['department_id' => $deptLab->department_id, 'program_name' => 'بكالوريوس المختبرات الطبية التشخيصية'],
            [
                'academic_system' => 'semester',
                'block_based' => false,
                'total_hours' => 140,
                'is_active' => true,
            ]
        );

        // 8. Levels & Semesters for MBBS
        $lvl1 = Level::updateOrCreate(
            ['program_id' => $progMbbs->program_id, 'level_number' => 1],
            ['level_name' => 'المستوى الأول (السنة الأولى - علوم طبية أساسية)']
        );

        $lvl2 = Level::updateOrCreate(
            ['program_id' => $progMbbs->program_id, 'level_number' => 2],
            ['level_name' => 'المستوى الثاني (السنة الثانية - الأجهزة العضوية)']
        );

        $lvl3 = Level::updateOrCreate(
            ['program_id' => $progMbbs->program_id, 'level_number' => 3],
            ['level_name' => 'المستوى الثالث (السنة الثالثة - مقدمة العلوم السريرية)']
        );

        $sem1_1 = Semester::updateOrCreate(
            ['level_id' => $lvl1->level_id, 'term_number' => 1],
            ['semester_name' => 'الفصل الدراسي الأول', 'academic_year' => '2025-2026']
        );

        $sem1_2 = Semester::updateOrCreate(
            ['level_id' => $lvl1->level_id, 'term_number' => 2],
            ['semester_name' => 'الفصل الدراسي الثاني', 'academic_year' => '2025-2026']
        );

        $sem2_1 = Semester::updateOrCreate(
            ['level_id' => $lvl2->level_id, 'term_number' => 1],
            ['semester_name' => 'الفصل الدراسي الأول', 'academic_year' => '2025-2026']
        );

        $sem2_2 = Semester::updateOrCreate(
            ['level_id' => $lvl2->level_id, 'term_number' => 2],
            ['semester_name' => 'الفصل الدراسي الثاني', 'academic_year' => '2025-2026']
        );

        // 9. Medical Blocks (البلوكات الطبية للنظام التكاملي)
        $blkFoundation = Block::updateOrCreate(
            ['program_id' => $progMbbs->program_id, 'block_number' => 1],
            [
                'level_id' => $lvl1->level_id,
                'block_name' => 'بلوك أساسيات العلوم الطبية والخلية (Foundation Block)',
                'weeks' => 8,
                'weight' => 6.00,
                'credit_hours' => 6,
                'type' => 'compulsory',
                'description' => 'يشمل بيولوجيا الخلية، مبادئ التشريح والأنسجة، الكيمياء الحيوية، والمفاهيم الطبية الأولية'
            ]
        );

        $blkMsk = Block::updateOrCreate(
            ['program_id' => $progMbbs->program_id, 'block_number' => 2],
            [
                'level_id' => $lvl1->level_id,
                'block_name' => 'بلوك الجهاز العضلي الهيكلي والجلد (MSK & Integumentary)',
                'weeks' => 8,
                'weight' => 6.00,
                'credit_hours' => 6,
                'type' => 'compulsory',
                'description' => 'تشريح العظام والمفاصل والعضلات ووظائفها والأمراض المرتبطة بها'
            ]
        );

        $blkCvs = Block::updateOrCreate(
            ['program_id' => $progMbbs->program_id, 'block_number' => 3],
            [
                'level_id' => $lvl2->level_id,
                'block_name' => 'بلوك الجهاز الدوري والقلب والأوعية (Cardiovascular Block)',
                'weeks' => 7,
                'weight' => 5.00,
                'credit_hours' => 5,
                'type' => 'compulsory',
                'description' => 'فيزيولوجيا وتشريح القلب، الدورة الدموية، الصدمة، وأمراض الشرايين والأوردة'
            ]
        );

        $blkResp = Block::updateOrCreate(
            ['program_id' => $progMbbs->program_id, 'block_number' => 4],
            [
                'level_id' => $lvl2->level_id,
                'block_name' => 'بلوك الجهاز التنفسي والغازات (Respiratory System Block)',
                'weeks' => 6,
                'weight' => 4.00,
                'credit_hours' => 4,
                'type' => 'compulsory',
                'description' => 'تشريح الرئتين، ميكانيكا التنفس، أمراض الجهاز التنفسي والأدوية الموسعة للقصبات'
            ]
        );

        $blkGit = Block::updateOrCreate(
            ['program_id' => $progMbbs->program_id, 'block_number' => 5],
            [
                'level_id' => $lvl2->level_id,
                'block_name' => 'بلوك الجهاز الهضمي والتغذية (GIT & Metabolism)',
                'weeks' => 7,
                'weight' => 5.00,
                'credit_hours' => 5,
                'type' => 'compulsory',
                'description' => 'هضم وامتصاص الغذاء، الكبد والقنوات الصفراوية، التمثيل الغذائي والأمراض الهضمية'
            ]
        );

        // 10. Realistic Courses
        $coursesData = [
            [
                'code' => 'ANAT101',
                'name' => 'تشريح الأنسجة البشرية والأجنة (Human Anatomy & Embryology)',
                'dept_id' => $deptMed->department_id,
                'prog_id' => $progMbbs->program_id,
                'lvl_id' => $lvl1->level_id,
                'sem_id' => $sem1_1->semester_id,
                'blk_id' => $blkFoundation->id,
                'hours' => 3,
                'weight' => 3.00,
                'category' => 'متطلب تخصص إجباري',
                'lang' => 'الإنجليزية',
            ],
            [
                'code' => 'PHYS101',
                'name' => 'علم وظائف الأعضاء الطبية (Medical Physiology I)',
                'dept_id' => $deptMed->department_id,
                'prog_id' => $progMbbs->program_id,
                'lvl_id' => $lvl1->level_id,
                'sem_id' => $sem1_1->semester_id,
                'blk_id' => $blkFoundation->id,
                'hours' => 3,
                'weight' => 3.00,
                'category' => 'متطلب تخصص إجباري',
                'lang' => 'الإنجليزية',
            ],
            [
                'code' => 'BIOC101',
                'name' => 'الكيمياء الحيوية السريرية (Medical Biochemistry)',
                'dept_id' => $deptMed->department_id,
                'prog_id' => $progMbbs->program_id,
                'lvl_id' => $lvl1->level_id,
                'sem_id' => $sem1_1->semester_id,
                'blk_id' => $blkFoundation->id,
                'hours' => 3,
                'weight' => 3.00,
                'category' => 'متطلب تخصص إجباري',
                'lang' => 'الإنجليزية',
            ],
            [
                'code' => 'HIST102',
                'name' => 'علم الأنسجة والجلد (Histology of Skin & MSK)',
                'dept_id' => $deptMed->department_id,
                'prog_id' => $progMbbs->program_id,
                'lvl_id' => $lvl1->level_id,
                'sem_id' => $sem1_2->semester_id,
                'blk_id' => $blkMsk->id,
                'hours' => 3,
                'weight' => 3.00,
                'category' => 'متطلب تخصص إجباري',
                'lang' => 'الإنجليزية',
            ],
            [
                'code' => 'ANAT102',
                'name' => 'تشريح الجهاز العضلي الهيكلي (Musculoskeletal Anatomy)',
                'dept_id' => $deptMed->department_id,
                'prog_id' => $progMbbs->program_id,
                'lvl_id' => $lvl1->level_id,
                'sem_id' => $sem1_2->semester_id,
                'blk_id' => $blkMsk->id,
                'hours' => 3,
                'weight' => 3.00,
                'category' => 'متطلب تخصص إجباري',
                'lang' => 'الإنجليزية',
            ],
            [
                'code' => 'PATH201',
                'name' => 'علم الأمراض العام والباثولوجيا (General Pathology)',
                'dept_id' => $deptMed->department_id,
                'prog_id' => $progMbbs->program_id,
                'lvl_id' => $lvl2->level_id,
                'sem_id' => $sem2_1->semester_id,
                'blk_id' => $blkCvs->id,
                'hours' => 4,
                'weight' => 4.00,
                'category' => 'متطلب تخصص إجباري',
                'lang' => 'الإنجليزية',
            ],
            [
                'code' => 'PHAR201',
                'name' => 'علم الأدوية والعقاقير السريري (Clinical Pharmacology)',
                'dept_id' => $deptMed->department_id,
                'prog_id' => $progMbbs->program_id,
                'lvl_id' => $lvl2->level_id,
                'sem_id' => $sem2_1->semester_id,
                'blk_id' => $blkCvs->id,
                'hours' => 4,
                'weight' => 4.00,
                'category' => 'متطلب تخصص إجباري',
                'lang' => 'الإنجليزية',
            ],
            [
                'code' => 'CVS202',
                'name' => 'طب وأمراض القلب والأوعية (Cardiovascular Medicine)',
                'dept_id' => $deptMed->department_id,
                'prog_id' => $progMbbs->program_id,
                'lvl_id' => $lvl2->level_id,
                'sem_id' => $sem2_1->semester_id,
                'blk_id' => $blkCvs->id,
                'hours' => 4,
                'weight' => 4.00,
                'category' => 'متطلب تخصص إجباري',
                'lang' => 'الإنجليزية',
            ],
            [
                'code' => 'RESP203',
                'name' => 'طب وأمراض الجهاز التنفسي (Pulmonology & Respiration)',
                'dept_id' => $deptMed->department_id,
                'prog_id' => $progMbbs->program_id,
                'lvl_id' => $lvl2->level_id,
                'sem_id' => $sem2_2->semester_id,
                'blk_id' => $blkResp->id,
                'hours' => 4,
                'weight' => 4.00,
                'category' => 'متطلب تخصص إجباري',
                'lang' => 'الإنجليزية',
            ],
            [
                'code' => 'ETHC101',
                'name' => 'أخلاقيات ممارسة مهنة الطب والاتصال السريري (Medical Ethics & Doctor-Patient Skills)',
                'dept_id' => $deptMed->department_id,
                'prog_id' => $progMbbs->program_id,
                'lvl_id' => $lvl1->level_id,
                'sem_id' => $sem1_1->semester_id,
                'blk_id' => $blkFoundation->id,
                'hours' => 2,
                'weight' => 2.00,
                'category' => 'متطلب جامعة',
                'lang' => 'العربية',
            ],
        ];

        $courseMap = [];
        foreach ($coursesData as $c) {
            $createdCourse = Course::updateOrCreate(
                ['course_code' => $c['code']],
                [
                    'course_name' => $c['name'],
                    'college_id' => $medCollege->college_id,
                    'department_id' => $c['dept_id'],
                    'program_id' => $c['prog_id'],
                    'level_id' => $c['lvl_id'],
                    'semester_id' => $c['sem_id'],
                    'block_id' => $c['blk_id'],
                    'credit_hours' => $c['hours'],
                    'weight' => $c['weight'],
                    'category' => $c['category'],
                    'teaching_language' => $c['lang'],
                    'is_active' => true,
                    'is_approved' => true,
                    'approval_date' => now(),
                ]
            );
            $courseMap[$c['code']] = $createdCourse;
        }

        // 11. Administrative & Academic Leadership Accounts
        $adminStaff = [
            [
                'name' => 'أ.د. عبدالكريم أحمد الشيباني',
                'email' => 'dean.medicine@unihub.edu',
                'academic_number' => 'MED-DEAN-01',
                'phone' => '771234001',
                'gender' => 0,
                'type' => $deanType,
                'role' => 'dean',
            ],
            [
                'name' => 'أ.د. محمد منصور العريقي',
                'email' => 'head.surgery@unihub.edu',
                'academic_number' => 'MED-HEAD-01',
                'phone' => '771234002',
                'gender' => 0,
                'type' => $deptHeadType,
                'role' => 'dept_head',
            ],
            [
                'name' => 'د. نادية عبدالحميد الكبسي',
                'email' => 'academic.medicine@unihub.edu',
                'academic_number' => 'MED-ACAD-01',
                'phone' => '771234003',
                'gender' => 1,
                'type' => $acadType,
                'role' => 'academic_affairs',
            ],
            [
                'name' => 'أ. عادل مهدي القدسي',
                'email' => 'classrooms.medicine@unihub.edu',
                'academic_number' => 'MED-HALL-01',
                'phone' => '771234004',
                'gender' => 0,
                'type' => $roomMgrType,
                'role' => 'classroom_manager',
            ],
            [
                'name' => 'أ. فؤاد يحيى الحيمي',
                'email' => 'control.medicine@unihub.edu',
                'academic_number' => 'MED-CTRL-01',
                'phone' => '771234005',
                'gender' => 0,
                'type' => $ctrlType,
                'role' => 'control',
            ],
        ];

        foreach ($adminStaff as $staff) {
            if (!$staff['type']) continue;
            User::updateOrCreate(
                ['email' => $staff['email']],
                [
                    'full_name' => $staff['name'],
                    'academic_number' => $staff['academic_number'],
                    'phone' => $staff['phone'],
                    'password' => $passwordHash,
                    'gender' => $staff['gender'],
                    'college_id' => $medCollege->college_id,
                    'user_type_id' => $staff['type']->user_type_id ?? $staff['type']->id,
                ]
            );
        }

        // 12. Medical Lecturers & Professors
        $lecturersList = [
            [
                'name' => 'أ.د. عبدالكريم أحمد الشيباني',
                'email' => 'dean.medicine@unihub.edu',
                'academic_number' => 'MED-DEAN-01',
                'phone' => '771234001',
                'gender' => 0,
                'title' => 'PROF',
                'user_type_id' => $deanType ? ($deanType->user_type_id ?? $deanType->id) : 3,
            ],
            [
                'name' => 'أ.د. هدى صالح الصلوي',
                'email' => 'lec.salwi@unihub.edu',
                'academic_number' => 'LEC-MED-002',
                'phone' => '771110002',
                'gender' => 1,
                'title' => 'PROF',
            ],
            [
                'name' => 'د. يحيى عبدالله المتوكل',
                'email' => 'lec.mutawakkil@unihub.edu',
                'academic_number' => 'LEC-MED-003',
                'phone' => '771110003',
                'gender' => 0,
                'title' => 'ASSOC_PROF',
            ],
            [
                'name' => 'د. أحمد صالح الوادعي',
                'email' => 'lec.wadaei@unihub.edu',
                'academic_number' => 'LEC-MED-004',
                'phone' => '771110004',
                'gender' => 0,
                'title' => 'ASST_PROF',
            ],
            [
                'name' => 'د. سميرة لطف المتوكل',
                'email' => 'lec.samira@unihub.edu',
                'academic_number' => 'LEC-MED-005',
                'phone' => '771110005',
                'gender' => 1,
                'title' => 'ASST_PROF',
            ],
            [
                'name' => 'د. وسيم فيصل الصبري',
                'email' => 'lec.sabri@unihub.edu',
                'academic_number' => 'LEC-MED-006',
                'phone' => '771110006',
                'gender' => 0,
                'title' => 'ASST_PROF',
            ],
        ];

        $lecturerModelMap = [];
        foreach ($lecturersList as $item) {
            $u = User::updateOrCreate(
                ['email' => $item['email']],
                [
                    'full_name' => $item['name'],
                    'academic_number' => $item['academic_number'],
                    'phone' => $item['phone'],
                    'password' => $passwordHash,
                    'gender' => $item['gender'],
                    'college_id' => $medCollege->college_id,
                    'user_type_id' => $item['user_type_id'] ?? ($lecturerType ? ($lecturerType->user_type_id ?? $lecturerType->id) : 8),
                ]
            );

            $lecturer = Lecturer::updateOrCreate(
                ['user_id' => $u->user_id],
                [
                    'college_id' => $medCollege->college_id,
                    'department_id' => $deptMed->department_id,
                    'title_id' => $titleMap[$item['title']] ?? 1,
                    'hire_date' => '2019-09-01',
                    'status' => true,
                    'can_teach_externally' => true,
                ]
            );

            $lecturerModelMap[$item['email']] = $lecturer;
        }

        // 13. Student Groups
        $groupA_L1 = StudentGroup::updateOrCreate(
            ['college_id' => $medCollege->college_id, 'group_name' => 'دفعة الطب البشري 44 - المجموعة (A) بنين'],
            [
                'department_id' => $deptMed->department_id,
                'program_id' => $progMbbs->program_id,
                'level_id' => $lvl1->level_id,
                'semester_id' => $sem1_1->semester_id,
                'block_id' => $blkFoundation->id,
                'max_students' => 45,
            ]
        );

        $groupB_L1 = StudentGroup::updateOrCreate(
            ['college_id' => $medCollege->college_id, 'group_name' => 'دفعة الطب البشري 44 - المجموعة (B) بنات'],
            [
                'department_id' => $deptMed->department_id,
                'program_id' => $progMbbs->program_id,
                'level_id' => $lvl1->level_id,
                'semester_id' => $sem1_1->semester_id,
                'block_id' => $blkFoundation->id,
                'max_students' => 45,
            ]
        );

        $groupA_L2 = StudentGroup::updateOrCreate(
            ['college_id' => $medCollege->college_id, 'group_name' => 'دفعة الطب البشري 43 - المستوى الثاني (A)'],
            [
                'department_id' => $deptMed->department_id,
                'program_id' => $progMbbs->program_id,
                'level_id' => $lvl2->level_id,
                'semester_id' => $sem2_1->semester_id,
                'block_id' => $blkCvs->id,
                'max_students' => 50,
            ]
        );

        // 14. Seed 60+ Realistic Students & Assign Members
        $maleNames = [
            'أسامة خالد الحبيشي', 'مروان عبده المقطري', 'حسام علي الريمي', 'محمد نبيل الذماري',
            'إبراهيم مطهر الوزير', 'طارق صالح الجابري', 'يوسف حميد الأحمر', 'عمار سمير الأغبري',
            'أحمد وليد الشوافي', 'أيمن فهد العريقي', 'ماجد غسان المذحجي', 'صهيب ياسين الشرجبي',
            'فارس جمال القدسي', 'هيثم توفيق السلامي', 'عبدالرحمن قاسم الصايدي', 'بشير عادل الهتار',
            'زياد حمود المؤيد', 'سالم ناصر العولقي', 'وسام نبيل الصبري', 'حمزة سلطان الشجاع',
            'مختار فيصل العميسي', 'سليمان وضاح الأكوع', 'أنس كمال الغفاري', 'زكريا منصور الشرعبي',
            'عمر طاهر الضبيبي', 'يحيى عبدالكريم الكبسي', 'معاذ رشاد الشيباني', 'بلال هاشم الحوثي',
            'لؤي منير الأصبحي', 'سامي عارف الشامي', 'حميد عادل الجوفي', 'أصيل مروان النعماني'
        ];

        $femaleNames = [
            'ريم عادل الأصبحي', 'حنين محمد العريقي', 'فاطمة ناصر الشامي', 'سارة جمال الحكيمي',
            'آية فؤاد المتوكل', 'مريم طاهر الصبري', 'ندى خالد العنسي', 'هدى سمير المقطري',
            'رغد ياسين الذبحاني', 'ملاك عبدالسلام الحيمي', 'شيماء وليد الكبسي', 'زينب علي الشرفي',
            'روان إبراهيم الوزير', 'بشرى عبدالحكيم الحرازي', 'نور هاني العماد', 'سمية بدر الدين الأهدل',
            'داليا منصور الصنوي', 'رؤى عبدالجليل الصلوي', 'أروى كمال الدبعي', 'يسرى صادق الوصابي',
            'وجدان عبدالكريم الشيباني', 'خلود توفيق الأغبري', 'بيان جميل الجعدبي', 'مرام نبيل الحداء',
            'إيمان حميد القدسي', 'تسنيم سليم القاضي', 'سندس بشير الماوري', 'أسرار ماجد الزبيري'
        ];

        $studentNumber = 44101000;
        $studentTypeId = $studentType ? ($studentType->user_type_id ?? $studentType->id) : 1;

        // Populate Group A (Males - Level 1)
        $groupAStudentIds = [];
        foreach ($maleNames as $idx => $mName) {
            $studentNumber++;
            $email = "std.{$studentNumber}@unihub.edu";
            $u = User::updateOrCreate(
                ['email' => $email],
                [
                    'full_name' => $mName,
                    'academic_number' => (string)$studentNumber,
                    'phone' => '77' . rand(1000000, 9999999),
                    'password' => $passwordHash,
                    'gender' => 0,
                    'college_id' => $medCollege->college_id,
                    'user_type_id' => $studentTypeId,
                ]
            );

            $st = Student::updateOrCreate(
                ['user_id' => $u->user_id],
                [
                    'college_id' => $medCollege->college_id,
                    'department_id' => $deptMed->department_id,
                    'program_id' => $progMbbs->program_id,
                    'level_id' => $lvl1->level_id,
                    'semester_id' => $sem1_1->semester_id,
                    'block_id' => $blkFoundation->id,
                    'status' => true,
                ]
            );

            $groupAStudentIds[] = $st->student_id;
        }

        // Attach to Group A
        foreach ($groupAStudentIds as $sId) {
            StudentGroupMember::updateOrCreate(
                ['student_id' => $sId, 'group_id' => $groupA_L1->group_id]
            );
        }

        // Populate Group B (Females - Level 1)
        $groupBStudentIds = [];
        foreach ($femaleNames as $idx => $fName) {
            $studentNumber++;
            $email = "std.{$studentNumber}@unihub.edu";
            $u = User::updateOrCreate(
                ['email' => $email],
                [
                    'full_name' => $fName,
                    'academic_number' => (string)$studentNumber,
                    'phone' => '73' . rand(1000000, 9999999),
                    'password' => $passwordHash,
                    'gender' => 1,
                    'college_id' => $medCollege->college_id,
                    'user_type_id' => $studentTypeId,
                ]
            );

            $st = Student::updateOrCreate(
                ['user_id' => $u->user_id],
                [
                    'college_id' => $medCollege->college_id,
                    'department_id' => $deptMed->department_id,
                    'program_id' => $progMbbs->program_id,
                    'level_id' => $lvl1->level_id,
                    'semester_id' => $sem1_1->semester_id,
                    'block_id' => $blkFoundation->id,
                    'status' => true,
                ]
            );

            $groupBStudentIds[] = $st->student_id;
        }

        // Attach to Group B
        foreach ($groupBStudentIds as $sId) {
            StudentGroupMember::updateOrCreate(
                ['student_id' => $sId, 'group_id' => $groupB_L1->group_id]
            );
        }

        $totalSeededStudents = count($groupAStudentIds) + count($groupBStudentIds);
        $this->command?->info("✅ Seeded {$totalSeededStudents} students and assigned them to Groups A & B!");

        // 15. Conflict-Free Timetable Slots
        $days = Day::all()->keyBy('day_name');
        $sat = $days['السبت']->day_id ?? 1;
        $sun = $days['الأحد']->day_id ?? 2;
        $mon = $days['الإثنين']->day_id ?? 3;
        $tue = $days['الثلاثاء']->day_id ?? 4;
        $wed = $days['الأربعاء']->day_id ?? 5;

        $p1 = $periodMap[0]->period_id ?? 1;
        $p2 = $periodMap[1]->period_id ?? 2;
        $p3 = $periodMap[2]->period_id ?? 3;

        $roomZub = $classroomMap[0]->classroom_id; // مدرج الزبيري
        $roomR1 = $classroomMap[1]->classroom_id;  // قاعة الرازي 1
        $roomR2 = $classroomMap[2]->classroom_id;  // قاعة الرازي 2
        $roomAnat = $classroomMap[3]->classroom_id;// معمل التشريح
        $roomPath = $classroomMap[4]->classroom_id;// معمل الباثولوجيا
        $roomSina = $classroomMap[5]->classroom_id;// مدرج ابن سينا

        $lecs = [
            'shaibani'   => ($lecturerModelMap['dean.medicine@unihub.edu'] ?? $lecturerModelMap['lec.shaibani@unihub.edu'])->lecturer_id,
            'salwi'      => $lecturerModelMap['lec.salwi@unihub.edu']->lecturer_id,
            'mutawakkil' => $lecturerModelMap['lec.mutawakkil@unihub.edu']->lecturer_id,
            'wadaei'     => $lecturerModelMap['lec.wadaei@unihub.edu']->lecturer_id,
            'samira'     => $lecturerModelMap['lec.samira@unihub.edu']->lecturer_id,
            'sabri'      => $lecturerModelMap['lec.sabri@unihub.edu']->lecturer_id,
        ];

        $timetableEntries = [
            // السبت - Group A
            [
                'course' => $courseMap['ANAT101']->course_id,
                'lecturer' => $lecs['mutawakkil'],
                'group' => $groupA_L1->group_id,
                'room' => $roomZub,
                'day' => $sat,
                'period' => $p1,
                'level' => $lvl1->level_id,
                'sem' => $sem1_1->semester_id,
                'blk' => $blkFoundation->id,
                'type' => 0,
            ],
            [
                'course' => $courseMap['PHYS101']->course_id,
                'lecturer' => $lecs['salwi'],
                'group' => $groupA_L1->group_id,
                'room' => $roomZub,
                'day' => $sat,
                'period' => $p2,
                'level' => $lvl1->level_id,
                'sem' => $sem1_1->semester_id,
                'blk' => $blkFoundation->id,
                'type' => 0,
            ],
            [
                'course' => $courseMap['ANAT101']->course_id,
                'lecturer' => $lecs['mutawakkil'],
                'group' => $groupA_L1->group_id,
                'room' => $roomAnat,
                'day' => $sat,
                'period' => $p3,
                'level' => $lvl1->level_id,
                'sem' => $sem1_1->semester_id,
                'blk' => $blkFoundation->id,
                'type' => 1, // عملي
            ],

            // الأحد - Group A
            [
                'course' => $courseMap['BIOC101']->course_id,
                'lecturer' => $lecs['samira'],
                'group' => $groupA_L1->group_id,
                'room' => $roomR1,
                'day' => $sun,
                'period' => $p1,
                'level' => $lvl1->level_id,
                'sem' => $sem1_1->semester_id,
                'blk' => $blkFoundation->id,
                'type' => 0,
            ],
            [
                'course' => $courseMap['ETHC101']->course_id,
                'lecturer' => $lecs['shaibani'],
                'group' => $groupA_L1->group_id,
                'room' => $roomR1,
                'day' => $sun,
                'period' => $p2,
                'level' => $lvl1->level_id,
                'sem' => $sem1_1->semester_id,
                'blk' => $blkFoundation->id,
                'type' => 0,
            ],

            // الإثنين - Group A
            [
                'course' => $courseMap['PHYS101']->course_id,
                'lecturer' => $lecs['salwi'],
                'group' => $groupA_L1->group_id,
                'room' => $roomR1,
                'day' => $mon,
                'period' => $p1,
                'level' => $lvl1->level_id,
                'sem' => $sem1_1->semester_id,
                'blk' => $blkFoundation->id,
                'type' => 0,
            ],
            [
                'course' => $courseMap['BIOC101']->course_id,
                'lecturer' => $lecs['samira'],
                'group' => $groupA_L1->group_id,
                'room' => $roomR1,
                'day' => $mon,
                'period' => $p2,
                'level' => $lvl1->level_id,
                'sem' => $sem1_1->semester_id,
                'blk' => $blkFoundation->id,
                'type' => 1,
            ],

            // السبت - Group B
            [
                'course' => $courseMap['PHYS101']->course_id,
                'lecturer' => $lecs['salwi'],
                'group' => $groupB_L1->group_id,
                'room' => $roomR1,
                'day' => $sat,
                'period' => $p1,
                'level' => $lvl1->level_id,
                'sem' => $sem1_1->semester_id,
                'blk' => $blkFoundation->id,
                'type' => 0,
            ],
            [
                'course' => $courseMap['ANAT101']->course_id,
                'lecturer' => $lecs['mutawakkil'],
                'group' => $groupB_L1->group_id,
                'room' => $roomR1,
                'day' => $sat,
                'period' => $p2,
                'level' => $lvl1->level_id,
                'sem' => $sem1_1->semester_id,
                'blk' => $blkFoundation->id,
                'type' => 0,
            ],

            // الأحد - Group B
            [
                'course' => $courseMap['ETHC101']->course_id,
                'lecturer' => $lecs['shaibani'],
                'group' => $groupB_L1->group_id,
                'room' => $roomSina,
                'day' => $sun,
                'period' => $p1,
                'level' => $lvl1->level_id,
                'sem' => $sem1_1->semester_id,
                'blk' => $blkFoundation->id,
                'type' => 0,
            ],
            [
                'course' => $courseMap['BIOC101']->course_id,
                'lecturer' => $lecs['samira'],
                'group' => $groupB_L1->group_id,
                'room' => $roomSina,
                'day' => $sun,
                'period' => $p2,
                'level' => $lvl1->level_id,
                'sem' => $sem1_1->semester_id,
                'blk' => $blkFoundation->id,
                'type' => 0,
            ],

            // المستوى الثاني - Group A (CVS & Pathology)
            [
                'course' => $courseMap['PATH201']->course_id,
                'lecturer' => $lecs['wadaei'],
                'group' => $groupA_L2->group_id,
                'room' => $roomR2,
                'day' => $tue,
                'period' => $p1,
                'level' => $lvl2->level_id,
                'sem' => $sem2_1->semester_id,
                'blk' => $blkCvs->id,
                'type' => 0,
            ],
            [
                'course' => $courseMap['PHAR201']->course_id,
                'lecturer' => $lecs['sabri'],
                'group' => $groupA_L2->group_id,
                'room' => $roomR2,
                'day' => $tue,
                'period' => $p2,
                'level' => $lvl2->level_id,
                'sem' => $sem2_1->semester_id,
                'blk' => $blkCvs->id,
                'type' => 0,
            ],
            [
                'course' => $courseMap['CVS202']->course_id,
                'lecturer' => $lecs['shaibani'],
                'group' => $groupA_L2->group_id,
                'room' => $roomSina,
                'day' => $wed,
                'period' => $p1,
                'level' => $lvl2->level_id,
                'sem' => $sem2_1->semester_id,
                'blk' => $blkCvs->id,
                'type' => 0,
            ],
            [
                'course' => $courseMap['PATH201']->course_id,
                'lecturer' => $lecs['wadaei'],
                'group' => $groupA_L2->group_id,
                'room' => $roomPath,
                'day' => $wed,
                'period' => $p2,
                'level' => $lvl2->level_id,
                'sem' => $sem2_1->semester_id,
                'blk' => $blkCvs->id,
                'type' => 1,
            ],
        ];

        $createdTimetables = [];
        foreach ($timetableEntries as $entry) {
            $tt = Timetable::updateOrCreate(
                [
                    'course_id' => $entry['course'],
                    'group_id' => $entry['group'],
                    'day_id' => $entry['day'],
                    'period_id' => $entry['period'],
                ],
                [
                    'lecturer_id' => $entry['lecturer'],
                    'classroom_id' => $entry['room'],
                    'level_id' => $entry['level'],
                    'semester_id' => $entry['sem'],
                    'block_id' => $entry['blk'],
                    'college_id' => $medCollege->college_id,
                    'department_id' => $deptMed->department_id,
                    'program_id' => $progMbbs->program_id,
                    'lecture_type' => $entry['type'],
                    'gender_type' => 0,
                    'lecture_hours' => 2.0,
                    'allowance_minutes' => 15,
                    'academic_year' => '2025-2026',
                    'start_date' => '2025-09-01',
                    'end_date' => '2026-06-30',
                    'status' => 1,
                ]
            );
            $createdTimetables[] = $tt;
        }

        $this->command?->info('✅ Seeded conflict-free weekly timetable slots for MBBS Level 1 & Level 2!');

        // 16. Generate Completed & Active Lecture Sessions
        $today = Carbon::today();
        foreach ($createdTimetables as $idx => $tt) {
            // A past completed session
            LectureSession::updateOrCreate(
                [
                    'timetable_id' => $tt->timetable_id,
                    'session_date' => $today->copy()->subDays(7)->format('Y-m-d'),
                ],
                [
                    'lecturer_id' => $tt->lecturer_id,
                    'actual_classroom_id' => $tt->classroom_id,
                    'start_time' => '08:00:00',
                    'end_time' => '10:00:00',
                    'actual_start_time' => $today->copy()->subDays(7)->setTime(8, 5),
                    'actual_end_time' => $today->copy()->subDays(7)->setTime(9, 55),
                    'session_code' => 'SESS' . (700000 + $tt->timetable_id * 10 + 1),
                    'status' => 2, // Completed
                    'is_makeup' => 0,
                ]
            );

            // An active session for today so lecturer dashboard displays live QR Code & Start Lecture button
            if ($idx < 3) {
                LectureSession::updateOrCreate(
                    [
                        'timetable_id' => $tt->timetable_id,
                        'session_date' => $today->format('Y-m-d'),
                    ],
                    [
                        'lecturer_id' => $tt->lecturer_id,
                        'actual_classroom_id' => $tt->classroom_id,
                        'start_time' => '08:00:00',
                        'end_time' => '10:00:00',
                        'actual_start_time' => $today->copy()->setTime(8, 0),
                        'session_code' => 'SESS' . (800000 + $tt->timetable_id * 10 + 2),
                        'status' => 1, // In Progress (Live!)
                        'is_makeup' => 0,
                    ]
                );
            }
        }

        $this->command?->info('✅ Seeded active and completed lecture sessions!');

        // 17. Seed Permissions for All Colleges
        $this->call(PermissionsSeeder::class);

        // 18. Ensure Global Admin has Full Access
        $adminUser = User::where('email', 'Admin@unitshub.com')->first();
        if ($adminUser) {
            $allPermIds = DB::table('permissions')->pluck('permission_id');
            $now = now();
            foreach ([$medCollege->college_id, $dentCollege->college_id, $csCollege->college_id] as $cId) {
                $rows = $allPermIds->map(fn($pId) => [
                    'user_type_id' => $adminUser->user_type_id,
                    'permission_id' => $pId,
                    'college_id' => $cId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                DB::table('user_type_permissions')->upsert(
                    $rows,
                    ['user_type_id', 'permission_id', 'college_id'],
                    ['updated_at']
                );
            }
            $this->command?->info('✅ Admin user granted complete permissions across all colleges!');
        }

        $this->command?->info('🎉 Comprehensive Demo Seeding Finished Successfully!');
    }
}
