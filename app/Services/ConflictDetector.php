<?php

namespace App\Services;

use App\Models\TimetableEntry;

class ConflictDetector
{
    public function findConflicts(int $scheduleId, array $payload): array
    {
        $dayId    = (int)$payload['day_id'];
        $periodId = (int)$payload['period_id'];

        $base = TimetableEntry::query()
            ->where('schedule_id', $scheduleId)
            ->where('day_id', $dayId)
            ->where('period_id', $periodId);

        $conflicts = [];

        if ((clone $base)->where('classroom_id', $payload['classroom_id'])->exists()) {
            $conflicts[] = ['type' => 'room', 'classroom_id' => (int)$payload['classroom_id'], 'day_id' => $dayId, 'period_id' => $periodId];
        }
        if ((clone $base)->where('lecturer_id', $payload['lecturer_id'])->exists()) {
            $conflicts[] = ['type' => 'lecturer', 'lecturer_id' => (int)$payload['lecturer_id'], 'day_id' => $dayId, 'period_id' => $periodId];
        }
        if ((clone $base)->where('group_id', $payload['group_id'])->exists()) {
            $conflicts[] = ['type' => 'group', 'group_id' => (int)$payload['group_id'], 'day_id' => $dayId, 'period_id' => $periodId];
        }

        return $conflicts;
    }
}