<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\V1\StudentGroupsController;
use Tests\TestCase;

class StudentGroupTransferRulesTest extends TestCase
{
    public function test_same_program_transfer_is_allowed_and_cross_program_transfer_is_rejected(): void
    {
        $controller = new StudentGroupsController();
        $method = new \ReflectionMethod($controller, 'validateSameProgramTransfer');
        $method->setAccessible(true);

        $source = [
            'college_id' => 1,
            'department_id' => 2,
            'program_id' => 10,
            'level_id' => 1,
            'semester_id' => 2,
            'block_id' => null,
        ];

        $sameProgramTarget = [
            'college_id' => 1,
            'department_id' => 2,
            'program_id' => 10,
            'level_id' => 2,
            'semester_id' => 3,
            'block_id' => null,
        ];

        $differentProgramTarget = [
            'college_id' => 1,
            'department_id' => 2,
            'program_id' => 11,
            'level_id' => 2,
            'semester_id' => 3,
            'block_id' => null,
        ];

        $this->assertTrue($method->invoke($controller, $source, $sameProgramTarget));
        $this->assertFalse($method->invoke($controller, $source, $differentProgramTarget));
    }
}
