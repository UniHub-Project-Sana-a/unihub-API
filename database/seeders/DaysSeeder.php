<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DaysSeeder extends Seeder
{
    public function run(): void
    {
        // أيام الأسبوع بالإنجليزية (موحدة لكل النظام)
        $days = [
            'السبت', 'الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة'
        ];

        foreach ($days as $name) {
            DB::table('days')->updateOrInsert(
                ['day_name' => $name],
                ['updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}