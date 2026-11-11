<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

/**
 * One-off import from the old ichiman database (UUID primary keys) into kei_v2 (bigint keys).
 *
 * - Rows get new sequential ids, ordered by created_at, so ids follow the original chronology.
 * - Every *_id foreign key is translated through an in-memory uuid => id map.
 * - Columns present in both schemas are copied as-is (timestamps and soft deletes included);
 *   columns that only exist in the old schema are reported and skipped.
 * - Everything runs in one transaction and is verified before commit, so it is safe to re-run.
 */
class ImportLegacyData extends Command
{
    protected $signature = 'legacy:import
        {--dry-run : Import and verify inside a transaction, then roll back}
        {--force : Replace data that already exists in the kei_v2 tables}';

    protected $description = 'Import data from the old ichiman database into kei_v2';

    private const LEGACY = 'legacy';

    private const CHUNK = 500;

    /** Target tables, parents first. */
    private const TABLES = [
        'stores', 'users', 'cars', 'slides', 'slide_has_cars', 'photos', 'reservations', 'gyauto_questions',
    ];

    private const ROLES = ['superadmin', 'admin', 'user'];

    /** @var array<string, array<string, int>> table => [old uuid => new id] */
    private array $maps = [];

    /** @var array<string, int> table => number of rows that should end up in kei_v2 */
    private array $expected = [];

    /** @var array<string, string> new user id => role name */
    private array $userRoles = [];

    public function handle(): int
    {
        try {
            DB::connection(self::LEGACY)->getPdo();
        } catch (Throwable $e) {
            $this->error('Cannot connect to the legacy database: '.$e->getMessage());
            $this->line('Check the LEGACY_DB_* values in .env.');

            return self::FAILURE;
        }

        $missingRoles = array_diff(self::ROLES, Role::whereIn('name', self::ROLES)->pluck('name')->all());
        if ($missingRoles) {
            $this->error('Missing roles: '.implode(', ', $missingRoles).'. Run: php artisan db:seed --class=RoleSeeder');

            return self::FAILURE;
        }

        $nonEmpty = array_filter(self::TABLES, fn ($t) => DB::table($t)->exists());
        if ($nonEmpty && ! $this->option('force') && ! $this->option('dry-run')) {
            $this->error('These tables already contain data: '.implode(', ', $nonEmpty));
            $this->line('Re-run with --force to replace it, or --dry-run to only test the import.');

            return self::FAILURE;
        }

        DB::beginTransaction();

        try {
            $this->clearTarget();

            $this->importStores();
            $this->importUsers();
            $this->importSimple('cars');
            $this->importSlides();
            $this->importSlideCars();
            $this->importWithStore('photos', nullable: true);
            $this->importWithStore('reservations', nullable: false);
            $this->importWithStore('gyauto_questions', nullable: false);

            $this->verify();
        } catch (Throwable $e) {
            DB::rollBack();
            $this->newLine();
            $this->error('Import aborted, nothing was changed: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            DB::rollBack();
            $this->newLine();
            $this->info('Dry run OK: all checks passed. Changes rolled back.');

            return self::SUCCESS;
        }

        DB::commit();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->newLine();
        $this->info('Import committed.');

        return self::SUCCESS;
    }

    private function clearTarget(): void
    {
        DB::table(config('permission.table_names.model_has_roles'))
            ->where('model_type', (new User)->getMorphClass())
            ->delete();

        // Old user ids are meaningless now; drop sessions so nobody is logged in as the wrong user.
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->delete();
        }

        foreach (array_reverse(self::TABLES) as $table) {
            DB::table($table)->delete();
        }
    }

    private function importStores(): void
    {
        $this->copy('stores');
    }

