<?php

namespace App\Support\Integrations;

class DrutogotiProfiler implements PerformanceProfiler
{
    public function enabled(): bool
    {
        return (bool) config('drutogoti.enabled');
    }

    public function slowThreshold(): int
    {
        return (int) config('drutogoti.watchers.queries.slow_threshold_ms');
    }
}
