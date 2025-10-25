<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // General
            ['permission_key' => 'dashboard.view', 'permission_name' => 'عرض لوحة التحكم', 'description' => ''],

            // Users
            ['permission_key' => 'users.view', 'permission_name' => 'عرض المستخدمين', 'description' => ''],
            ['permission_key' => 'users.create', 'permission_name' => 'إنشاء مستخدم', 'description' => ''],
            ['permission_key' => 'users.update', 'permission_name' => 'تعديل مستخدم', 'description' => ''],
            ['permission_key' => 'users.delete', 'permission_name' => 'حذف مستخدم', 'description' => ''],

            // Roles (User Types) & Permissions
            ['permission_key' => 'roles.view', 'permission_name' => 'عرض الأدوار', 'description' => ''],
            ['permission_key' => 'roles.create', 'permission_name' => 'إنشاء دور', 'description' => ''],
            ['permission_key' => 'roles.update', 'permission_name' => 'تعديل دور', 'description' => ''],
            ['permission_key' => 'roles.delete', 'permission_name' => 'حذف دور', 'description' => ''],
            ['permission_key' => 'roles.assign_permissions', 'permission_name' => 'تعيين صلاحيات للأدوار', 'description' => ''],

            // Academic Dictionaries
            ['permission_key' => 'colleges.manage', 'permission_name' => 'إدارة الكليات', 'description' => ''],
            ['permission_key' => 'departments.manage', 'permission_name' => 'إدارة الأقسام', 'description' => ''],
            ['permission_key' => 'programs.manage', 'permission_name' => 'إدارة البرامج', 'description' => ''],
            ['permission_key' => 'levels.manage', 'permission_name' => 'إدارة المستويات', 'description' => ''],
            ['permission_key' => 'semesters.manage', 'permission_name' => 'إدارة الفصول الدراسية', 'description' => ''],

            // Facilities & Periods
            ['permission_key' => 'buildings.manage', 'permission_name' => 'إدارة المباني', 'description' => ''],
            ['permission_key' => 'classrooms.manage', 'permission_name' => 'إدارة القاعات', 'description' => ''],
            ['permission_key' => 'periods.manage', 'permission_name' => 'إدارة الفترات', 'description' => ''],

            // Courses & Groups
            ['permission_key' => 'courses.manage', 'permission_name' => 'إدارة المقررات', 'description' => ''],
            ['permission_key' => 'groups.manage', 'permission_name' => 'إدارة المجموعات الطلابية', 'description' => ''],

            // Timetable & Sessions
            ['permission_key' => 'timetable.manage', 'permission_name' => 'إدارة الجداول الدراسية', 'description' => ''],
            ['permission_key' => 'timetable.view', 'permission_name' => 'عرض الجداول الدراسية', 'description' => ''],

            // Attendance & QR
            ['permission_key' => 'attendance.view', 'permission_name' => 'عرض سجلات الحضور', 'description' => ''],
            ['permission_key' => 'qr.manage', 'permission_name' => 'إدارة رموز QR', 'description' => ''],

            // Requests & Notifications
            ['permission_key' => 'excuses.review', 'permission_name' => 'مراجعة أعذار الطلاب', 'description' => ''],
            ['permission_key' => 'makeup_lectures.review', 'permission_name' => 'مراجعة طلبات المحاضرات التعويضية', 'description' => ''],
            ['permission_key' => 'notifications.send', 'permission_name' => 'إرسال إشعارات', 'description' => ''],

            // Admin & Settings
            ['permission_key' => 'settings.manage', 'permission_name' => 'إدارة إعدادات النظام', 'description' => ''],
            ['permission_key' => 'sessions.view', 'permission_name' => 'عرض الجلسات النشطة', 'description' => ''],
            ['permission_key' => 'audit_logs.view', 'permission_name' => 'عرض سجلات التدقيق', 'description' => ''],
        ];

        DB::table('permissions')->insert($permissions);
    }
}