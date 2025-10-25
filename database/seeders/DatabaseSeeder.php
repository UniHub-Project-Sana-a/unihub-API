<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        // 1. حذف البيانات (طريقة بديلة)
        DB::table('users')->truncate();
        DB::table('user_types')->truncate();
        DB::table('permissions')->truncate();
        DB::table('settings')->truncate();
        // ... أضف أسماء بقية الجداول هنا
        
        Schema::enableForeignKeyConstraints();

        // 2. إعادة ملء البيانات الأساسية
        $this->call([
            PermissionsSeeder::class,
            UserTypesSeeder::class,
            SettingsSeeder::class,
        ]);

        // 3. إنشاء المستخدم المشرف العام
        $adminType = UserType::where('user_type_code', 'admin')->first();
        if ($adminType) {
            User::create([
                'full_name' => 'علاء حسين سعيد',
                'email' => 'ala.hussein002@gmail.com',
                'phone' => '737131058',
                'password' => Hash::make('Admin@12345'),
                'academic_number' => 'ADM0001',
                'gender' => 0,
                'user_type_id' => $adminType->user_type_id,
            ]);
        }
    }
}