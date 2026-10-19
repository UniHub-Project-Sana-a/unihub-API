<?php

namespace App\Services\Integration;

use App\Models\IntegrationClient;
use App\Models\IntegrationEntityRef;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * محرّك التكامل العام (Generic Ingestion Engine).
 *
 * يعمل على الجداول الفعلية القائمة وفق تهيئة config/integration.php:
 *   - Upsert بالمفتاح الطبيعي (أحادي أو مركّب)، حل المفاتيح الأجنبية بالأكواد (foreign).
 *   - مراجع مركّبة (foreign_composite) ومراجع متداخلة (parents).
 *   - موارد مرتبطة بمستخدم (linked_user) مثل students/lecturers: تكتب في users + الجدول.
 *   - نجاح جزئي صف-بصف، استرجاع المحذوف ناعماً، تعطيل (عمود) أو حذف فعلي، وتسجيل الربط.
 */
class IntegrationSyncService
{
    public function resource(string $resource): ?array
    {
        return config("integration.resources.{$resource}");
    }

    public function resources(): array
    {
        return array_keys(config('integration.resources', []));
    }

    // =====================================================================
    // الإدخال/التحديث (Upsert)
    // =====================================================================

    public function upsert(string $resource, array $rows, ?IntegrationClient $client = null): array
    {
        $cfg = $this->resource($resource);

        $inserted = 0;
        $updated = 0;
        $skipped = [];

        foreach ($rows as $index => $row) {
            $label = "Row #{$index}";

            if (!is_array($row)) {
                $skipped[] = "{$label}: payload must be an object.";
                continue;
            }

            DB::beginTransaction();
            try {
                $status = isset($cfg['linked_user'])
                    ? $this->upsertLinkedRow($cfg, $resource, $row, $client)
                    : $this->upsertSingleRow($cfg, $resource, $row, $client);

                DB::commit();

                if ($status === 'inserted') {
                    $inserted++;
                } else {
                    $updated++;
                }
            } catch (\Throwable $e) {
                DB::rollBack();
                $skipped[] = "{$label}: " . $this->shortError($e);
            }
        }

        return [
            'inserted_records' => $inserted,
            'updated_records'  => $updated,
            'skipped_records'  => count($skipped),
            'skipped_reasons'  => $skipped,
        ];
    }

    /** مورد يجلس على جدول واحد. */
    private function upsertSingleRow(array $cfg, string $resource, array $row, ?IntegrationClient $client): string
    {
        $prepared = $this->prepare($cfg, $row, true);
        if ($prepared['error'] !== null) {
            throw new RuntimeException($prepared['error']);
        }

        $match = $this->buildMatch($cfg, $prepared['attrs']);
        if ($match === null) {
            throw new RuntimeException('missing match key.');
        }

        $table = $cfg['table'];
        $pk = $cfg['primary_key'];
        $isComposite = is_array($pk); // مفتاح مركّب (مثل pivot بلا id تلقائي)
        $existing = DB::table($table)->where($match)->first();

        $this->assertNoConflicts($cfg, $prepared['attrs'], (!$isComposite && $existing) ? (int) $existing->{$pk} : null);

        $values = $prepared['attrs'];
        if (!$isComposite) {
            unset($values[$pk]);
        }
        $values['updated_at'] = now();

        if ($existing) {
            if ($this->hasSoftDeletes($table)) {
                $values['deleted_at'] = null;
            }
            if ($isComposite) {
                DB::table($table)->where($match)->update($values);
                $internalId = 0; // لا يوجد id فردي — المطابقة بالمفتاح الطبيعي
            } else {
                DB::table($table)->where($pk, $existing->{$pk})->update($values);
                $internalId = (int) $existing->{$pk};
            }
            $status = 'updated';
        } else {
            $values['created_at'] = now();
            if ($isComposite) {
                DB::table($table)->insert($values);
                $internalId = 0;
            } else {
                $internalId = (int) DB::table($table)->insertGetId($values);
            }
            $status = 'inserted';
        }

        $this->recordRef($cfg, $resource, $row, $internalId, $match, $client);

        return $status;
    }