    private function importUsers(): void
    {
        $morphKey = config('permission.column_names.model_morph_key');

        $this->copy(
            'users',
            foreignKeys: ['store_id' => ['stores', true]],
            // kei_v2 users have no soft deletes: deleted accounts are not carried over.
            filter: fn ($row) => empty($row->deleted_at ?? null),
            after: function (object $old, int $newId) {
                $role = ! empty($old->is_superadmin) ? 'superadmin' : ($old->role ?? null);

                if (! in_array($role, self::ROLES, true)) {
                    $this->warn("  users: unknown role '{$role}' for {$old->email}, assigning 'admin'");
                    $role = 'admin';
                }

                $this->userRoles[$newId] = $role;
            },
        );

        $roleIds = Role::whereIn('name', self::ROLES)->pluck('id', 'name');
        $rows = [];
        foreach ($this->userRoles as $userId => $role) {
            $rows[] = [
                'role_id' => $roleIds[$role],
                'model_type' => (new User)->getMorphClass(),
                $morphKey => $userId,
            ];
        }
        DB::table(config('permission.table_names.model_has_roles'))->insert($rows);
    }

    private function importSimple(string $table): void
    {
        $this->copy($table);
    }

    private function importSlides(): void
    {
        $this->copy('slides', transform: function (array $data) {
            // Some old schemas stored active as 'active'/'inactive' strings instead of 1/0.
            if (array_key_exists('active', $data)) {
                $data['active'] = in_array(strtolower((string) $data['active']), ['1', 'active', 'true'], true) ? 1 : 0;
            }

            return $data;
        });
    }

    private function importSlideCars(): void
    {
        $hasOrder = Schema::connection(self::LEGACY)->hasColumn('slide_has_cars', 'order_number');
        $positions = [];

        $this->copy(
            'slide_has_cars',
            foreignKeys: ['slide_id' => ['slides', false], 'car_id' => ['cars', false]],
            orderBy: ['slide_id', 'id'],
            transform: function (array $data, object $old) use ($hasOrder, &$positions) {
                // Old pivot had no order column: keep the order the cars were attached in,
                // 0-based like SlideController does.
                $position = $positions[$old->slide_id] = ($positions[$old->slide_id] ?? -1) + 1;

                if (! $hasOrder || ($data['order_number'] ?? null) === null) {
                    $data['order_number'] = $position;
                }

                return $data;
            },
        );
    }

    private function importWithStore(string $table, bool $nullable): void
    {
        $this->copy($table, foreignKeys: ['store_id' => ['stores', $nullable]]);
    }

    /**
     * Copy one table: shared columns as-is, new sequential ids, foreign keys translated.
     *
     * @param  array<string, array{0: string, 1: bool}>  $foreignKeys  column => [parent table, nullable]
     * @param  list<string>|null  $orderBy  legacy columns to order by (default: created_at, id)
     */
    private function copy(
        string $table,
        array $foreignKeys = [],
        ?callable $filter = null,
        ?callable $transform = null,
        ?callable $after = null,
        ?array $orderBy = null,
    ): void {
        $legacyColumns = Schema::connection(self::LEGACY)->getColumnListing($table);
        $targetColumns = Schema::getColumnListing($table);

        $shared = array_values(array_diff(array_intersect($targetColumns, $legacyColumns), ['id']));
        $dropped = array_diff($legacyColumns, $targetColumns);

        $query = DB::connection(self::LEGACY)->table($table);
        foreach ($orderBy ?? ['created_at', 'id'] as $column) {
            $query->orderBy($column);
        }

        $nextId = 1;
        $skipped = 0;
        $batch = [];
        $this->maps[$table] = [];

        foreach ($query->cursor() as $old) {
            if ($filter && ! $filter($old)) {
                $skipped++;

                continue;
            }

            $data = ['id' => $nextId];
            foreach ($shared as $column) {
                $data[$column] = $old->{$column};
            }

            foreach ($foreignKeys as $column => [$parent, $nullable]) {
                $data[$column] = $this->mapKey($table, $column, $old->{$column}, $parent, $nullable, $old->id);
            }

            if ($transform) {
                $data = $transform($data, $old);
            }

            $this->maps[$table][(string) $old->id] = $nextId;
            if ($after) {
                $after($old, $nextId);
            }

            $batch[] = $data;
            $nextId++;

            if (count($batch) >= self::CHUNK) {
                DB::table($table)->insert($batch);
                $batch = [];
            }
        }

        if ($batch) {
            DB::table($table)->insert($batch);
        }

        $this->expected[$table] = $nextId - 1;

        $line = sprintf('  %-17s %5d rows', $table, $nextId - 1);
        if ($skipped) {
            $line .= ", {$skipped} skipped (soft-deleted)";
        }
        if ($dropped) {
            $line .= '  | not carried over: '.implode(', ', $dropped);
        }
        $this->line($line);
    }

