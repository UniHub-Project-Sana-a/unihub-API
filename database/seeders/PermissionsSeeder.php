<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // تنظيف الجدول بطريقة متوافقة مع جميع قواعد البيانات
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('permissions')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } elseif ($driver === 'pgsql') {
            // DB::statement('SET session_replication_role = replica;');
            // DB::table('permissions')->truncate();
            // DB::statement('SET session_replication_role = DEFAULT;');
            DB::table('permissions')->delete();
        } else {
            // SQLite or others
            DB::table('permissions')->delete();
        }

        $permissions = [
            // =======================================================
            // 1. إدارة الكليات (Colleges Management)
            // =======================================================
            ['key' => 'colleges.view',   'name' => 'عرض الكليات',       'desc' => 'عرض قائمة الكليات وتفاصيلها'],
            ['key' => 'colleges.create', 'name' => 'إضافة كلية',        'desc' => 'إنشاء كلية جديدة وإعداد بياناتها'],
            ['key' => 'colleges.update', 'name' => 'تعديل بيانات كلية', 'desc' => 'تعديل الاسم، الشعار، أو الكود الأكاديمي'],
            ['key' => 'colleges.delete', 'name' => 'حذف كلية',          'desc' => 'حذف الكلية من النظام'],

            // =======================================================
            // 2. إدارة المستخدمين (Users Management)
            // =======================================================
            ['key' => 'users.view',   'name' => 'عرض المستخدمين',       'desc' => 'عرض قائمة كافة المستخدمين في النظام'],
            ['key' => 'users.create', 'name' => 'إضافة مستخدم',         'desc' => 'إنشاء حساب مستخدم جديد'],
            ['key' => 'users.update', 'name' => 'تعديل بيانات مستخدم',  'desc' => 'تعديل البريد، الاسم، أو الهاتف'],
            ['key' => 'users.delete', 'name' => 'حذف مستخدم',           'desc' => 'حذف حساب مستخدم نهائياً أو إيقافه'],

            // =======================================================
            // 3. إدارة الأدوار والصلاحيات (Roles & Permissions)
            // =======================================================
            ['key' => 'roles.view',               'name' => 'عرض الأدوار',              'desc' => 'عرض قائمة أنواع المستخدمين (الأدوار)'],
            ['key' => 'roles.create',             'name' => 'إضافة دور جديد',           'desc' => 'إنشاء مسمى وظيفي جديد'],
            ['key' => 'roles.update',             'name' => 'تعديل دور',                'desc' => 'تعديل اسم أو كود الدور'],
            ['key' => 'roles.delete',             'name' => 'حذف دور',                  'desc' => 'حذف الدور من النظام'],
            ['key' => 'roles.assign_permissions', 'name' => 'توزيع الصلاحيات (المصفوفة)','desc' => 'التحكم في مصفوفة الصلاحيات'],
            ['key' => 'roles.assign_user',        'name' => 'تعيين دور لمستخدم',        'desc' => 'ربط الموظف بوظيفة محددة'],

            // =======================================================
            // 4. التحكم في الوصول والأمان (Access Control)
            // =======================================================
            ['key' => 'security.view_policy',    'name' => 'عرض سياسة الأمان',      'desc' => 'الاطلاع على إعدادات الأمان'],
            ['key' => 'security.manage_policy',  'name' => 'تعديل سياسة الأمان',    'desc' => 'تغيير إعدادات كلمات المرور والحظر'],
            ['key' => 'security.manage_sessions','name' => 'إدارة الجلسات النشطة',  'desc' => 'إنهاء جلسات المستخدمين'],
            ['key' => 'security.view_devices',   'name' => 'عرض أجهزة المستخدمين',  'desc' => 'مراقبة الأجهزة المتصلة'],
            ['key' => 'security.manage_ips',     'name' => 'قيود الشبكة (IP)',      'desc' => 'إدارة القائمة البيضاء والسوداء'],
            ['key' => 'logs.view',               'name' => 'عرض سجلات النظام',      'desc' => 'الاطلاع على الـ Audit Logs'],

            // =======================================================
            // 5. الخطة الدراسية (Study Plan)
            // =======================================================
            ['key' => 'study_plan.view',   'name' => 'عرض الخطة الدراسية',   'desc' => 'عرض الهيكل الأكاديمي والمقررات'],
            ['key' => 'study_plan.create', 'name' => 'إضافة في الخطة',       'desc' => 'إضافة أقسام، برامج، أو مواد'],
            ['key' => 'study_plan.update', 'name' => 'تعديل الخطة',          'desc' => 'تعديل بيانات البرامج والمقررات'],
            ['key' => 'study_plan.delete', 'name' => 'حذف من الخطة',         'desc' => 'حذف عناصر من الهيكل الأكاديمي'],

            // =======================================================
            // 6. القاعات والمباني (Locations)
            // =======================================================
            ['key' => 'locations.view',   'name' => 'عرض القاعات والمباني',  'desc' => 'عرض قائمة المباني والقاعات'],
            ['key' => 'locations.create', 'name' => 'إضافة قاعة/مبنى',       'desc' => 'إضافة مبنى جديد أو قاعة'],
            ['key' => 'locations.update', 'name' => 'تعديل قاعة/مبنى',       'desc' => 'تعديل بيانات المباني والقاعات'],
            ['key' => 'locations.delete', 'name' => 'حذف قاعة/مبنى',         'desc' => 'حذف المباني أو القاعات'],

            // =======================================================
            // 7. الرتب الأكاديمية (Academic Titles)
            // =======================================================
            ['key' => 'academic_titles.view',   'name' => 'عرض الرتب الأكاديمية', 'desc' => 'عرض قائمة الرتب العلمية'],
            ['key' => 'academic_titles.create', 'name' => 'إضافة رتبة أكاديمية',  'desc' => 'إضافة رتبة علمية جديدة'],
            ['key' => 'academic_titles.update', 'name' => 'تعديل رتبة أكاديمية',  'desc' => 'تعديل بيانات الرتبة'],
            ['key' => 'academic_titles.delete', 'name' => 'حذف رتبة أكاديمية',    'desc' => 'حذف رتبة علمية'],

            // =======================================================
            // 8. هيئة التدريس (Staff)
            // =======================================================
            ['key' => 'staff.view',   'name' => 'عرض هيئة التدريس',    'desc' => 'عرض قائمة المحاضرين'],
            ['key' => 'staff.create', 'name' => 'إضافة عضو هيئة تدريس', 'desc' => 'إضافة محاضر جديد'],
            ['key' => 'staff.update', 'name' => 'تعديل بيانات عضو',     'desc' => 'تعديل الملف الشخصي للمحاضر'],
            ['key' => 'staff.delete', 'name' => 'حذف عضو هيئة تدريس',   'desc' => 'حذف محاضر من النظام'],

            // =======================================================
            // 9. الجدول الدراسي (Timetable)
            // =======================================================
            ['key' => 'timetable.create_table',   'name' => 'إنشاء جدول دراسي',    'desc' => 'تهيئة جدول جديد للفصل'],
            ['key' => 'timetable.view_lectures',  'name' => 'عرض المحاضرات',       'desc' => 'عرض المحاضرات في الجدول'],
            ['key' => 'timetable.create_lecture', 'name' => 'إنشاء محاضرة',        'desc' => 'إضافة محاضرة للجدول'],
            ['key' => 'timetable.update_lecture', 'name' => 'تعديل محاضرة',        'desc' => 'تغيير وقت أو قاعة المحاضرة'],
            ['key' => 'timetable.delete_lecture', 'name' => 'حذف محاضرة',          'desc' => 'إزالة محاضرة من الجدول'],

            // =======================================================
            // 10. التسجيل والطلاب (Registration)
            // =======================================================
            ['key' => 'groups.view',     'name' => 'عرض المجموعات',     'desc' => 'عرض المجموعات الطلابية'],
            ['key' => 'groups.create',   'name' => 'إنشاء مجموعة',      'desc' => 'إنشاء مجموعة جديدة'],
            ['key' => 'groups.update',   'name' => 'تعديل مجموعة',      'desc' => 'تعديل اسم أو سعة المجموعة'],
            ['key' => 'groups.delete',   'name' => 'حذف مجموعة',        'desc' => 'حذف مجموعة طلابية'],
            ['key' => 'students.add',    'name' => 'إضافة طالب',        'desc' => 'تسجيل طالب في النظام'],
            ['key' => 'students.update', 'name' => 'تعديل بيانات طالب', 'desc' => 'تعديل معلومات الطالب'],
            ['key' => 'students.delete', 'name' => 'حذف طالب',          'desc' => 'حذف طالب من النظام'],
            ['key' => 'students.move',   'name' => 'نقل طالب بين المجموعات', 'desc' => 'نقل الطالب من مجموعة إلى أخرى'],

            // =======================================================
            // 11. الفترات الزمنية (Periods)
            // =======================================================
            ['key' => 'periods.view',   'name' => 'عرض الفترات',    'desc' => 'عرض الفترات الزمنية'],
            ['key' => 'periods.create', 'name' => 'إضافة فترة',     'desc' => 'إضافة فترة زمنية جديدة'],
            ['key' => 'periods.update', 'name' => 'تعديل فترة',     'desc' => 'تعديل بيانات فترة'],
            ['key' => 'periods.delete', 'name' => 'حذف فترة',       'desc' => 'حذف فترة زمنية'],

            // =======================================================
            // 12. طلبات التعويض (Requests)
            // =======================================================
            ['key' => 'requests.approve_makeup',   'name' => 'الموافقة على طلبات التعويض',  'desc' => 'قبول أو رفض طلبات المحاضرين'],
            ['key' => 'requests.rejected_makeup',  'name' => 'أعادة الطلبات المرفوضة',      'desc' => 'إعادة تقديم طلبات المحاضرين المرفوضة'],
            ['key' => 'requests.view_makeup',      'name' => 'عرض طلبات التعويض',           'desc' => 'عرض كافة طلبات المحاضرين للتعويض'],
            ['key' => 'requests.schedule_makeup',  'name' => 'جدولة المحاضرة التعويضية',    'desc' => 'تحديد الوقت والقاعة للطلب بعد الموافقة عليه'],

            // =======================================================
            // 13. التقارير (Reports)
            // =======================================================
            ['key' => 'reports.financial_manage',    'name' => 'إدارة التقارير المالية',   'desc' => 'عرض وإنشاء كشوف الاستحقاق'],
            ['key' => 'reports.lecturer_attendance', 'name' => 'تقرير حضور المحاضرين',     'desc' => 'عرض سجلات حضور الدكاترة'],
            ['key' => 'reports.student_attendance',  'name' => 'تقرير حضور الطلاب',        'desc' => 'عرض سجلات حضور وغياب الطلاب'],
            ['key' => 'reports.semester_results',    'name' => 'نتائج أعمال الفصل',        'desc' => 'عرض درجات ونتائج الفصل'],
            ['key' => 'reports.view_custom',         'name' => 'عرض التقارير المخصصة',     'desc' => 'إنشاء تقارير مخصصة حسب الحاجة'],

            // =======================================================
            // 14. لوحة التحكم (Dashboards)
            // =======================================================
            ['key' => 'dashboard.view_global',  'name' => 'عرض لوحة التحكم العامة',   'desc' => 'اللوحة الرئيسية للنظام (تشمل جميع الكليات)'],
            ['key' => 'dashboard.view_college', 'name' => 'عرض لوحة تحكم الكلية',     'desc' => 'اللوحة الخاصة بإحصائيات الكلية الواحدة'],
        ];

        $this->command->info('Start Seeding Permissions...');

        foreach ($permissions as $perm) {
            DB::table('permissions')->updateOrInsert(
                ['permission_key' => $perm['key']], // الشرط
                [
                    'permission_name' => $perm['name'],
                    'description'     => $perm['desc'],
                    'created_at'      => now(),
                    'updated_at'      => now()
                ]
            );
        }

        $this->command->info('✅ Permissions seeded successfully! Total: ' . count($permissions));

        // =======================================================
        // تعيين الصلاحيات الافتراضية للأدوار (Default Role Permissions)
        // =======================================================
        $allPermMap = DB::table('permissions')->pluck('permission_id', 'permission_key')->all();
        $userTypes = DB::table('user_types')->pluck('user_type_id', 'user_type_code')->all();

        $roleDefaults = [
            'dean' => [
                'colleges.view', 'colleges.update',
                'users.view', 'users.create', 'users.update', 'users.delete',
                'roles.view', 'roles.assign_permissions', 'roles.assign_user',
                'study_plan.view', 'study_plan.create', 'study_plan.update', 'study_plan.delete',
                'locations.view', 'locations.create', 'locations.update', 'locations.delete',
                'academic_titles.view', 'academic_titles.create', 'academic_titles.update', 'academic_titles.delete',
                'staff.view', 'staff.create', 'staff.update', 'staff.delete',
                'timetable.create_table', 'timetable.view_lectures', 'timetable.create_lecture', 'timetable.update_lecture', 'timetable.delete_lecture',
                'groups.view', 'groups.create', 'groups.update', 'groups.delete', 'students.add', 'students.update', 'students.delete', 'students.move',
                'periods.view', 'periods.create', 'periods.update', 'periods.delete',
                'requests.approve_makeup', 'requests.rejected_makeup', 'requests.view_makeup', 'requests.schedule_makeup',
                'reports.financial_manage', 'reports.lecturer_attendance', 'reports.student_attendance', 'reports.semester_results', 'reports.view_custom',
                'dashboard.view_college',
            ],
            'academic_affairs' => [
                'users.view', 'users.create', 'users.update',
                'roles.view', 'roles.assign_user',
                'staff.view', 'staff.create', 'staff.update',
                'study_plan.view', 'study_plan.create', 'study_plan.update',
                'timetable.create_table', 'timetable.view_lectures', 'timetable.create_lecture', 'timetable.update_lecture', 'timetable.delete_lecture',
                'groups.view', 'groups.create', 'groups.update', 'groups.delete', 'students.add', 'students.update', 'students.move',
                'periods.view', 'periods.create', 'periods.update',
                'locations.view',
                'academic_titles.view',
                'requests.view_makeup', 'requests.schedule_makeup',
                'reports.lecturer_attendance', 'reports.student_attendance', 'reports.semester_results',
                'dashboard.view_college',
            ],
            'dept_head' => [
                'users.view',
                'study_plan.view', 'study_plan.create', 'study_plan.update',
                'staff.view', 'staff.create', 'staff.update',
                'timetable.view_lectures', 'timetable.create_lecture', 'timetable.update_lecture',
                'groups.view', 'groups.create', 'groups.update', 'students.move',
                'locations.view',
                'requests.view_makeup', 'requests.approve_makeup',
                'reports.lecturer_attendance', 'reports.student_attendance',
                'dashboard.view_college',
            ],
            'classroom_manager' => [
                'locations.view', 'locations.create', 'locations.update', 'locations.delete',
                'periods.view', 'periods.create', 'periods.update', 'periods.delete',
                'timetable.view_lectures',
                'dashboard.view_college',
            ],
            'control' => [
                'students.add', 'students.update', 'students.move',
                'groups.view', 'groups.create', 'groups.update',
                'reports.semester_results', 'reports.student_attendance',
                'study_plan.view',
                'dashboard.view_college',
            ],
            'lecturer' => [
                'study_plan.view',
                'timetable.view_lectures',
                'requests.view_makeup',
                'reports.student_attendance',
            ],
        ];

        $now = now();
        $assignedCount = 0;
        $collegeIds = DB::table('colleges')->pluck('college_id')->all();

        foreach ($collegeIds as $collegeId) {
            foreach ($roleDefaults as $roleCode => $permKeys) {
                if (!isset($userTypes[$roleCode])) continue;
                $typeId = $userTypes[$roleCode];

                foreach ($permKeys as $key) {
                    if (!isset($allPermMap[$key])) continue;
                    $permId = $allPermMap[$key];

                    DB::table('user_type_permissions')->updateOrInsert(
                        [
                            'user_type_id'  => $typeId,
                            'permission_id' => $permId,
                            'college_id'    => $collegeId,
                        ],
                        [
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );
                    $assignedCount++;
                }
            }
        }

        $this->command->info("✅ Default role permissions assigned successfully! Total: {$assignedCount}");
    }
}