    /** مورد مرتبط بمستخدم (users + الجدول). */
    private function upsertLinkedRow(array $cfg, string $resource, array $row, ?IntegrationClient $client): string
    {
        $userCfg = $cfg['linked_user'];

        // ---------- (1) المستخدم ----------
        $preparedUser = $this->prepare($userCfg, $row, true);
        if ($preparedUser['error'] !== null) {
            throw new RuntimeException($preparedUser['error']);
        }
        $userAttrs = $preparedUser['attrs'];

        $userTypeId = DB::table('user_types')
            ->where('user_type_code', $userCfg['user_type'])
            ->value('user_type_id');
        if (!$userTypeId) {
            throw new RuntimeException("user type '{$userCfg['user_type']}' is not configured.");
        }

        $existingUser = $this->findUser($userAttrs);

        if ($existingUser) {
            $update = $userAttrs;
            unset($update[$userCfg['primary_key']]);
            $update['updated_at'] = now();
            if ($this->hasSoftDeletes('users')) {
                $update['deleted_at'] = null;
            }
            DB::table('users')->where('user_id', $existingUser->user_id)->update($update);
            $userId = (int) $existingUser->user_id;
        } else {
            $userAttrs['password'] = Hash::make((string) config('integration.default_user_password'));
            $userAttrs['user_type_id'] = $userTypeId;
            $userAttrs['created_at'] = now();
            $userAttrs['updated_at'] = now();
            $userId = (int) DB::table('users')->insertGetId($userAttrs);
        }

        // ---------- (2) سجل الطالب/المحاضر ----------
        $preparedChild = $this->prepare($cfg, $row, true);
        if ($preparedChild['error'] !== null) {
            throw new RuntimeException($preparedChild['error']);
        }
        $childAttrs = $preparedChild['attrs'];
        $fkey = $userCfg['foreign_key'];
        $childAttrs[$fkey] = $userId;

        $table = $cfg['table'];
        $pk = $cfg['primary_key'];
        $child = DB::table($table)->where($fkey, $userId)->first();

        if ($child) {
            $update = $childAttrs;
            unset($update[$pk]);
            $update['updated_at'] = now();
            if ($this->hasSoftDeletes($table)) {
                $update['deleted_at'] = null;
            }
            DB::table($table)->where($pk, $child->{$pk})->update($update);
            $internalId = (int) $child->{$pk};
            $status = 'updated';
        } else {
            $childAttrs['created_at'] = now();
            $childAttrs['updated_at'] = now();
            $internalId = (int) DB::table($table)->insertGetId($childAttrs);
            $status = 'inserted';
        }

        $this->recordRef(
            $cfg,
            $resource,
            $row,
            $internalId,
            ['academic_number' => $userAttrs['academic_number'] ?? null],
            $client
        );

        return $status;
    }

    // =====================================================================
    // التعطيل/الحذف
    // =====================================================================

    public function deactivate(string $resource, array $rows, ?IntegrationClient $client = null): array
    {
        $cfg = $this->resource($resource);

        $count = 0;
        $skipped = [];

        foreach ($rows as $index => $row) {
            $label = "Row #{$index}";

            if (!is_array($row)) {
                $skipped[] = "{$label}: payload must be an object.";
                continue;
            }

            DB::beginTransaction();
            try {
                if (isset($cfg['linked_user'])) {
                    $this->deactivateLinkedRow($cfg, $resource, $row, $client);
                } else {
                    $this->deactivateSingleRow($cfg, $resource, $row, $client);
                }

                DB::commit();
                $count++;
            } catch (\Throwable $e) {
                DB::rollBack();
                $skipped[] = "{$label}: " . $this->shortError($e);
            }
        }

        return [
            'deactivated_records' => $count,
            'skipped_records'     => count($skipped),
            'skipped_reasons'     => $skipped,
        ];
    }

