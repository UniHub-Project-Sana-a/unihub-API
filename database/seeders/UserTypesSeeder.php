<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserTypesSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['user_type_name' => 'مشرف عام', 'user_type_code' => 'admin'],
            ['user_type_name' => 'عميد', 'user_type_code' => 'dean'],
            ['user_type_name' => 'رئيس قسم', 'user_type_code' => 'dept_head'],
            ['user_type_name' => 'شؤون أكاديمية', 'user_type_code' => 'academic_affairs'],
            ['user_type_name' => 'كنترول', 'user_type_code' => 'control'],
            ['user_type_name' => 'مدير قاعات', 'user_type_code' => 'classroom_manager'],
            ['user_type_name' => 'محاضر', 'user_type_code' => 'lecturer'], // استخدمنا lecturer بدلاً من lecter
            ['user_type_name' => 'طالب', 'user_type_code' => 'student'],
        ];

        DB::table('user_types')->insert($types);
    }
}