    private function mapKey(string $table, string $column, mixed $uuid, string $parent, bool $nullable, mixed $rowId): ?int
    {
        if ($uuid === null || $uuid === '') {
            if ($nullable) {
                return null;
            }

            throw new RuntimeException("{$table} {$rowId}: {$column} is empty but required in kei_v2");
        }

        $id = $this->maps[$parent][(string) $uuid] ?? null;

        if ($id === null) {
            if ($nullable) {
                $this->warn("  {$table} {$rowId}: {$column} {$uuid} not found in {$parent}, set to NULL");

                return null;
            }

            throw new RuntimeException("{$table} {$rowId}: {$column} {$uuid} not found in {$parent}");
        }

        return $id;
    }

    private function verify(): void
    {
        $this->newLine();
        $this->line('Verifying...');
        $problems = [];

        // 1. Row counts, including soft-deleted rows.
        $rows = [];
        foreach (self::TABLES as $table) {
            $legacy = DB::connection(self::LEGACY)->table($table)->count();
            $actual = DB::table($table)->count();
            $ok = $actual === $this->expected[$table];
            $rows[] = [$table, $legacy, $this->expected[$table], $actual, $ok ? 'OK' : 'MISMATCH'];
            if (! $ok) {
                $problems[] = "{$table}: expected {$this->expected[$table]} rows, found {$actual}";
            }
        }
        $this->table(['table', 'ichiman', 'expected', 'kei_v2', 'status'], $rows);

        // 2. Rows per store must match for every store-owned table.
        foreach (['users', 'photos', 'reservations', 'gyauto_questions'] as $table) {
            $legacyQuery = DB::connection(self::LEGACY)->table($table)
                ->selectRaw('store_id, count(*) as total')
                ->groupBy('store_id');
            if ($table === 'users') {
                $legacyQuery->whereNull('deleted_at');
            }

            $expected = [];
            foreach ($legacyQuery->get() as $row) {
                $key = $row->store_id === null ? 'null' : ($this->maps['stores'][$row->store_id] ?? 'null');
                $expected[$key] = ($expected[$key] ?? 0) + $row->total;
            }

            $actual = DB::table($table)->selectRaw('store_id, count(*) as total')->groupBy('store_id')
                ->get()->mapWithKeys(fn ($r) => [$r->store_id ?? 'null' => (int) $r->total])->all();

            ksort($expected);
            ksort($actual);
            if ($expected != $actual) {
                $problems[] = "{$table}: rows per store differ";
            }
        }

        // 3. Every slide keeps exactly the same set of cars.
        $legacyPairs = DB::connection(self::LEGACY)->table('slide_has_cars')->get(['slide_id', 'car_id'])
            ->map(fn ($r) => $this->maps['slides'][$r->slide_id].'-'.$this->maps['cars'][$r->car_id])
            ->sort()->values()->all();
        $targetPairs = DB::table('slide_has_cars')->get(['slide_id', 'car_id'])
            ->map(fn ($r) => $r->slide_id.'-'.$r->car_id)
            ->sort()->values()->all();
        if ($legacyPairs !== $targetPairs) {
            $problems[] = 'slide_has_cars: slide/car pairs differ';
        }

        // 4. Every imported user has exactly one role.
        $withRole = DB::table(config('permission.table_names.model_has_roles'))
            ->where('model_type', (new User)->getMorphClass())->count();
        if ($withRole !== $this->expected['users']) {
            $problems[] = "roles: {$withRole} role assignments for {$this->expected['users']} users";
        }

        if ($problems) {
            throw new RuntimeException("verification failed:\n - ".implode("\n - ", $problems));
        }

        $this->info('All checks passed (row counts, rows per store, slide cars, user roles).');
    }
}