    private function deactivateSingleRow(array $cfg, string $resource, array $row, ?IntegrationClient $client): void
    {
        $table = $cfg['table'];
        $pk = $cfg['primary_key'];

        // مفتاح مركّب (pivot): لا id فردي — المطابقة بالمفتاح الطبيعي،
        // أو من internal_key المخزّن عند إرسال external_id.
        if (is_array($pk)) {
            $match = $this->resolveMatchByExternalId($cfg, $resource, $row, $client);

            if ($match === null) {
                $prepared = $this->prepare($cfg, $row, false);
                if ($prepared['error'] !== null) {
                    throw new RuntimeException($prepared['error']);
                }
                $match = $this->buildMatch($cfg, $prepared['attrs']);
                if ($match === null) {
                    throw new RuntimeException('missing match key.');
                }
            }

            if (!DB::table($table)->where($match)->exists()) {
                throw new RuntimeException('record not found.');
            }
            $this->applyDeactivate($table, $match, $cfg['deactivate']);
            return;
        }

        $internalId = $this->resolveByExternalId($cfg, $resource, $row, $client);

        if (!$internalId) {
            $prepared = $this->prepare($cfg, $row, false);
            if ($prepared['error'] !== null) {
                throw new RuntimeException($prepared['error']);
            }
            $match = $this->buildMatch($cfg, $prepared['attrs']);
            if ($match === null) {
                throw new RuntimeException('missing match key.');
            }
            $internalId = DB::table($table)->where($match)->value($pk);
        }

        if (!$internalId) {
            throw new RuntimeException('record not found.');
        }

        $this->applyDeactivate($table, [$pk => (int) $internalId], $cfg['deactivate']);
    }

    private function deactivateLinkedRow(array $cfg, string $resource, array $row, ?IntegrationClient $client): void
    {
        $userCfg = $cfg['linked_user'];
        $table = $cfg['table'];
        $pk = $cfg['primary_key'];
        $userId = null;

        // عبر المعرّف الخارجي
        $extField = $cfg['external_id'] ?? null;
        if ($client && $extField && !empty($row[$extField])) {
            $internalId = IntegrationEntityRef::where('client_id', $client->id)
                ->where('entity', $resource)
                ->where('external_id', $row[$extField])
                ->value('internal_id');

            if ($internalId) {
                $userId = DB::table($table)->where($pk, $internalId)->value($userCfg['foreign_key']);
            }
        }

        // عبر مفاتيح المستخدم
        if (!$userId) {
            $lookup = [];
            foreach (['academic_number', 'email', 'phone'] as $field) {
                if (!empty($row[$field])) {
                    $lookup[$field] = $row[$field];
                }
            }
            if ($lookup) {
                $user = $this->findUser($lookup);
                if ($user) {
                    $userId = $user->user_id;
                }
            }
        }

        if (!$userId) {
            throw new RuntimeException('record not found.');
        }

        $childId = DB::table($table)->where($userCfg['foreign_key'], $userId)->value($pk);
        if (!$childId) {
            throw new RuntimeException('record not found.');
        }

        $this->applyDeactivate($table, [$pk => (int) $childId], $cfg['deactivate']);
    }

