<?php

namespace App\Http\Controllers\Admissions\Concerns;

/**
 * The XssSanitizer middleware runs strip_tags() over all input, which turns nulls back into
 * empty strings; nullable integer and date columns reject those.
 */
trait NullsBlankInput
{
    protected function nullBlanks(array $data): array
    {
        return array_map(fn ($value) => $value === '' ? null : $value, $data);
    }
}
