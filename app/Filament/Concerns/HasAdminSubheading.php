<?php

namespace App\Filament\Concerns;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Lang;

trait HasAdminSubheading
{
    public function getSubheading(): string|Htmlable|null
    {
        $key = static::getResource()::adminLexicon().'.description';

        return Lang::has($key) ? __($key) : null;
    }
}
