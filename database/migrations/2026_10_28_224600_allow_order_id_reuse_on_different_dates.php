<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function sameColumns(array $columns, array $expected): bool
    {
        $columns = array_values(array_map(
            static fn ($value) => strtolower(trim((string) $value, " \t\n\r\0\x0B\"`")),
            $columns
        ));
        $expected = array_values(array_map('strtolower', $expected));
        sort($columns);
        sort($expected);
        return $columns === $expected;
    }

    public function up(): void
    {
        if (!Schema::hasTable('fallout_data')) {
            return;
        }

        foreach (['batch_id', 'order_id', 'tanggal'] as $column) {
            if (!Schema::hasColumn('fallout_data', $column)) {
                return;
            }
        }

        $driver = DB::connection()->getDriverName();
        $hasNewUnique = false;

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $groups = [];
            foreach (DB::select('SHOW INDEX FROM fallout_data') as $index) {
                $name = (string) $index->Key_name;
                if ($name === 'PRIMARY' || (int) $index->Non_unique !== 0) {
                    continue;
                }
                $groups[$name]['columns'][(int) $index->Seq_in_index] = strtolower((string) $index->Column_name);
            }

            foreach ($groups as $name => $info) {
                $columns = array_values($info['columns'] ?? []);
                if ($this->sameColumns($columns, ['batch_id', 'order_id'])) {
                    $safeName = str_replace('`', '``', $name);
                    DB::statement("ALTER TABLE `fallout_data` DROP INDEX `{$safeName}`");
                } elseif ($this->sameColumns($columns, ['batch_id', 'order_id', 'tanggal'])) {
                    $hasNewUnique = true;
                }
            }
        } elseif ($driver === 'pgsql') {
            $constraints = DB::select(<<<'SQL'
                SELECT c.conname,
                       array_agg(a.attname ORDER BY k.ordinality) AS columns
                FROM pg_constraint c
                JOIN pg_class t ON t.oid = c.conrelid
                JOIN pg_namespace n ON n.oid = t.relnamespace
                CROSS JOIN LATERAL unnest(c.conkey) WITH ORDINALITY AS k(attnum, ordinality)
                JOIN pg_attribute a ON a.attrelid = t.oid AND a.attnum = k.attnum
                WHERE c.contype = 'u'
                  AND t.relname = 'fallout_data'
                  AND n.nspname = current_schema()
                GROUP BY c.conname
            SQL);

            foreach ($constraints as $constraint) {
                $columns = is_array($constraint->columns)
                    ? $constraint->columns
                    : str_getcsv(trim((string) $constraint->columns, '{}'));
                if ($this->sameColumns($columns, ['batch_id', 'order_id'])) {
                    $name = (string) $constraint->conname;
                    if (preg_match('/^[A-Za-z0-9_]+$/', $name)) {
                        DB::statement('ALTER TABLE "' . str_replace('"', '""', 'fallout_data') . '" DROP CONSTRAINT "' . str_replace('"', '""', $name) . '"');
                    }
                } elseif ($this->sameColumns($columns, ['batch_id', 'order_id', 'tanggal'])) {
                    $hasNewUnique = true;
                }
            }

            // Also handle unique indexes created directly, rather than as table constraints.
            $indexes = DB::select(<<<'SQL'
                SELECT indexname, indexdef
                FROM pg_indexes
                WHERE schemaname = current_schema()
                  AND tablename = 'fallout_data'
                  AND indexdef ILIKE 'CREATE UNIQUE INDEX%'
            SQL);
            foreach ($indexes as $index) {
                $name = (string) $index->indexname;
                $definition = (string) $index->indexdef;
                if (!preg_match('/\\(([^()]*)\\)\\s*$/', $definition, $match)) {
                    continue;
                }
                $columns = array_map(static function ($column) {
                    $column = trim($column);
                    $column = preg_replace('/\\s+(ASC|DESC)(\\s+NULLS\\s+(FIRST|LAST))?$/i', '', $column);
                    return trim($column, " \t\n\r\0\x0B\"`");
                }, explode(',', $match[1]));
                if ($this->sameColumns($columns, ['batch_id', 'order_id'])) {
                    if (preg_match('/^[A-Za-z0-9_]+$/', $name)) {
                        DB::statement('DROP INDEX IF EXISTS "' . str_replace('"', '""', $name) . '"');
                    }
                } elseif ($this->sameColumns($columns, ['batch_id', 'order_id', 'tanggal'])) {
                    $hasNewUnique = true;
                }
            }
        } else {
            // Unsupported driver: leave existing constraints untouched rather than guessing.
            return;
        }

        if (!$hasNewUnique) {
            Schema::table('fallout_data', function (Blueprint $table) {
                $table->unique(
                    ['batch_id', 'order_id', 'tanggal'],
                    'fallout_data_batch_order_date_unique'
                );
            });
        }
    }

    public function down(): void
    {
        // Do not restore the old (batch_id, order_id) constraint automatically.
        // Valid next-day records may now share an Order ID within one batch.
        // The application enforces the intended Witel + date + Order ID rule.
    }
};
