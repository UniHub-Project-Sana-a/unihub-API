<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\V1\TimetableController;
use App\Models\Department;
use App\Models\Program;
use Tests\TestCase;

class TimetableProgramPathTest extends TestCase
{
    public function test_program_path_rules_cover_all_four_variants(): void
    {
        $controller = new TimetableController();

        $semesterStandard = new Program(['academic_system' => 'semester', 'block_based' => false]);
        $semesterBlock = new Program(['academic_system' => 'semester', 'block_based' => true]);
        $creditStandard = new Program(['academic_system' => 'credit', 'block_based' => false]);
        $creditBlock = new Program(['academic_system' => 'credit', 'block_based' => true]);

        $ref = new \ReflectionMethod($controller, 'resolveProgramPathRules');
        $ref->setAccessible(true);

        $semesterStandardRules = $ref->invoke($controller, $semesterStandard);
        $semesterBlockRules = $ref->invoke($controller, $semesterBlock);
        $creditStandardRules = $ref->invoke($controller, $creditStandard);
        $creditBlockRules = $ref->invoke($controller, $creditBlock);

        $this->assertSame(['required', 'integer', 'exists:levels,level_id'], $semesterStandardRules['level_id']);
        $this->assertSame(['required', 'integer', 'exists:semesters,semester_id'], $semesterStandardRules['semester_id']);
        $this->assertSame(['nullable', 'integer', 'exists:blocks,id'], $semesterStandardRules['block_id']);

        $this->assertSame(['required', 'integer', 'exists:levels,level_id'], $semesterBlockRules['level_id']);
        $this->assertSame(['nullable', 'integer', 'exists:semesters,semester_id'], $semesterBlockRules['semester_id']);
        $this->assertSame(['required', 'integer', 'exists:blocks,id'], $semesterBlockRules['block_id']);

        $this->assertSame(['nullable', 'integer', 'exists:levels,level_id'], $creditStandardRules['level_id']);
        $this->assertSame(['nullable', 'integer', 'exists:semesters,semester_id'], $creditStandardRules['semester_id']);
        $this->assertSame(['nullable', 'integer', 'exists:blocks,id'], $creditStandardRules['block_id']);
        $this->assertSame(['required', 'integer', 'exists:programs,program_id'], $creditStandardRules['program_id']);

        $this->assertSame(['nullable', 'integer', 'exists:levels,level_id'], $creditBlockRules['level_id']);
        $this->assertSame(['nullable', 'integer', 'exists:semesters,semester_id'], $creditBlockRules['semester_id']);
        $this->assertSame(['required', 'integer', 'exists:blocks,id'], $creditBlockRules['block_id']);
    }
}
