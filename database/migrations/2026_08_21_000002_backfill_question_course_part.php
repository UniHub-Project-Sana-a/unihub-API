<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            UPDATE topic_questions q
            INNER JOIN course_topics t ON t.topic_id = q.topic_id
            SET q.course_id = t.course_id,
                q.part = t.part
            WHERE q.topic_id IS NOT NULL
              AND (q.course_id IS NULL OR q.part IS NULL)
        SQL);
    }

    public function down(): void
    {
        DB::statement(<<<'SQL'
            UPDATE topic_questions
            SET course_id = NULL,
                part = NULL
            WHERE topic_id IS NOT NULL
        SQL);
    }
};
