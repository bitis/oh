<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Date;

trait UseTimestamps
{
    /**
     * Convert a DateTime to a storable string.
     *
     * @param  mixed  $value
     * @return string|null
     */
    public function fromDateTime($value)
    {
        return Date::now()->timestamp;
    }
}