    /**
     * تطبيق استراتيجية التعطيل:
     *   hard_delete : حذف فعلي للصف (جداول الروابط/ال.pivot بلا deleted_at أو حالة).
     *   column      : تعطيل عبر عمود (is_active/status).
     *   soft_delete : حذف ناعم عبر deleted_at.
     */
    private function applyDeactivate(string $table, array $where, array $deactivate): void
    {
        $type = $deactivate['type'] ?? null;

        if ($type === 'hard_delete') {
            DB::table($table)->where($where)->delete();
            return;
        }

        if ($type === 'column') {
            DB::table($table)->where($where)->update([
                $deactivate['column'] => $deactivate['value'] ?? 0,
                'updated_at' => now(),
            ]);
            return;
        }

        DB::table($table)->where($where)->update([
            'deleted_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // =====================================================================
    // التجهيز والتحويل
    // =====================================================================

    private function prepare(array $cfg, array $row, bool $enforceRequired): array
    {
        $attrs = Arr::only($row, $cfg['writable']);

        // تحويل الأنواع
        foreach ($cfg['casts'] ?? [] as $col => $type) {
            if (array_key_exists($col, $attrs) && $attrs[$col] !== null && $attrs[$col] !== '') {
                $attrs[$col] = match ($type) {
                    'bool'   => filter_var($attrs[$col], FILTER_VALIDATE_BOOLEAN),
                    'int'    => (int) $attrs[$col],
                    'gender' => $this->parseGender($attrs[$col]),
                    'json'   => is_array($attrs[$col]) ? json_encode($attrs[$col], JSON_UNESCAPED_UNICODE) : (string) $attrs[$col],
                    default  => (string) $attrs[$col],
                };
            }
        }

        // القيم الافتراضية
        foreach ($cfg['defaults'] ?? [] as $col => $value) {
            if (!array_key_exists($col, $attrs) || $attrs[$col] === null || $attrs[$col] === '') {
                $attrs[$col] = $value;
            }
        }

        // الحقول الإلزامية
        if ($enforceRequired) {
            foreach ($cfg['required'] ?? [] as $req) {
                if (!array_key_exists($req, $attrs) || $attrs[$req] === null || $attrs[$req] === '') {
                    return ['attrs' => [], 'error' => "missing required field '{$req}'."];
                }
            }
        }

        // القيم المسموحة (null = تخطٍّ: لأعمدة nullable مثل exam_type)
        foreach ($cfg['enum'] ?? [] as $col => $allowed) {
            if (array_key_exists($col, $attrs) && $attrs[$col] !== null && !in_array($attrs[$col], $allowed, true)) {
                return ['attrs' => [], 'error' => "invalid value for '{$col}'."];
            }
        }

        // المفاتيح الأجنبية البسيطة
        foreach ($cfg['foreign'] ?? [] as $field => $ref) {
            if (!array_key_exists($field, $attrs) || $attrs[$field] === null || $attrs[$field] === '') {
                unset($attrs[$field]);
                continue;
            }
            $target = $this->resolveForeign($ref, $attrs[$field]);
            if ($target === null) {
                return ['attrs' => [], 'error' => "unresolved reference '{$field}' = '{$attrs[$field]}'."];
            }
            $attrs[$ref['target']] = $target;
            unset($attrs[$field]);
        }

        // مفاتيح عبر مستخدم (users → جدول مرتبط): academic_number/email → lecturer_id مثلاً
        foreach ($cfg['foreign_via_user'] ?? [] as $field => $ref) {
            if (!array_key_exists($field, $attrs) || $attrs[$field] === null || $attrs[$field] === '') {
                unset($attrs[$field]);
                continue;
            }
            $value = $attrs[$field];

            $user = DB::table('users')
                ->where(function ($w) use ($ref, $value) {
                    foreach (array_values((array) $ref['user_ref']) as $i => $col) {
                        $i === 0 ? $w->where($col, $value) : $w->orWhere($col, $value);
                    }
                })
                ->first();

            $target = $user
                ? DB::table($ref['child_table'])->where($ref['child_fk'], $user->user_id)->value($ref['select'] ?? $ref['target'])
                : null;

            if ($target === null) {
                return ['attrs' => [], 'error' => "unresolved reference '{$field}' = '{$value}'."];
            }

            $attrs[$ref['target']] = (int) $target;
            unset($attrs[$field]);
        }

        // المراجع المتداخلة (parents)
        foreach ($cfg['parents'] ?? [] as $targetCol => $parent) {
            $field = $parent['field'];
            if (!array_key_exists($field, $attrs) || empty($attrs[$field])) {
                unset($attrs[$field]);
                if ($enforceRequired && !($parent['optional'] ?? false)) {
                    return ['attrs' => [], 'error' => "missing parent reference '{$field}'."];
                }
                continue;
            }
            $parentId = $this->resolveParentByObject($parent['resource'], $attrs[$field]);
            if ($parentId === null) {
                return ['attrs' => [], 'error' => "unresolved parent '{$field}' ({$parent['resource']})."];
            }
            $attrs[$targetCol] = $parentId;
            unset($attrs[$field]);
        }

        // المفاتيح الأجنبية المركّبة (بعد المراجع المتداخلة حتى تستفيد من مفاتيح الأب المحلولة)
        foreach ($cfg['foreign_composite'] ?? [] as $targetCol => $ref) {
            $nullable = (bool) ($ref['nullable'] ?? false);
            $where = [];
            $ready = true;
            foreach ($ref['key'] as $k) {
                $val = $attrs[$k] ?? null;
                if ($val === '') {
                    $val = null;
                }
                if (!$nullable && ($val === null)) {
                    $ready = false;
                    break;
                }
                $where[$k] = $val;
            }
            if (!$ready) {
                foreach ($ref['unset'] ?? [] as $u) {
                    unset($attrs[$u]);
                }
                continue; // مرجع اختياري
            }

            $query = DB::table($ref['table'])->where($where);
            if ($this->hasSoftDeletes($ref['table'])) {
                $query->whereNull('deleted_at');
            }
            $value = $query->value($ref['target']);
            if ($value === null) {
                return ['attrs' => [], 'error' => "unresolved reference '{$targetCol}'."];
            }
            $attrs[$targetCol] = (int) $value;
            foreach ($ref['unset'] ?? [] as $u) {
                unset($attrs[$u]);
            }
        }

        return ['attrs' => $attrs, 'error' => null];
    }

    private function buildMatch(array $cfg, array $attrs): ?array
    {
        $match = [];
        foreach ($cfg['match'] ?? [] as $col) {
            $match[$col] = $attrs[$col] ?? null;
        }

        return $match ?: null;
    }

    /**
     * كشف التعارض: يمنع تزاحم الأبعاد المعلَنة (محاضر/مجموعة/قاعة) في نفس
     * (اليوم + الحصة + السنة الأكاديمية) مع تقاطع الفترات الزمنية.
     */
    private function assertNoConflicts(array $cfg, array $attrs, ?int $excludeId): void
    {
        $cc = $cfg['conflict'] ?? null;
        if (!$cc) {
            return;
        }

        // لا نفحص عند تعطيل السجل
        $active = $cc['active'] ?? null;
        if ($active && array_key_exists($active['column'], $attrs)
            && (int) $attrs[$active['column']] !== (int) $active['value']) {
            return;
        }

        // نطاق التعارض (يوم/حصة/سنة) يجب أن يكون مكتملاً
        foreach ($cc['scope'] as $sc) {
            if (!array_key_exists($sc, $attrs) || $attrs[$sc] === null || $attrs[$sc] === '') {
                return;
            }
        }

        $table = $cfg['table'];
        $pk = $cfg['primary_key'];
        $startCol = $cc['dates']['start'];
        $endCol = $cc['dates']['end'];

        foreach ($cc['dimensions'] as $dim) {
            $col = $dim['column'];
            if (!array_key_exists($col, $attrs) || $attrs[$col] === null || $attrs[$col] === '') {
                continue;
            }

            $query = DB::table($table)->where($col, $attrs[$col]);
            foreach ($cc['scope'] as $sc) {
                $query->where($sc, $attrs[$sc]);
            }
            if ($active) {
                $query->where($active['column'], $active['value']);
            }
            if ($excludeId !== null) {
                $query->where($pk, '!=', $excludeId);
            }
            // تقاطع الفترات الزمنية
            $query->where($startCol, '<=', $attrs[$endCol]);
            $query->where($endCol, '>=', $attrs[$startCol]);

            if ($query->exists()) {
                throw new RuntimeException(
                    "conflict: {$dim['label']} is already booked on the same day/period ({$attrs['academic_year']})."
                );
            }
        }
    }

    /**
     * حلّ مفتاح أجنبي بسيط: يبحث في جدول المصدر بالعمود `ref` ويقرأ قيمة `select`
     * (أو `target` عند غيابه — توافق للخلف)، ثم تُخزَّن في `attrs[target]`.
     */
    private function resolveForeign(array $ref, $value): ?int
    {
        $query = DB::table($ref['table'])->where($ref['ref'], $value);
        if ($this->hasSoftDeletes($ref['table'])) {
            $query->whereNull('deleted_at');
        }
        $result = $query->value($ref['select'] ?? $ref['target']);

        return $result === null ? null : (int) $result;
    }

    private function resolveParentByObject(string $resource, $object): ?int
    {
        $cfg = $this->resource($resource);
        if (!$cfg || !is_array($object)) {
            return null;
        }

        $prepared = $this->prepare($cfg, $object, true);
        if ($prepared['error'] !== null) {
            return null;
        }

        $match = $this->buildMatch($cfg, $prepared['attrs']);
        if ($match === null) {
            return null;
        }

        $query = DB::table($cfg['table'])->where($match);
        if ($this->hasSoftDeletes($cfg['table'])) {
            $query->whereNull('deleted_at');
        }
        $id = $query->value($cfg['primary_key']);

        return $id === null ? null : (int) $id;
    }

    private function findUser(array $attrs)
    {
        $academic = $attrs['academic_number'] ?? null;
        $email = $attrs['email'] ?? null;
        $phone = $attrs['phone'] ?? null;

        if (!$academic && !$email && !$phone) {
            return null;
        }

        return DB::table('users')
            ->where(function ($w) use ($academic, $email, $phone) {
                if (!empty($academic)) {
                    $w->orWhere('academic_number', $academic);
                }
                if (!empty($email)) {
                    $w->orWhere('email', $email);
                }
                if (!empty($phone)) {
                    $w->orWhere('phone', $phone);
                }
            })
            ->first();
    }

    private function resolveByExternalId(array $cfg, string $resource, array $row, ?IntegrationClient $client): ?int
    {
        $extField = $cfg['external_id'] ?? null;
        if (!$client || !$extField || empty($row[$extField])) {
            return null;
        }

        $id = IntegrationEntityRef::where('client_id', $client->id)
            ->where('entity', $resource)
            ->where('external_id', $row[$extField])
            ->value('internal_id');

        return $id === null ? null : (int) $id;
    }

    /**
     * المفاتيح الطبيعية المخزّنة لسجل بمفتاح مركّب (internal_key) عبر external_id.
     * تُستخدم في التعطيل لأن المفاتيح المركّبة لا تحمل id فردياً.
     */
    private function resolveMatchByExternalId(array $cfg, string $resource, array $row, ?IntegrationClient $client): ?array
    {
        $extField = $cfg['external_id'] ?? null;
        if (!$client || !$extField || empty($row[$extField])) {
            return null;
        }

        $stored = IntegrationEntityRef::where('client_id', $client->id)
            ->where('entity', $resource)
            ->where('external_id', $row[$extField])
            ->value('internal_key');

        $match = $stored ? json_decode($stored, true) : null;

        return is_array($match) && $match !== [] ? $match : null;
    }

    private function recordRef(array $cfg, string $resource, array $row, int $internalId, array $match, ?IntegrationClient $client): void
    {
        if (!$client) {
            return;
        }

        $extField = $cfg['external_id'] ?? null;
        $extId = $extField ? ($row[$extField] ?? null) : null;

        // مفتاح مركّب بلا external_id: لا سجل ربط (لا يوجد id فردي يُخزَّن —
        // المطابقة تكون دائماً بالمفتاح الطبيعي).
        if ($extId === null && is_array($cfg['primary_key'] ?? null)) {
            return;
        }

        $internalKey = json_encode($match, JSON_UNESCAPED_UNICODE);

        $keys = $extId !== null
            ? ['client_id' => $client->id, 'entity' => $resource, 'external_id' => $extId]
            : ['client_id' => $client->id, 'entity' => $resource, 'external_id' => null, 'internal_id' => $internalId];

        IntegrationEntityRef::updateOrCreate($keys, [
            'internal_id'  => $internalId,
            'internal_key' => $internalKey,
        ]);
    }

    /** تحويل الجنس: 1 = ذكر، 0 = أنثى (نفس منطق النظام الحالي). */
    private function parseGender($value): int
    {
        if ($value === null) {
            return 0;
        }
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        if (is_numeric($value)) {
            return ((int) $value) > 0 ? 1 : 0;
        }

        $s = mb_strtolower(trim((string) $value));
        $s = strtr($s, ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ى' => 'ي', 'ة' => 'ه', 'ـ' => '']);

        $maleSet = ['1', 'm', 'male', 'man', 'boy', 'ذكر', 'ذ', 'ذكور'];
        $femaleSet = ['0', 'f', 'female', 'woman', 'girl', 'انثى', 'انثي', 'انث', 'اناث', 'بنت', 'فتاه', 'فتاة', 'نساء', 'سيده', 'امرأه', 'امراه', 'ا'];

        if (in_array($s, $maleSet, true)) {
            return 1;
        }
        if (in_array($s, $femaleSet, true)) {
            return 0;
        }

        $c = mb_substr($s, 0, 1);
        if ($c === 'm' || $c === 'ذ') {
            return 1;
        }
        if ($c === 'f' || $c === 'ا' || $c === 'ب') {
            return 0;
        }

        return 0;
    }

    private function hasSoftDeletes(string $table): bool
    {
        return Schema::hasColumn($table, 'deleted_at');
    }

    private function shortError(\Throwable $e): string
    {
        return mb_substr($e->getMessage(), 0, 300);
    }
}
