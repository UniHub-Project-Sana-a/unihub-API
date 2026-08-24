<?php

namespace Database\Seeders;

use App\Models\College;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InitialCollegeSeeder extends Seeder
{
    public function run(): void
    {
        $college = College::updateOrCreate(
            ['college_code' => 'fcit'],
            ['college_name' => 'كلية الحاسوب']
        );

        $user = User::find(1);
        if (!$user) {
            $user = User::where('academic_number', 'ADM0001')->first();
        }

        if (!$user) {
            $this->command->warn('لم يتم العثور على المستخدم 1 لإسناد الصلاحيات.');
            return;
        }

        $user->update(['college_id' => $college->college_id]);

        $now = now();
        $rows = Permission::query()->get(['permission_id'])->map(
            fn (Permission $permission) => [
                'user_type_id' => $user->user_type_id,
                'college_id' => $college->college_id,
                'permission_id' => $permission->permission_id,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        )->all();

        if ($rows) {
            DB::table('user_type_permissions')->upsert(
                $rows,
                ['user_type_id', 'permission_id', 'college_id'],
                ['updated_at']
            );
        }

        $this->command->info('تم إنشاء كلية الحاسوب وإسناد جميع الصلاحيات للمستخدم 1.');
    }
}