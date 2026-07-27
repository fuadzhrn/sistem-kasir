<?php

namespace App\Services\Demo;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Closure;

class DemoExecutionContext
{
    public function at(CarbonInterface $date, Closure $callback): mixed
    {
        $previous = Carbon::getTestNow();
        Carbon::setTestNow($date);

        try {
            return $callback();
        } finally {
            Carbon::setTestNow($previous);
        }
    }
}
