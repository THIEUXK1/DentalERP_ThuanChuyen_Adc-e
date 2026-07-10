<?php

namespace App\Models\Concerns;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

trait GeneratesUniqueCode
{
    /**
     * Create a record, retrying with a freshly generated code if two requests
     * race on generateCode()'s max(id)+1 and collide on the code unique index.
     *
     * Wrapped in DB::transaction so a failed attempt only rolls back to a
     * savepoint when called inside a caller's own transaction, instead of
     * poisoning it (Postgres aborts the whole transaction on any statement
     * error otherwise, which would make the retry fail too).
     */
    public static function createWithCode(array $attributes = []): static
    {
        for ($attempt = 0; ; $attempt++) {
            try {
                return DB::transaction(fn () => static::create([...$attributes, 'code' => static::generateCode()]));
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt >= 3 || ! str_contains($e->getMessage(), '_code_unique')) {
                    throw $e;
                }
            }
        }
    }
}
