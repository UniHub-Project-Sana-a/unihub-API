<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DaysSeeder extends Seeder
{
    public function run(): void
    {
        $days = [
            'السبت', 'الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة'
        ];

        foreach ($days as $name) {
            DB::table('days')->updateOrInsert(
                ['day_name' => $name],
                ['updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}