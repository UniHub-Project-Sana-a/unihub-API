<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. إعادة ملء البيانات الأساسية
        $this->call([
            PermissionsSeeder::class,
            UserTypesSeeder::class,
            SettingsSeeder::class,
            DaysSeeder::class,
        ]);

        // 2. إنشاء عميل Passport
        Artisan::call('passport:client', [
            '--personal' => true,
            '--name' => 'UniHub API Personal Access Client'
        ]);

        // 3. جلب نوع المستخدم أو إنشائه إن لم يكن موجوداً
        $adminType = UserType::firstOrCreate(
            ['user_type_code' => 'admin'],
            ['user_type_name' => 'Admin']
        );

        // 4. إنشاء المستخدم المشرف العام بشكل مضمون
        User::updateOrCreate(
            ['email' => 'ala.hussein002@gmail.com'],
            [
                'academic_number' => 'ADM0001',
                'full_name'       => 'Alaa Hussein',
                'phone'           => '734637112',
                'password'        => Hash::make('Admin@12345'),
                'gender'          => 0,
                'user_type_id'    => $adminType->user_type_id ?? $adminType->id,
                'college_id'      => null,
            ]
        );

        // 5. إنشاء الكلية الأولى وربط كل الصلاحيات
        $this->call(InitialCollegeSeeder::class);
    }
}