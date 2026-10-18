<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Gap-tolerant counter for human-facing numbers (order №1042).
 * The row lock serializes concurrent callers until their transaction commits.
 */
class Sequence
{
    public static function next(string $name): int
    {
        return DB::transaction(function () use ($name) {
            $current = DB::table('sequences')->where('name', $name)->lockForUpdate()->value('value');

            if ($current === null) {
                DB::table('sequences')->insert(['name' => $name, 'value' => 1001]);

                return 1001;
            }

            DB::table('sequences')->where('name', $name)->update(['value' => $current + 1]);

            return (int) $current + 1;
        });
    }
}
